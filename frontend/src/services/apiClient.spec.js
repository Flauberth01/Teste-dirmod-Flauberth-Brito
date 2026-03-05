import MockAdapter from 'axios-mock-adapter'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import apiClient, { configureApiClient } from './apiClient'

describe('apiClient interceptors', () => {
  let mock
  let authStore
  let redirectSpy

  beforeEach(() => {
    mock = new MockAdapter(apiClient)
    authStore = { clearSession: vi.fn() }
    redirectSpy = vi.fn()

    configureApiClient({
      authStoreGetter: () => authStore,
      unauthorizedHandler: redirectSpy,
      tokenGetter: () => 'token-123',
    })
  })

  afterEach(() => {
    mock.restore()
  })

  it('returns 422 errors preserving field-level payload', async () => {
    mock.onPost('/validation').reply(422, {
      message: 'Validation error',
      errors: { email: ['E-mail obrigatório'] },
      code: 'VALIDATION_ERROR',
      request_id: 'req-422',
    })

    await expect(apiClient.post('/validation')).rejects.toEqual({
      message: 'Validation error',
      errors: { email: ['E-mail obrigatório'] },
      code: 'VALIDATION_ERROR',
      status: 422,
      request_id: 'req-422',
    })
  })

  it('clears session and redirects on 401', async () => {
    mock.onGet('/protected').reply(401, {
      message: 'Unauthenticated',
      errors: null,
      code: 'UNAUTHENTICATED',
      request_id: 'req-401',
    })

    await expect(apiClient.get('/protected')).rejects.toEqual({
      message: 'Unauthenticated',
      errors: null,
      code: 'UNAUTHENTICATED',
      status: 401,
      request_id: 'req-401',
    })

    expect(authStore.clearSession).toHaveBeenCalledTimes(1)
    expect(redirectSpy).toHaveBeenCalledTimes(1)
  })

  it('preserves backend payload for non-401/422 errors', async () => {
    mock.onGet('/boom').reply(500, {
      message: 'Database unavailable',
      errors: null,
      code: 'DATABASE_UNAVAILABLE',
      request_id: 'req-500',
    })

    await expect(apiClient.get('/boom')).rejects.toEqual({
      message: 'Database unavailable',
      errors: null,
      code: 'DATABASE_UNAVAILABLE',
      status: 500,
      request_id: 'req-500',
    })
  })
})
