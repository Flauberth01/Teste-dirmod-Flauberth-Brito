<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import FieldError from '../components/FieldError.vue'
import { useRegisterRealtimeValidation } from '../composables/useRegisterRealtimeValidation'
import { useAuthStore } from '../stores/authStore'
import { normalizeApiError, firstError } from '../utils/errorMapper'
import { toDigits } from '../utils/validators'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const alertMessage = ref('')
const infoMessage = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  cpf: '',
  cep: '',
  street: '',
  neighborhood: '',
  city: '',
  state: '',
})

const {
  fieldErrors,
  asyncFeedback,
  cepNotice,
  isValidatingCritical,
  canShowSubmitButton,
  onFieldInput,
  onFieldBlur,
  onCpfInput,
  onCpfBlur,
  onCepInput,
  onCepBlur,
  applyServerErrors,
  validateBeforeSubmit,
} = useRegisterRealtimeValidation(form)

async function onSubmit() {
  alertMessage.value = ''
  infoMessage.value = ''

  const canSubmit = await validateBeforeSubmit()
  if (!canSubmit) {
    return
  }

  loading.value = true

  try {
    const payload = {
      ...form,
      cpf: toDigits(form.cpf),
      cep: toDigits(form.cep),
    }

    const response = await authStore.register(payload)

    if (response.user?.address_status === 'pending') {
      infoMessage.value = 'Cadastro salvo com endereço pendente devido à indisponibilidade da API externa.'
    }

    await router.push('/expenses')
  } catch (error) {
    const parsedError = normalizeApiError(error)
    applyServerErrors(parsedError.errors)
    alertMessage.value = Object.keys(parsedError.errors).length === 0 ? parsedError.displayMessage : ''
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="panel">
    <h1>Criar conta</h1>

    <p v-if="alertMessage" role="alert" class="alert">{{ alertMessage }}</p>
    <p v-if="infoMessage" class="info">{{ infoMessage }}</p>

    <form class="form" @submit.prevent="onSubmit" novalidate>
      <label for="name">Nome</label>
      <input id="name" v-model="form.name" type="text" required @input="onFieldInput('name')" @blur="onFieldBlur('name')" />
      <FieldError :message="firstError(fieldErrors, 'name')" />

      <label for="email">E-mail</label>
      <input
        id="email"
        v-model="form.email"
        type="email"
        required
        autocomplete="email"
        @input="onFieldInput('email')"
        @blur="onFieldBlur('email')"
      />
      <FieldError :message="firstError(fieldErrors, 'email')" />

      <label for="password">Senha</label>
      <input
        id="password"
        v-model="form.password"
        type="password"
        required
        autocomplete="new-password"
        @input="onFieldInput('password')"
        @blur="onFieldBlur('password')"
      />
      <FieldError :message="firstError(fieldErrors, 'password')" />

      <label for="password_confirmation">Confirmação de senha</label>
      <input
        id="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        required
        autocomplete="new-password"
        @input="onFieldInput('password_confirmation')"
        @blur="onFieldBlur('password_confirmation')"
      />
      <FieldError :message="firstError(fieldErrors, 'password_confirmation')" />

      <label for="cpf">CPF</label>
      <input id="cpf" v-model="form.cpf" type="text" inputmode="numeric" required @input="onCpfInput" @blur="onCpfBlur" />
      <small v-if="asyncFeedback.cpf" class="hint">{{ asyncFeedback.cpf }}</small>
      <FieldError :message="firstError(fieldErrors, 'cpf')" />

      <label for="cep">CEP</label>
      <input
        id="cep"
        v-model="form.cep"
        type="text"
        inputmode="numeric"
        required
        @input="onCepInput"
        @blur="onCepBlur"
      />
      <small class="hint">Endereco e validado automaticamente ao preencher o CEP.</small>
      <small v-if="asyncFeedback.cep" class="hint">{{ asyncFeedback.cep }}</small>
      <small v-if="cepNotice" class="hint">{{ cepNotice }}</small>
      <FieldError :message="firstError(fieldErrors, 'cep')" />

      <label for="street">Rua</label>
      <input id="street" v-model="form.street" type="text" readonly />

      <label for="neighborhood">Bairro</label>
      <input id="neighborhood" v-model="form.neighborhood" type="text" readonly />

      <label for="city">Cidade</label>
      <input id="city" v-model="form.city" type="text" readonly />

      <label for="state">Estado</label>
      <input id="state" v-model="form.state" type="text" readonly />

      <button type="submit" :disabled="loading || isValidatingCritical || !canShowSubmitButton">
        {{ loading ? 'Salvando...' : 'Cadastrar' }}
      </button>
      <small v-if="!canShowSubmitButton" class="hint">Preencha os campos corretamente para liberar o cadastro.</small>
    </form>
  </section>
</template>
