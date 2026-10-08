import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { api } from '@/api/client'
import { unwrap } from '@/api/problem'

// Renew a little before expiry, so a request never leaves with a token that dies on the way
const EXPIRY_MARGIN_MS = 30_000

/**
 * Admin session. The access token lives only in memory; the refresh token is an httpOnly cookie
 * the browser sends to /api/auth itself, so page scripts never see it.
 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const expiresAt = ref(0)
  let pendingRefresh: Promise<boolean> | null = null

  const isAuthenticated = computed(() => token.value !== null)

  function setToken(data: { token: string; expiresIn: number }): void {
    token.value = data.token
    expiresAt.value = Date.now() + data.expiresIn * 1000
  }

  function clear(): void {
    token.value = null
    expiresAt.value = 0
  }

  async function login(email: string, password: string): Promise<void> {
    setToken(unwrap(await api.POST('/api/auth/login', { body: { email, password } })))
  }

  /**
   * Exchanges the refresh cookie for a new access token. Concurrent callers share one request:
   * the refresh token is single-use, a second parallel refresh would fail and end the session.
   */
  function refresh(): Promise<boolean> {
    pendingRefresh ??= (async () => {
      try {
        const { data, response } = await api.POST('/api/auth/refresh')
        if (response.ok && data) {
          setToken(data)
          return true
        }
      } catch {
        // Network error: treat as no session
      }
      clear()
      return false
    })().finally(() => {
      pendingRefresh = null
    })

    return pendingRefresh
  }

  /** A token valid for at least the next 30 s, refreshed if needed; null when there is no session. */
  async function validToken(): Promise<string | null> {
    if (token.value !== null && expiresAt.value - Date.now() > EXPIRY_MARGIN_MS) {
      return token.value
    }

    return (await refresh()) ? token.value : null
  }

  async function logout(): Promise<void> {
    try {
      await api.POST('/api/auth/logout')
    } finally {
      clear()
    }
  }

  return { token, isAuthenticated, login, refresh, validToken, logout, clear }
})
