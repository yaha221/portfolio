import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, type Mock, vi } from 'vitest'
import { createMemoryHistory, createRouter, type Router } from 'vue-router'

import { api } from '@/api/client'
import ProjectsView from '@/views/ProjectsView.vue'

import { okResult } from './fixtures'

vi.mock('@/api/client', () => ({ api: { GET: vi.fn<(path: string) => Promise<unknown>>() } }))

// openapi-fetch's GET is overloaded per path: a plain signature keeps mock.calls readable
const get = vi.mocked(api.GET) as unknown as Mock<
  (path: string, init?: unknown) => Promise<unknown>
>

const PROJECT = {
  slug: 'portfolio',
  title: 'Портфолио',
  description: 'Этот сайт',
  coverThumbnails: {},
  technologies: [{ slug: 'php', name: 'PHP', icon: 'php' }],
}

beforeEach(() => {
  get.mockReset()
  get.mockImplementation((path: string) =>
    Promise.resolve(
      path === '/api/technologies'
        ? okResult({ items: [{ slug: 'php', name: 'PHP', icon: 'php', projectCount: 1 }] })
        : okResult({ items: [PROJECT], meta: { page: 2, perPage: 12, total: 25, pages: 3 } }),
    ),
  )
})

async function open(url: string): Promise<{ router: Router; wrapper: ReturnType<typeof mount> }> {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/projects', name: 'projects', component: ProjectsView },
      { path: '/projects/:slug', name: 'project', component: { render: () => null } },
    ],
  })
  await router.push(url)
  await router.isReady()
  const wrapper = mount(ProjectsView, { global: { plugins: [router] } })
  await flushPromises()
  return { router, wrapper }
}

function projectQueries() {
  return get.mock.calls
    .filter(([path]) => path === '/api/projects')
    .map(([, init]) => (init as { params: { query: unknown } }).params.query)
}

describe('ProjectsView', () => {
  it('takes the filter and the page from the address', async () => {
    await open('/projects?technology=php&page=2')

    expect(projectQueries()).toEqual([{ page: 2, perPage: 12, technology: 'php' }])
  })

  it.each(['abc', '0', '-1', '1.5'])('treats page=%s as the first page', async (page) => {
    await open(`/projects?page=${page}`)

    expect(projectQueries()).toEqual([{ page: 1, perPage: 12, technology: undefined }])
  })

  it('reloads when the address changes, without a page reload', async () => {
    const { router } = await open('/projects')

    await router.push('/projects?technology=php')
    await flushPromises()

    expect(projectQueries()).toEqual([
      { page: 1, perPage: 12, technology: undefined },
      { page: 1, perPage: 12, technology: 'php' },
    ])
  })

  it('keeps the filter in page links and starts a new filter from the first page', async () => {
    const { wrapper } = await open('/projects?technology=php&page=2')

    const hrefs = wrapper.findAll('a').map((link) => link.attributes('href'))

    expect(hrefs).toContain('/projects?technology=php&page=3')
    expect(hrefs).toContain('/projects?technology=php')
    expect(hrefs).toContain('/projects')
    expect(hrefs).toContain('/projects/portfolio')
  })
})
