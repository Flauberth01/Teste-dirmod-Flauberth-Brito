<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getExpense, retryExpenseConversion } from '../services/expenseService'
import { normalizeApiError } from '../utils/errorMapper'

const route = useRoute()

const loading = ref(false)
const retrying = ref(false)
const alertMessage = ref('')
const infoMessage = ref('')
const expense = ref(null)

function friendlyPendingReason(reason) {
  if (!reason) {
    return 'Conversão pendente por indisponibilidade temporária do serviço de câmbio.'
  }

  if (reason === 'External service unavailable') {
    return 'Conversão pendente por indisponibilidade temporária do serviço de câmbio.'
  }

  return reason
}

async function loadExpense() {
  loading.value = true
  alertMessage.value = ''

  try {
    const response = await getExpense(route.params.id)
    expense.value = response.data?.data ?? null
  } catch (error) {
    const parsedError = normalizeApiError(error)
    alertMessage.value = parsedError.displayMessage ?? 'Erro ao carregar despesa.'
  } finally {
    loading.value = false
  }
}

async function retryConversion() {
  if (!expense.value) {
    return
  }

  retrying.value = true
  alertMessage.value = ''
  infoMessage.value = ''

  try {
    const response = await retryExpenseConversion(expense.value.id)
    expense.value = response.data?.data ?? expense.value
    infoMessage.value = 'Conversão reprocessada com sucesso.'
  } catch (error) {
    const parsedError = normalizeApiError(error)
    alertMessage.value = parsedError.displayMessage ?? 'Erro ao reprocessar conversão.'
  } finally {
    retrying.value = false
  }
}

onMounted(() => {
  void loadExpense()
})
</script>

<template>
  <section class="panel panel-wide">
    <h1>Detalhes da despesa</h1>

    <p v-if="alertMessage" role="alert" class="alert">{{ alertMessage }}</p>
    <p v-if="infoMessage" class="info">{{ infoMessage }}</p>
    <p v-if="loading">Carregando...</p>

    <div v-if="expense && !loading" class="details-grid">
      <div>
        <span class="label">Valor original</span>
        <strong>{{ expense.amount_original }} {{ expense.currency }}</strong>
      </div>
      <div>
        <span class="label">Cotação</span>
        <strong>{{ expense.exchange_rate ?? '-' }}</strong>
      </div>
      <div>
        <span class="label">Valor BRL</span>
        <strong>{{ expense.amount_brl ?? '-' }}</strong>
      </div>
      <div>
        <span class="label">Status</span>
        <strong>{{ expense.status }}</strong>
      </div>
      <div v-if="expense.status === 'pending'">
        <span class="label">Motivo</span>
        <strong>{{ friendlyPendingReason(expense.failure_reason) }}</strong>
      </div>
    </div>

    <button
      v-if="expense?.status === 'pending'"
      type="button"
      :disabled="retrying"
      @click="retryConversion"
    >
      {{ retrying ? 'Reprocessando...' : 'Reprocessar conversão' }}
    </button>
  </section>
</template>
