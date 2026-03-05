import axios from 'axios'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let authStoreGetter = () => null
let unauthorizedHandler = () => {}
let tokenGetter = () => {
  if (typeof window === 'undefined') {
    return null
  }

  return window.localStorage.getItem('auth_token')
}

export function configureApiClient(options = {}) {
  authStoreGetter = options.authStoreGetter ?? authStoreGetter
  unauthorizedHandler = options.unauthorizedHandler ?? unauthorizedHandler
  tokenGetter = options.tokenGetter ?? tokenGetter
}

apiClient.interceptors.request.use((config) => {
  const token = tokenGetter()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const payload = error?.response?.data ?? {}
    const requestId = payload.request_id ?? error?.response?.headers?.['x-request-id'] ?? null

    if (status === 401) {
      const authStore = authStoreGetter()
      authStore?.clearSession?.()
      unauthorizedHandler()

      return Promise.reject({
        message: payload.message ?? 'Unauthenticated',
        errors: payload.errors ?? null,
        code: payload.code ?? 'UNAUTHENTICATED',
        status,
        request_id: requestId,
      })
    }

    if (status === 422) {
      return Promise.reject({
        message: payload.message ?? 'Validation error',
        errors: payload.errors ?? {},
        code: payload.code ?? 'VALIDATION_ERROR',
        status,
        request_id: requestId,
      })
    }

    if (status) {
      return Promise.reject({
        message: payload.message ?? 'Erro ao processar',
        errors: payload.errors ?? null,
        code: payload.code ?? 'GENERIC_ERROR',
        status,
        request_id: requestId,
      })
    }

    return Promise.reject({
      message: 'Falha de conexão com o servidor',
      errors: null,
      code: 'NETWORK_ERROR',
      status: null,
      request_id: null,
    })
  },
)

export default apiClient
