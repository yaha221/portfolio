import { describe, expect, it } from 'vitest'

import { ApiError, unwrap } from '@/api/problem'

describe('ApiError.fieldErrors', () => {
  it('keeps the first message per field', () => {
    const error = new ApiError(422, {
      type: 'about:blank',
      title: 'Unprocessable Content',
      status: 422,
      detail: null,
      violations: [
        { propertyPath: 'email', message: 'Not an email.' },
        { propertyPath: 'email', message: 'Too long.' },
        { propertyPath: 'name', message: 'Required.' },
      ],
    })

    expect(error.fieldErrors).toEqual({ email: 'Not an email.', name: 'Required.' })
  })
})

describe('unwrap', () => {
  it('returns the data of a successful response', () => {
    expect(unwrap({ data: { ok: true }, response: new Response(null, { status: 200 }) })).toEqual({
      ok: true,
    })
  })

  it('throws ApiError carrying the problem details', () => {
    const problem = {
      type: 'about:blank',
      title: 'Not Found',
      status: 404,
      detail: 'Project "x" not found.',
    }

    expect(() =>
      unwrap({ error: problem, response: new Response(null, { status: 404 }) }),
    ).toThrowError(expect.objectContaining({ status: 404, message: 'Project "x" not found.' }))
  })
})
