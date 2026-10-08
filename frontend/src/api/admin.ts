import { useAuthStore } from '@/stores/auth'

import { type FetchResult, SessionExpiredError, unwrap } from './problem'

// A type alias, not an interface: openapi-fetch's init type has an index signature interfaces can't satisfy
export type AuthHeaders = {
  headers: { Authorization: string }
}

let onSessionExpired: () => void = () => {}

/** Called once from main.ts: where to send the user when the session is over. */
export function setSessionExpiredHandler(handler: () => void): void {
  onSessionExpired = handler
}

/**
 * Runs an admin API call with a valid access token. On 401 (token revoked or expired in flight)
 * it refreshes once and repeats the call; without a session it redirects to the login page.
 *
 * Usage: adminCall((auth) => api.GET('/api/admin/projects', auth))
 */
export async function adminCall<T>(
  call: (auth: AuthHeaders) => Promise<FetchResult<T>>,
): Promise<T> {
  const auth = useAuthStore()

  let token = await auth.validToken()
  if (token === null) {
    return expired()
  }

  let result = await call(bearer(token))
  if (result.response.status === 401) {
    token = (await auth.refresh()) ? auth.token : null
    if (token === null) {
      return expired()
    }
    result = await call(bearer(token))
  }

  return unwrap(result)
}

function bearer(token: string): AuthHeaders {
  return { headers: { Authorization: `Bearer ${token}` } }
}

function expired(): never {
  onSessionExpired()
  throw new SessionExpiredError()
}
