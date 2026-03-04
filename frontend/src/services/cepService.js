import apiClient from './apiClient'

export function lookupCep(cep) {
  return apiClient.get(`/cep/${cep}`)
}
