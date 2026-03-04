import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { configureApiClient } from './services/apiClient'
import { useAuthStore } from './stores/authStore'
import './style.css'

const app = createApp(App)
const pinia = createPinia()

configureApiClient({
  authStoreGetter: () => useAuthStore(pinia),
  tokenGetter: () => useAuthStore(pinia).token,
  unauthorizedHandler: () => {
    if (router.currentRoute.value.path !== '/login') {
      void router.push('/login')
    }
  },
})

app.use(pinia)
app.use(router)
app.mount('#app')
