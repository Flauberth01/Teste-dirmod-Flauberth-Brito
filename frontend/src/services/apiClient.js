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

    if (status === 401) {
      const authStore = authStoreGetter()
      authStore?.clearSession?.()
      unauthorizedHandler()

      return Promise.reject({
        message: 'Unauthenticated',
        errors: null,
        code: 'UNAUTHENTICATED',
      })
    }

    if (status === 422) {
      const payload = error?.response?.data ?? {}

      return Promise.reject({
        message: payload.message ?? 'Validation error',
        errors: payload.errors ?? null,
        code: payload.code ?? 'VALIDATION_ERROR',
      })
    }

    return Promise.reject({
      message: 'Erro ao processar',
      errors: null,
      code: 'GENERIC_ERROR',
    })
  },
)

export default apiClient
