import { describe, expect, it } from 'vitest'

import { useAsync } from '@/composables/useAsync'

import { deferred } from './fixtures'

describe('useAsync', () => {
  it('is loading while the request is in flight', async () => {
    const request = deferred<string>()
    const { data, loading, run } = useAsync(() => request.promise)

    const running = run()
    expect(loading.value).toBe(true)

    request.resolve('done')
    await running
    expect(loading.value).toBe(false)
    expect(data.value).toBe('done')
  })

  it('keeps the result of the latest run when an earlier one finishes last', async () => {
    // Page 1 is slow, the user already went to page 2: page 1 must not overwrite it
    const slow = deferred<string>()
    const fast = deferred<string>()
    let call = 0
    const { data, loading, run } = useAsync(() => (call++ === 0 ? slow : fast).promise)

    const first = run()
    const second = run()
    fast.resolve('page 2')
    await second
    slow.resolve('page 1')
    await first

    expect(data.value).toBe('page 2')
    expect(loading.value).toBe(false)
  })

  it('ignores the error of an outdated run', async () => {
    const slow = deferred<string>()
    const fast = deferred<string>()
    let call = 0
    const { data, error, run } = useAsync(() => (call++ === 0 ? slow : fast).promise)

    const first = run()
    const second = run()
    fast.resolve('fresh')
    await second
    slow.reject(new Error('stale failure'))
    await first

    expect(error.value).toBeNull()
    expect(data.value).toBe('fresh')
  })

  it('reports an error and clears it on the next run', async () => {
    let fail = true
    const { error, data, run } = useAsync(async () => {
      if (fail) throw new Error('offline')
      return 'back online'
    })

    await run()
    expect(error.value).toEqual(new Error('offline'))

    fail = false
    await run()
    expect(error.value).toBeNull()
    expect(data.value).toBe('back online')
  })
})
