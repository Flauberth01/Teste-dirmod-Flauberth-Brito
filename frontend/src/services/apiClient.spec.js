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
    })

    await expect(apiClient.post('/validation')).rejects.toEqual({
      message: 'Validation error',
      errors: { email: ['E-mail obrigatório'] },
      code: 'VALIDATION_ERROR',
    })
  })

  it('clears session and redirects on 401', async () => {
    mock.onGet('/protected').reply(401)

    await expect(apiClient.get('/protected')).rejects.toEqual({
      message: 'Unauthenticated',
      errors: null,
      code: 'UNAUTHENTICATED',
    })

    expect(authStore.clearSession).toHaveBeenCalledTimes(1)
    expect(redirectSpy).toHaveBeenCalledTimes(1)
  })

  it('normalizes generic errors', async () => {
    mock.onGet('/boom').reply(500, {
      message: 'Internal server error',
    })

    await expect(apiClient.get('/boom')).rejects.toEqual({
      message: 'Erro ao processar',
      errors: null,
      code: 'GENERIC_ERROR',
    })
  })
})
