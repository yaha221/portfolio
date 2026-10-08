import type { ProblemDetails } from '@/api/problem'

/** What openapi-fetch resolves to for a successful response. */
export function okResult<T>(data: T, status = 200) {
  return { data, response: new Response(null, { status }) } as never
}

/** What openapi-fetch resolves to for an application/problem+json response. */
export function problemResult(status: number, extra: Partial<ProblemDetails> = {}) {
  const problem: ProblemDetails = {
    type: 'about:blank',
    title: `HTTP ${status}`,
    status,
    detail: null,
    ...extra,
  }
  return { error: problem, response: new Response(null, { status }) } as never
}

/** A promise that the test settles by hand, to check what happens in between. */
export function deferred<T>() {
  let resolve: (value: T) => void = () => {}
  let reject: (reason: unknown) => void = () => {}
  const promise = new Promise<T>((onResolve, onReject) => {
    resolve = onResolve
    reject = onReject
  })
  return { promise, resolve, reject }
}
