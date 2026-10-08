import type { Schemas } from './client'

export type ProblemDetails = Schemas['ProblemDetails']

/**
 * An error response of the API (application/problem+json, RFC 9457).
 */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly problem: ProblemDetails | null,
  ) {
    super(problem?.detail ?? problem?.title ?? `HTTP ${status}`)
    this.name = 'ApiError'
  }

  /** First violation message per field, e.g. { email: 'This value is not a valid email address.' } */
  get fieldErrors(): Record<string, string> {
    const errors: Record<string, string> = {}
    for (const violation of this.problem?.violations ?? []) {
      errors[violation.propertyPath] ??= violation.message
    }
    return errors
  }
}

/** The session is over (refresh token missing or expired); the user has to sign in again. */
export class SessionExpiredError extends Error {
  constructor() {
    super('Session expired')
    this.name = 'SessionExpiredError'
  }
}

export interface FetchResult<T> {
  data?: T
  error?: unknown
  response: Response
}

/** Returns the body of a successful response or throws ApiError. */
export function unwrap<T>(result: FetchResult<T>): T {
  if (result.response.ok) {
    return result.data as T
  }

  throw new ApiError(result.response.status, isProblem(result.error) ? result.error : null)
}

function isProblem(value: unknown): value is ProblemDetails {
  return typeof value === 'object' && value !== null && 'status' in value && 'title' in value
}
