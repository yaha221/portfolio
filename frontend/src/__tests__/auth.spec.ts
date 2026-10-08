import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { type AuthHeaders, setSessionExpiredHandler, adminCall } from '@/api/admin'
import { api } from '@/api/client'
import { type FetchResult, SessionExpiredError } from '@/api/problem'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/client', () => ({ api: { POST: vi.fn<() => Promise<unknown>>() } }))

type AdminRequest = (auth: AuthHeaders) => Promise<FetchResult<unknown>>

const post = vi.mocked(api.POST)

function tokenResponse(token: string, expiresIn = 900) {
  return { data: { token, expiresIn }, response: new Response(null, { status: 200 }) } as never
}

function unauthorized() {
  return {
    error: { title: 'Unauthorized', status: 401 },
    response: new Response(null, { status: 401 }),
  } as never
}

beforeEach(() => {
  setActivePinia(createPinia())
  post.mockReset()
})

afterEach(() => {
  vi.useRealTimers()
})

describe('auth store', () => {
  it('shares one refresh request between concurrent callers', async () => {
    // The refresh token is single-use: a second parallel refresh would end the session
    post.mockResolvedValue(tokenResponse('t1'))
    const auth = useAuthStore()

    const tokens = await Promise.all([auth.validToken(), auth.validToken(), auth.validToken()])

    expect(tokens).toEqual(['t1', 't1', 't1'])
    expect(post).toHaveBeenCalledTimes(1)
  })

  it('reuses the token until 30 seconds before it expires', async () => {
    vi.useFakeTimers()
    post
      .mockResolvedValueOnce(tokenResponse('t1', 900))
      .mockResolvedValueOnce(tokenResponse('t2', 900))
    const auth = useAuthStore()
    await auth.validToken()

    vi.advanceTimersByTime(860_000)
    expect(await auth.validToken()).toBe('t1')

    vi.advanceTimersByTime(20_000)
    expect(await auth.validToken()).toBe('t2')
    expect(post).toHaveBeenCalledTimes(2)
  })

  it('ends the session when the refresh cookie is no longer accepted', async () => {
    post.mockResolvedValue(unauthorized())
    const auth = useAuthStore()

    expect(await auth.validToken()).toBeNull()
    expect(auth.isAuthenticated).toBe(false)
  })
})

describe('adminCall', () => {
  it('refreshes once and repeats the call after a 401', async () => {
    post.mockResolvedValueOnce(tokenResponse('t1')).mockResolvedValueOnce(tokenResponse('t2'))
    const call = vi
      .fn<AdminRequest>()
      .mockResolvedValueOnce({ response: new Response(null, { status: 401 }) })
      .mockResolvedValueOnce({ data: { items: [] }, response: new Response(null, { status: 200 }) })

    const result = await adminCall(call)

    expect(result).toEqual({ items: [] })
    expect(call).toHaveBeenNthCalledWith(1, { headers: { Authorization: 'Bearer t1' } })
    expect(call).toHaveBeenNthCalledWith(2, { headers: { Authorization: 'Bearer t2' } })
  })

  it('sends the user to the login page when there is no session', async () => {
    post.mockResolvedValue(unauthorized())
    const onExpired = vi.fn<() => void>()
    setSessionExpiredHandler(onExpired)
    const call = vi.fn<AdminRequest>()

    await expect(adminCall(call)).rejects.toBeInstanceOf(SessionExpiredError)
    expect(onExpired).toHaveBeenCalledOnce()
    expect(call).not.toHaveBeenCalled()
  })
})
