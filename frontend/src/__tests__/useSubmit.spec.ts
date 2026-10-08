import { describe, expect, it } from 'vitest'

import { ApiError, SessionExpiredError } from '@/api/problem'
import { useSubmit } from '@/composables/useSubmit'

import { deferred } from './fixtures'

function validationError() {
  return new ApiError(422, {
    type: 'about:blank',
    title: 'Unprocessable Content',
    status: 422,
    detail: 'The request contains invalid parameters.',
    violations: [{ propertyPath: 'slug', message: 'This slug is already used.' }],
  })
}

describe('useSubmit', () => {
  it('returns the result and is busy only while the action runs', async () => {
    const action = deferred<number>()
    const { busy, failure, submit } = useSubmit()

    const submitting = submit(() => action.promise)
    expect(busy.value).toBe(true)
    action.resolve(42)

    expect(await submitting).toBe(42)
    expect(busy.value).toBe(false)
    expect(failure.value).toBeNull()
  })

  it('puts 422 violations next to their fields', async () => {
    const { errors, failure, submit } = useSubmit()

    const result = await submit(() => Promise.reject(validationError()))

    expect(result).toBeUndefined()
    expect(errors.value).toEqual({ slug: 'This slug is already used.' })
    expect(failure.value).toBe('Исправьте отмеченные поля.')
  })

  it('shows the message of any other API error', async () => {
    const { errors, failure, submit } = useSubmit()

    await submit(() =>
      Promise.reject(
        new ApiError(409, {
          type: 'about:blank',
          title: 'Conflict',
          status: 409,
          detail: 'Already exists.',
        }),
      ),
    )

    expect(errors.value).toEqual({})
    expect(failure.value).toBe('Already exists.')
  })

  it('explains a network failure in plain words', async () => {
    const { failure, submit } = useSubmit()

    await submit(() => Promise.reject(new TypeError('Failed to fetch')))

    expect(failure.value).toBe('Не получилось. Проверьте соединение и попробуйте ещё раз.')
  })

  it('stays quiet when the session ended: the user is already on the login page', async () => {
    const { failure, busy, submit } = useSubmit()

    await submit(() => Promise.reject(new SessionExpiredError()))

    expect(failure.value).toBeNull()
    expect(busy.value).toBe(false)
  })

  it('clears the errors of the previous attempt', async () => {
    const { errors, failure, submit } = useSubmit()
    await submit(() => Promise.reject(validationError()))

    await submit(() => Promise.resolve('saved'))

    expect(errors.value).toEqual({})
    expect(failure.value).toBeNull()
  })
})
