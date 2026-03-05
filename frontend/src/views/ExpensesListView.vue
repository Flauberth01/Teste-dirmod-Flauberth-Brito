<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { listExpenses } from '../services/expenseService'
import { normalizeApiError } from '../utils/errorMapper'

const loading = ref(false)
const alertMessage = ref('')
const expenses = ref([])

const filterStatus = ref('all')
const filterCurrency = ref('all')

const filteredExpenses = computed(() => {
  return expenses.value.filter((expense) => {
    const statusMatch = filterStatus.value === 'all' || expense.status === filterStatus.value
    const currencyMatch =
      filterCurrency.value === 'all' || expense.currency === filterCurrency.value.toUpperCase()

    return statusMatch && currencyMatch
  })
})

const currencies = computed(() => {
  const values = new Set(expenses.value.map((expense) => expense.currency))
  return Array.from(values)
})

async function loadExpenses() {
  loading.value = true
  alertMessage.value = ''

  try {
    const response = await listExpenses()
    expenses.value = response.data?.data ?? []
  } catch (error) {
    const parsedError = normalizeApiError(error)
    alertMessage.value = parsedError.displayMessage ?? 'Erro ao carregar despesas.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadExpenses()
})
</script>

<template>
  <section class="panel panel-wide">
    <div class="panel-head">
      <h1>Minhas despesas</h1>
      <RouterLink class="btn" to="/expenses/new">Nova despesa</RouterLink>
    </div>

    <p v-if="alertMessage" role="alert" class="alert">{{ alertMessage }}</p>

    <div class="filters">
      <label>
        Status
        <select v-model="filterStatus">
          <option value="all">Todos</option>
          <option value="converted">Convertidas</option>
          <option value="pending">Pendentes</option>
        </select>
      </label>

      <label>
        Moeda
        <select v-model="filterCurrency">
          <option value="all">Todas</option>
          <option v-for="currency in currencies" :key="currency" :value="currency">
            {{ currency }}
          </option>
        </select>
      </label>
    </div>

    <p v-if="loading">Carregando...</p>

    <p v-else-if="filteredExpenses.length === 0">Nenhuma despesa encontrada.</p>

    <table v-else class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Valor original</th>
          <th>Moeda</th>
          <th>Cotação</th>
          <th>Valor BRL</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="expense in filteredExpenses" :key="expense.id">
          <td>{{ expense.id }}</td>
          <td>{{ expense.amount_original }}</td>
          <td>{{ expense.currency }}</td>
          <td>{{ expense.exchange_rate ?? '-' }}</td>
          <td>{{ expense.amount_brl ?? '-' }}</td>
          <td>{{ expense.status }}</td>
          <td>
            <RouterLink :to="`/expenses/${expense.id}`">Detalhes</RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
