import apiClient from './apiClient'

export function checkCpfAvailability(cpf) {
  return apiClient.get(`/validation/cpf/${cpf}`)
}
