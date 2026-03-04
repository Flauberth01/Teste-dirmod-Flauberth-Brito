<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FieldError from '../components/FieldError.vue'
import { useAuthStore } from '../stores/authStore'
import { normalizeApiError, firstError } from '../utils/errorMapper'
import { hasFieldErrors, validateLoginForm } from '../utils/validators'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

const loading = ref(false)
const alertMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  email: '',
  password: '',
})

async function onSubmit() {
  alertMessage.value = ''
  fieldErrors.value = validateLoginForm(form)

  if (hasFieldErrors(fieldErrors.value)) {
    return
  }

  loading.value = true

  try {
    await authStore.login(form)
    const redirect = route.query.redirect ? String(route.query.redirect) : '/expenses'
    await router.push(redirect)
  } catch (error) {
    const parsedError = normalizeApiError(error)
    fieldErrors.value = parsedError.errors
    alertMessage.value = parsedError.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="panel">
    <h1>Entrar</h1>

    <p v-if="alertMessage" role="alert" class="alert">{{ alertMessage }}</p>

    <form class="form" @submit.prevent="onSubmit" novalidate>
      <label for="email">E-mail</label>
      <input id="email" v-model="form.email" type="email" autocomplete="email" required />
      <FieldError :message="firstError(fieldErrors, 'email')" />

      <label for="password">Senha</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        autocomplete="current-password"
        required
      />
      <FieldError :message="firstError(fieldErrors, 'password')" />

      <button type="submit" :disabled="loading">
        {{ loading ? 'Entrando...' : 'Entrar' }}
      </button>
    </form>
  </section>
</template>
