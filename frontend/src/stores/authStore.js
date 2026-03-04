import { defineStore } from 'pinia'
import {
  fetchAuthenticatedUser,
  loginUser,
  logoutUser,
  registerUser,
} from '../services/authService'

function readPersistedUser() {
  if (typeof window === 'undefined') {
    return null
  }

  const raw = window.localStorage.getItem('auth_user')

  if (!raw) {
    return null
  }

  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function readPersistedToken() {
  if (typeof window === 'undefined') {
    return null
  }

  return window.localStorage.getItem('auth_token')
}

function normalizeUserFromResponse(data) {
  return data?.data?.user ?? data?.user ?? data?.data ?? null
}

function normalizeTokenFromResponse(data) {
  return data?.data?.token ?? data?.token ?? null
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: readPersistedUser(),
    token: readPersistedToken(),
    isLoading: false,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),
  },

  actions: {
    setSession(user, token) {
      this.user = user
      this.token = token

      if (typeof window !== 'undefined') {
        if (token) {
          window.localStorage.setItem('auth_token', token)
        }

        if (user) {
          window.localStorage.setItem('auth_user', JSON.stringify(user))
        }
      }
    },

    clearSession() {
      this.user = null
      this.token = null

      if (typeof window !== 'undefined') {
        window.localStorage.removeItem('auth_token')
        window.localStorage.removeItem('auth_user')
      }
    },

    async register(payload) {
      const response = await registerUser(payload)
      const user = normalizeUserFromResponse(response.data)
      const token = normalizeTokenFromResponse(response.data)

      this.setSession(user, token)

      return { user, token }
    },

    async login(payload) {
      const response = await loginUser(payload)
      const user = normalizeUserFromResponse(response.data)
      const token = normalizeTokenFromResponse(response.data)

      this.setSession(user, token)

      return { user, token }
    },

    async logout() {
      this.isLoading = true

      try {
        await logoutUser()
      } finally {
        this.clearSession()
        this.isLoading = false
      }
    },

    async fetchMe(options = {}) {
      const allowWithoutToken = options.allowWithoutToken ?? false

      if (!allowWithoutToken && !this.token) {
        return false
      }

      this.isLoading = true

      try {
        const response = await fetchAuthenticatedUser()
        const user = normalizeUserFromResponse(response.data)

        if (!user) {
          this.clearSession()

          return false
        }

        this.user = user

        if (typeof window !== 'undefined') {
          window.localStorage.setItem('auth_user', JSON.stringify(user))
        }

        return true
      } catch {
        this.clearSession()

        return false
      } finally {
        this.isLoading = false
      }
    },
  },
})
