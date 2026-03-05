export function normalizeApiError(error) {
  const message = error?.message ?? 'Erro ao processar'
  const errors = error?.code === 'VALIDATION_ERROR' ? (error.errors ?? {}) : {}
  const code = error?.code ?? 'GENERIC_ERROR'
  const status = error?.status ?? null
  const requestId = error?.request_id ?? null

  const displayMessage =
    typeof status === 'number' && status >= 500 && requestId
      ? `${message} (Referência: ${requestId})`
      : message

  return {
    message,
    displayMessage,
    errors,
    code,
    status,
    request_id: requestId,
  }
}

export function firstError(errors, field) {
  const messages = errors?.[field]

  if (!messages || messages.length === 0) {
    return ''
  }

  return messages[0]
}
