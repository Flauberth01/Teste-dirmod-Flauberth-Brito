import apiClient from './apiClient'

export function listExpenses() {
  return apiClient.get('/expenses')
}

export function getExpense(id) {
  return apiClient.get(`/expenses/${id}`)
}

export function createExpense(payload) {
  return apiClient.post('/expenses', payload)
}

export function retryExpenseConversion(id) {
  return apiClient.post(`/expenses/${id}/retry-conversion`)
}
