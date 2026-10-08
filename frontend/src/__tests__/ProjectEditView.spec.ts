import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import type { AuthHeaders } from '@/api/admin'
import { api } from '@/api/client'
import type { FetchResult } from '@/api/problem'
import ProjectEditView from '@/views/admin/ProjectEditView.vue'

import { okResult, problemResult } from './fixtures'

vi.mock('@/api/client', () => ({
  api: {
    GET: vi.fn<(path: string) => Promise<unknown>>(),
    POST: vi.fn<(path: string, init: unknown) => Promise<unknown>>(),
    PUT: vi.fn<(path: string, init: unknown) => Promise<unknown>>(),
  },
}))
// Token handling has its own tests: here every admin call just goes through with a fixed token
vi.mock('@/api/admin', async () => {
  const { unwrap } = await vi.importActual<typeof import('@/api/problem')>('@/api/problem')
  return {
    adminCall: async <T>(call: (auth: AuthHeaders) => Promise<FetchResult<T>>) =>
      unwrap(await call({ headers: { Authorization: 'Bearer test' } })),
  }
})

const get = vi.mocked(api.GET)
const post = vi.mocked(api.POST)

const TECHNOLOGIES = [
  { id: 1, slug: 'php', name: 'PHP', icon: 'php' },
  { id: 2, slug: 'symfony', name: 'Symfony', icon: 'symfony' },
]

function savedProject(overrides: object = {}) {
  return {
    id: 7,
    slug: 'portfolio',
    title: 'Портфолио',
    description: 'Этот сайт',
    content: '',
    coverImageUrl: null,
    coverThumbnails: {},
    repoUrl: null,
    demoUrl: null,
    isPublished: false,
    sortOrder: 0,
    technologies: [],
    images: [],
    createdAt: '2026-10-08T10:00:00+00:00',
    updatedAt: '2026-10-08T10:00:00+00:00',
    ...overrides,
  }
}

beforeEach(() => {
  vi.resetAllMocks()
  get.mockImplementation(((path: string) =>
    Promise.resolve(
      path === '/api/admin/technologies'
        ? okResult({ items: TECHNOLOGIES })
        : okResult(savedProject({ title: 'Старое название', slug: 'old-slug' })),
    )) as never)
})

async function open(url: string, id?: string) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/projects', name: 'admin-projects', component: { render: () => null } },
      { path: '/admin/projects/new', name: 'admin-project-new', component: ProjectEditView },
      {
        path: '/admin/projects/:id',
        name: 'admin-project-edit',
        component: ProjectEditView,
        props: true,
      },
    ],
  })
  await router.push(url)
  await router.isReady()
  const wrapper = mount(ProjectEditView, {
    props: id === undefined ? {} : { id },
    global: { plugins: [router] },
  })
  await flushPromises()
  return { router, wrapper }
}

describe('ProjectEditView', () => {
  it('builds the address from the title until it is edited by hand', async () => {
    const { wrapper } = await open('/admin/projects/new')
    const slug = wrapper.find<HTMLInputElement>('#project-slug')

    await wrapper.find('#project-title').setValue('Щука и ёж')
    expect(slug.element.value).toBe('shchuka-i-ezh')

    await slug.setValue('my-slug')
    await wrapper.find('#project-title').setValue('Другое название')
    expect(slug.element.value).toBe('my-slug')
  })

  it('creates the project with empty links as null and opens it for editing', async () => {
    post.mockResolvedValue(okResult(savedProject(), 201))
    const { wrapper, router } = await open('/admin/projects/new')

    await wrapper.find('#project-title').setValue('Портфолио')
    await wrapper.find('#project-description').setValue('Этот сайт')
    await wrapper.find('input[type="checkbox"][value="symfony"]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(post).toHaveBeenCalledWith('/api/admin/projects', {
      headers: { Authorization: 'Bearer test' },
      body: expect.objectContaining({
        slug: 'portfolio',
        repoUrl: null,
        demoUrl: null,
        technologies: ['symfony'],
      }),
    })
    expect(router.currentRoute.value.fullPath).toBe('/admin/projects/7')
  })

  it('shows an unknown technology error next to the technology list', async () => {
    post.mockResolvedValue(
      problemResult(422, {
        violations: [{ propertyPath: 'technologies[0]', message: 'Unknown technology "go".' }],
      }),
    )
    const { wrapper } = await open('/admin/projects/new')

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Unknown technology "go".')
  })

  it('keeps the address of an existing project when its title changes', async () => {
    // Changing a published address silently would break every link to the project
    const { wrapper } = await open('/admin/projects/7', '7')
    expect(get).toHaveBeenCalledWith('/api/admin/projects/{id}', {
      headers: { Authorization: 'Bearer test' },
      params: { path: { id: '7' } },
    })

    await wrapper.find('#project-title').setValue('Новое название')

    expect(wrapper.find<HTMLInputElement>('#project-slug').element.value).toBe('old-slug')
  })
})
