import { flushPromises, mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'

import { api } from '@/api/client'
import LoginView from '@/views/admin/LoginView.vue'

import { okResult, problemResult } from './fixtures'

vi.mock('@/api/client', () => ({ api: { POST: vi.fn<(path: string) => Promise<unknown>>() } }))

const post = vi.mocked(api.POST)

function respond(refresh: unknown, login: unknown = okResult({ token: 'access', expiresIn: 900 })) {
  post.mockImplementation(((path: string) =>
    Promise.resolve(path === '/api/auth/refresh' ? refresh : login)) as never)
}

async function open(url: string) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/admin/login', component: LoginView },
      { path: '/:pathMatch(.*)*', component: { render: () => null } },
    ],
  })
  await router.push(url)
  await router.isReady()
  const wrapper = mount(LoginView, { global: { plugins: [createPinia(), router] } })
  await flushPromises()
  return { wrapper, router }
}

async function signIn(wrapper: Awaited<ReturnType<typeof open>>['wrapper']) {
  await wrapper.find('#login-email').setValue('admin@portfolio.local')
  await wrapper.find('#login-password').setValue('secret')
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

beforeEach(() => {
  post.mockReset()
})

describe('LoginView', () => {
  it('returns to the admin page the user wanted to open', async () => {
    respond(problemResult(401))
    const { wrapper, router } = await open('/admin/login?redirect=/admin/messages')

    await signIn(wrapper)

    expect(post).toHaveBeenCalledWith('/api/auth/login', {
      body: { email: 'admin@portfolio.local', password: 'secret' },
    })
    expect(router.currentRoute.value.fullPath).toBe('/admin/messages')
  })

  it.each(['https://evil.example/admin', '//evil.example', '/projects'])(
    'ignores a redirect outside the admin: %s',
    async (redirect) => {
      respond(problemResult(401))
      const { wrapper, router } = await open(
        `/admin/login?redirect=${encodeURIComponent(redirect)}`,
      )

      await signIn(wrapper)

      expect(router.currentRoute.value.fullPath).toBe('/admin')
    },
  )

  it('says when the password is wrong', async () => {
    respond(problemResult(401), problemResult(401, { detail: 'Invalid credentials.' }))
    const { wrapper, router } = await open('/admin/login')

    await signIn(wrapper)

    expect(wrapper.find('[role="alert"]').text()).toBe('Неверный email или пароль.')
    expect(router.currentRoute.value.path).toBe('/admin/login')
  })

  it('says when there were too many attempts', async () => {
    respond(problemResult(401), problemResult(429))
    const { wrapper } = await open('/admin/login')

    await signIn(wrapper)

    expect(wrapper.find('[role="alert"]').text()).toBe('Слишком много попыток. Подождите 15 минут.')
  })

  it('skips the form while the session is still alive', async () => {
    respond(okResult({ token: 'access', expiresIn: 900 }))

    const { router } = await open('/admin/login?redirect=/admin/skills')

    expect(router.currentRoute.value.fullPath).toBe('/admin/skills')
    expect(post).not.toHaveBeenCalledWith('/api/auth/login', expect.anything())
  })
})
