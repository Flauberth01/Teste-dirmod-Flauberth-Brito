<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import FieldError from '../components/FieldError.vue'
import { createExpense } from '../services/expenseService'
import { normalizeApiError, firstError } from '../utils/errorMapper'
import { validateExpenseField } from '../utils/validators'

const router = useRouter()

const loading = ref(false)
const alertMessage = ref('')
const infoMessage = ref('')
const fieldErrors = ref({})

const form = reactive({
  amount_original: '',
  currency: 'USD',
})

const fieldState = reactive({
  amount_original: {
    touched: false,
    dirty: false,
  },
  currency: {
    touched: false,
    dirty: false,
  },
})

function setFieldError(field, message) {
  if (!message) {
    const next = { ...fieldErrors.value }
    delete next[field]
    fieldErrors.value = next
    return
  }

  fieldErrors.value = {
    ...fieldErrors.value,
    [field]: [message],
  }
}

function validateField(field, options = {}) {
  if (!options.force && !fieldState[field].dirty && !fieldState[field].touched) {
    return true
  }

  const message = validateExpenseField(field, form)
  setFieldError(field, message)

  return !message
}

function normalizeAmountInput(value) {
  const sanitized = String(value ?? '')
    .replace(',', '.')
    .replace(/[^0-9.]/g, '')

  const [integerPart, ...decimalParts] = sanitized.split('.')

  if (decimalParts.length === 0) {
    return integerPart
  }

  return `${integerPart}.${decimalParts.join('').slice(0, 2)}`
}

function onAmountInput() {
  fieldState.amount_original.dirty = true
  form.amount_original = normalizeAmountInput(form.amount_original)
  validateField('amount_original')
}

function onCurrencyInput() {
  fieldState.currency.dirty = true
  form.currency = String(form.currency ?? '')
    .toUpperCase()
    .replace(/[^A-Z]/g, '')
    .slice(0, 3)

  validateField('currency')
}

function onFieldBlur(field) {
  fieldState[field].touched = true
  validateField(field, { force: true })
}

function validateBeforeSubmit() {
  fieldState.amount_original.touched = true
  fieldState.amount_original.dirty = true
  fieldState.currency.touched = true
  fieldState.currency.dirty = true

  const amountValid = validateField('amount_original', { force: true })
  const currencyValid = validateField('currency', { force: true })

  return amountValid && currencyValid
}

async function onSubmit() {
  alertMessage.value = ''
  infoMessage.value = ''

  if (!validateBeforeSubmit()) {
    return
  }

  loading.value = true

  try {
    const response = await createExpense({
      amount_original: form.amount_original.replace(',', '.'),
      currency: form.currency.toUpperCase(),
    })

    const expense = response.data?.data ?? {}

    if (expense.status === 'pending') {
      infoMessage.value = 'Despesa salva como pendente devido à indisponibilidade da API de câmbio.'
    }

    await router.push(`/expenses/${expense.id}`)
  } catch (error) {
    const parsedError = normalizeApiError(error)
    fieldErrors.value = parsedError.errors
    alertMessage.value = parsedError.displayMessage
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="panel">
    <h1>Nova despesa</h1>

    <p v-if="alertMessage" role="alert" class="alert">{{ alertMessage }}</p>
    <p v-if="infoMessage" class="info">{{ infoMessage }}</p>

    <form class="form" @submit.prevent="onSubmit" novalidate>
      <label for="amount_original">Valor original</label>
      <input
        id="amount_original"
        v-model="form.amount_original"
        type="text"
        inputmode="decimal"
        placeholder="Ex.: 100.00"
        required
        @input="onAmountInput"
        @blur="onFieldBlur('amount_original')"
      />
      <FieldError :message="firstError(fieldErrors, 'amount_original')" />

      <label for="currency">Moeda</label>
      <input
        id="currency"
        v-model="form.currency"
        type="text"
        maxlength="3"
        required
        @input="onCurrencyInput"
        @blur="onFieldBlur('currency')"
      />
      <FieldError :message="firstError(fieldErrors, 'currency')" />

      <button type="submit" :disabled="loading">
        {{ loading ? 'Salvando...' : 'Salvar despesa' }}
      </button>
    </form>
  </section>
</template>
