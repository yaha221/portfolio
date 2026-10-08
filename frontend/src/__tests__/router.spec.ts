import { createPinia, setActivePinia } from 'pinia'
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'

import { api } from '@/api/client'
import { trackPageView } from '@/api/pageViews'
import router from '@/router'

import { okResult, problemResult } from './fixtures'

vi.mock('@/api/client', () => ({
  api: { POST: vi.fn<() => Promise<unknown>>(), GET: vi.fn<() => Promise<unknown>>() },
}))
vi.mock('@/api/pageViews', () => ({ trackPageView: vi.fn<(path: string) => void>() }))

const post = vi.mocked(api.POST)
const track = vi.mocked(trackPageView)

beforeAll(() => {
  // jsdom has no layout: scrollBehavior's scrolling would only print "not implemented"
  vi.spyOn(window, 'scrollTo').mockImplementation(() => {})
})

beforeEach(() => {
  setActivePinia(createPinia())
  post.mockReset()
  track.mockReset()
})

describe('router', () => {
  it('sends a visitor without a session to the login page, remembering the address', async () => {
    post.mockResolvedValue(problemResult(401))

    await router.push('/admin/messages?page=2')

    expect(router.currentRoute.value.name).toBe('admin-login')
    expect(router.currentRoute.value.query.redirect).toBe('/admin/messages?page=2')
  })

  it('opens admin pages after a reload while the refresh cookie still works', async () => {
    post.mockResolvedValue(okResult({ token: 'access', expiresIn: 900 }))

    await router.push('/admin/skills')

    expect(router.currentRoute.value.name).toBe('admin-skills')
    expect(post).toHaveBeenCalledWith('/api/auth/refresh')
  })

  it('names the browser tab after the page', async () => {
    await router.push('/about')

    expect(document.title).toBe('Обо мне — Антон Степанюк')
  })

  it('counts views of public pages, but not admin pages or a new filter on the same page', async () => {
    post.mockResolvedValue(okResult({ token: 'access', expiresIn: 900 }))

    await router.push('/admin/projects')
    await router.push('/projects?technology=php')
    await router.push('/projects?technology=vuedotjs')
    await router.push('/projects?technology=vuedotjs&page=2')
    await router.push('/contact')

    expect(track.mock.calls).toEqual([['/projects'], ['/contact']])
  })
})
