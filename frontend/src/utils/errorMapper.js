export function normalizeApiError(error) {
  const message = error?.message ?? 'Erro ao processar'
  const errors = error?.code === 'VALIDATION_ERROR' ? (error.errors ?? {}) : {}

  return {
    message,
    errors,
  }
}

export function firstError(errors, field) {
  const messages = errors?.[field]

  if (!messages || messages.length === 0) {
    return ''
  }

  return messages[0]
}
