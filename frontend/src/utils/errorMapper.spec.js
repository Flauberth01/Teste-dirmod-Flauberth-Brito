import { describe, expect, it } from 'vitest'
import { normalizeApiError } from './errorMapper'

describe('normalizeApiError', () => {
  it('adds request id to display message for 5xx errors', () => {
    const parsed = normalizeApiError({
      message: 'Database unavailable',
      code: 'DATABASE_UNAVAILABLE',
      status: 503,
      request_id: 'req-123',
    })

    expect(parsed.displayMessage).toBe('Database unavailable (Referência: req-123)')
    expect(parsed.request_id).toBe('req-123')
  })

  it('does not add request id to display message for non-5xx errors', () => {
    const parsed = normalizeApiError({
      message: 'Validation error',
      code: 'VALIDATION_ERROR',
      status: 422,
      request_id: 'req-422',
      errors: { email: ['Inválido'] },
    })

    expect(parsed.displayMessage).toBe('Validation error')
    expect(parsed.errors).toEqual({ email: ['Inválido'] })
  })
})
