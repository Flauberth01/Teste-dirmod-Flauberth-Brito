import { computed, getCurrentInstance, onUnmounted, reactive, ref } from 'vue'
import { lookupCep } from '../services/cepService'
import { checkCpfAvailability } from '../services/validationService'
import { normalizeApiError } from '../utils/errorMapper'
import {
  formatCep,
  formatCpf,
  isValidCpfAlgorithm,
  unformatCep,
  unformatCpf,
  validateRegisterField,
} from '../utils/validators'

const ASYNC_DEBOUNCE_MS = 350

function createFieldValidationState() {
  return {
    touched: false,
    dirty: false,
    validating: false,
    asyncChecked: false,
    error: '',
  }
}

export function useRegisterRealtimeValidation(form) {
  const fieldStates = reactive({
    name: createFieldValidationState(),
    email: createFieldValidationState(),
    password: createFieldValidationState(),
    password_confirmation: createFieldValidationState(),
    cpf: createFieldValidationState(),
    cep: createFieldValidationState(),
  })

  const fieldList = Object.keys(fieldStates)
  const serverErrors = ref({})
  const asyncFeedback = reactive({
    cpf: '',
    cep: '',
  })
  const stickyAsyncErrors = reactive({
    cpf: '',
  })
  const cepNotice = ref('')
  const cpfAvailability = ref({
    valid: false,
    available: false,
    reason: null,
  })
  const timerState = reactive({
    cpf: false,
    cep: false,
  })

  let cpfDebounceTimer = null
  let cepDebounceTimer = null
  let cpfRequestVersion = 0
  let cepRequestVersion = 0
  let lastCpfChecked = ''
  let lastCepChecked = ''
  let lastCpfInputValue = ''

  function clearAddressFields() {
    form.street = ''
    form.neighborhood = ''
    form.city = ''
    form.state = ''
  }

  function clearServerError(field) {
    if (!serverErrors.value[field]) {
      return
    }

    const next = { ...serverErrors.value }
    delete next[field]
    serverErrors.value = next
  }

  function markDirty(field) {
    fieldStates[field].dirty = true
  }

  function markTouched(field) {
    fieldStates[field].touched = true
  }

  function shouldValidateField(field) {
    return fieldStates[field].dirty || fieldStates[field].touched
  }

  function setFieldLocalError(field, message) {
    fieldStates[field].error = message
  }

  function localFieldError(field) {
    return validateRegisterField(field, form)
  }

  function runLocalValidation(field, options = {}) {
    if (!options.force && !shouldValidateField(field)) {
      return true
    }

    let message = localFieldError(field)

    if (field === 'cpf' && message === '' && stickyAsyncErrors.cpf) {
      message = stickyAsyncErrors.cpf
    }

    setFieldLocalError(field, message)

    return message === ''
  }

  function currentFieldMessage(field) {
    if (field === 'cpf') {
      const cpf = unformatCpf(form.cpf)
      const hasKnownDuplicateCpf =
        cpfAvailability.value.valid === true &&
        cpfAvailability.value.available === false &&
        lastCpfChecked === cpf

      if (hasKnownDuplicateCpf) {
        return stickyAsyncErrors.cpf || 'Este CPF já está cadastrado.'
      }

      const cpfLocallyValid = validateRegisterField('cpf', form) === ''
      if (cpfLocallyValid && stickyAsyncErrors.cpf) {
        return stickyAsyncErrors.cpf
      }
    }

    if (fieldStates[field].error) {
      return fieldStates[field].error
    }

    const backendMessage = serverErrors.value[field]?.[0]
    return backendMessage ?? ''
  }

  const fieldErrors = computed(() => {
    const errors = {}

    fieldList.forEach((field) => {
      const message = currentFieldMessage(field)
      if (message) {
        errors[field] = [message]
      }
    })

    return errors
  })

  function hasVisibleErrors() {
    return Object.keys(fieldErrors.value).length > 0
  }

  function clearCpfDebounceTimer() {
    if (!cpfDebounceTimer) {
      return
    }

    clearTimeout(cpfDebounceTimer)
    cpfDebounceTimer = null
    timerState.cpf = false
  }

  function clearCepDebounceTimer() {
    if (!cepDebounceTimer) {
      return
    }

    clearTimeout(cepDebounceTimer)
    cepDebounceTimer = null
    timerState.cep = false
  }

  function invalidateCpfAsyncState() {
    cpfRequestVersion += 1
    fieldStates.cpf.validating = false
    fieldStates.cpf.asyncChecked = false
    stickyAsyncErrors.cpf = ''
    cpfAvailability.value = {
      valid: false,
      available: false,
      reason: null,
    }
    asyncFeedback.cpf = ''
    lastCpfChecked = ''
  }

  function invalidateCepAsyncState() {
    cepRequestVersion += 1
    fieldStates.cep.validating = false
    fieldStates.cep.asyncChecked = false
    asyncFeedback.cep = ''
    lastCepChecked = ''
  }

  async function runCpfAvailabilityCheck() {
    const cpf = unformatCpf(form.cpf)

    if (cpf.length !== 11 || !isValidCpfAlgorithm(cpf)) {
      return false
    }

    if (fieldStates.cpf.asyncChecked && lastCpfChecked === cpf) {
      if (cpfAvailability.value.available === false) {
        stickyAsyncErrors.cpf = 'Este CPF já está cadastrado.'
        setFieldLocalError('cpf', stickyAsyncErrors.cpf)
        asyncFeedback.cpf = ''

        return false
      }

      stickyAsyncErrors.cpf = ''
      setFieldLocalError('cpf', '')

      return true
    }

    const requestVersion = ++cpfRequestVersion
    fieldStates.cpf.validating = true
    asyncFeedback.cpf = 'Validando CPF...'

    try {
      const response = await checkCpfAvailability(cpf)

      if (requestVersion !== cpfRequestVersion) {
        return false
      }

      const data = response.data?.data ?? {}
      const available = data.available !== false

      fieldStates.cpf.asyncChecked = true
      lastCpfChecked = cpf
      cpfAvailability.value = {
        valid: true,
        available,
        reason: data.reason ?? null,
      }

      if (!available) {
        stickyAsyncErrors.cpf = 'Este CPF já está cadastrado.'
        setFieldLocalError('cpf', stickyAsyncErrors.cpf)
        asyncFeedback.cpf = ''
        return false
      }

      stickyAsyncErrors.cpf = ''
      setFieldLocalError('cpf', '')
      asyncFeedback.cpf = 'CPF disponível.'

      return true
    } catch (error) {
      if (requestVersion !== cpfRequestVersion) {
        return false
      }

      const parsedError = normalizeApiError(error)
      const cpfError = parsedError.errors?.cpf?.[0] ?? 'Não foi possível validar o CPF agora.'

      stickyAsyncErrors.cpf = cpfError
      setFieldLocalError('cpf', stickyAsyncErrors.cpf)
      asyncFeedback.cpf = ''

      return false
    } finally {
      if (requestVersion === cpfRequestVersion) {
        fieldStates.cpf.validating = false
      }
    }
  }

  async function runCepLookup() {
    const cep = unformatCep(form.cep)

    if (cep.length !== 8) {
      return false
    }

    if (cep === '00000000') {
      clearAddressFields()
      setFieldLocalError('cep', 'CEP inválido ou inexistente.')
      asyncFeedback.cep = ''

      return false
    }

    if (fieldStates.cep.asyncChecked && lastCepChecked === cep) {
      return fieldStates.cep.error === ''
    }

    const requestVersion = ++cepRequestVersion
    fieldStates.cep.validating = true
    asyncFeedback.cep = 'Consultando CEP...'
    cepNotice.value = ''

    try {
      const response = await lookupCep(cep)

      if (requestVersion !== cepRequestVersion) {
        return false
      }

      const data = response.data?.data ?? {}

      form.street = data.street ?? ''
      form.neighborhood = data.neighborhood ?? ''
      form.city = data.city ?? ''
      form.state = data.state ?? ''

      fieldStates.cep.asyncChecked = true
      lastCepChecked = cep
      setFieldLocalError('cep', '')
      asyncFeedback.cep = ''

      return true
    } catch (error) {
      if (requestVersion !== cepRequestVersion) {
        return false
      }

      clearAddressFields()

      const parsedError = normalizeApiError(error)
      const cepFieldError = parsedError.errors?.cep?.[0]

      if (cepFieldError) {
        setFieldLocalError('cep', cepFieldError)
        asyncFeedback.cep = ''
        return false
      }

      setFieldLocalError('cep', '')
      cepNotice.value = 'Servico de CEP indisponivel no momento. Voce pode continuar o cadastro.'
      asyncFeedback.cep = ''

      return true
    } finally {
      if (requestVersion === cepRequestVersion) {
        fieldStates.cep.validating = false
      }
    }
  }

  function scheduleCpfAvailability(options = {}) {
    clearCpfDebounceTimer()
    timerState.cpf = true

    cpfDebounceTimer = setTimeout(() => {
      clearCpfDebounceTimer()
      void runCpfAvailabilityCheck()
    }, options.immediate ? 0 : ASYNC_DEBOUNCE_MS)
  }

  function scheduleCepLookup(options = {}) {
    clearCepDebounceTimer()
    timerState.cep = true

    cepDebounceTimer = setTimeout(() => {
      clearCepDebounceTimer()
      void runCepLookup()
    }, options.immediate ? 0 : ASYNC_DEBOUNCE_MS)
  }

  function onFieldInput(field) {
    markDirty(field)
    clearServerError(field)
    runLocalValidation(field)

    if (field === 'password') {
      runLocalValidation('password_confirmation')
    }
  }

  function onFieldBlur(field) {
    markTouched(field)
    runLocalValidation(field, { force: true })

    if (field === 'password') {
      runLocalValidation('password_confirmation')
    }
  }

  function onCpfInput() {
    markDirty('cpf')
    clearServerError('cpf')
    form.cpf = formatCpf(form.cpf)
    const cpf = unformatCpf(form.cpf)

    if (cpf === lastCpfInputValue) {
      runLocalValidation('cpf')
      return
    }

    lastCpfInputValue = cpf

    clearCpfDebounceTimer()
    invalidateCpfAsyncState()

    const isLocallyValid = runLocalValidation('cpf')

    if (!isLocallyValid) {
      return
    }

    scheduleCpfAvailability()
  }

  function onCpfBlur() {
    markTouched('cpf')

    const cpf = unformatCpf(form.cpf)
    const cpfLocallyValid = validateRegisterField('cpf', form) === ''

    if (cpfLocallyValid && stickyAsyncErrors.cpf) {
      setFieldLocalError('cpf', stickyAsyncErrors.cpf)
      return
    }

    const hasKnownDuplicateCpf =
      cpfAvailability.value.valid === true &&
      cpfAvailability.value.available === false &&
      lastCpfChecked === cpf

    if (hasKnownDuplicateCpf) {
      stickyAsyncErrors.cpf = stickyAsyncErrors.cpf || 'Este CPF já está cadastrado.'
      setFieldLocalError('cpf', stickyAsyncErrors.cpf)
      return
    }

    const isLocallyValid = runLocalValidation('cpf', { force: true })

    if (!isLocallyValid) {
      clearCpfDebounceTimer()
      invalidateCpfAsyncState()
      return
    }

    scheduleCpfAvailability({ immediate: true })
  }

  function onCepInput() {
    markDirty('cep')
    clearServerError('cep')
    cepNotice.value = ''
    form.cep = formatCep(form.cep)

    clearCepDebounceTimer()
    invalidateCepAsyncState()

    const isLocallyValid = runLocalValidation('cep')
    const cep = unformatCep(form.cep)

    if (!isLocallyValid || cep.length !== 8) {
      clearAddressFields()
      return
    }

    scheduleCepLookup()
  }

  function onCepBlur() {
    markTouched('cep')

    const isLocallyValid = runLocalValidation('cep', { force: true })
    const cep = unformatCep(form.cep)

    if (!isLocallyValid || cep.length !== 8) {
      clearCepDebounceTimer()
      invalidateCepAsyncState()
      clearAddressFields()
      return
    }

    scheduleCepLookup({ immediate: true })
  }

  function clearServerErrors() {
    serverErrors.value = {}
  }

  function applyServerErrors(errors) {
    serverErrors.value = errors ?? {}

    const cpfError = errors?.cpf?.[0]
    if (cpfError) {
      const cpf = unformatCpf(form.cpf)
      const cpfLocallyValid = cpf.length === 11 && isValidCpfAlgorithm(cpf)

      if (cpfLocallyValid) {
        stickyAsyncErrors.cpf = cpfError
        setFieldLocalError('cpf', cpfError)
      } else {
        stickyAsyncErrors.cpf = ''
      }
    }
  }

  async function flushCpfValidation() {
    clearCpfDebounceTimer()
    return runCpfAvailabilityCheck()
  }

  async function flushCepLookup() {
    clearCepDebounceTimer()
    return runCepLookup()
  }

  async function validateBeforeSubmit() {
    clearServerErrors()
    cepNotice.value = ''

    fieldList.forEach((field) => {
      markTouched(field)
      markDirty(field)
      runLocalValidation(field, { force: true })
    })

    if (hasVisibleErrors()) {
      return false
    }

    const cpfValid = await flushCpfValidation()
    if (!cpfValid) {
      return false
    }

    const cepLocallyValid = runLocalValidation('cep', { force: true })
    const cep = unformatCep(form.cep)

    if (cepLocallyValid && cep.length === 8) {
      const cepResult = await flushCepLookup()
      if (!cepResult) {
        return false
      }
    }

    return !hasVisibleErrors()
  }

  const isValidatingCritical = computed(() => {
    return (
      fieldStates.cpf.validating ||
      fieldStates.cep.validating ||
      timerState.cpf ||
      timerState.cep
    )
  })

  const canShowSubmitButton = computed(() => {
    const cpf = unformatCpf(form.cpf)
    const cep = unformatCep(form.cep)
    const allFieldsValidLocally = fieldList.every((field) => validateRegisterField(field, form) === '')

    const cpfValidationCompleted =
      cpf.length === 11 &&
      isValidCpfAlgorithm(cpf) &&
      fieldStates.cpf.asyncChecked &&
      cpfAvailability.value.valid === true &&
      cpfAvailability.value.available === true &&
      stickyAsyncErrors.cpf === ''

    const cepValidationCompleted =
      cep.length === 8 &&
      (fieldStates.cep.asyncChecked || cepNotice.value !== '')

    return (
      allFieldsValidLocally &&
      cpfValidationCompleted &&
      cepValidationCompleted &&
      !hasVisibleErrors() &&
      !isValidatingCritical.value
    )
  })

  if (getCurrentInstance()) {
    onUnmounted(() => {
      clearCpfDebounceTimer()
      clearCepDebounceTimer()
    })
  }

  return {
    fieldStates,
    fieldErrors,
    asyncFeedback,
    cepNotice,
    cpfAvailability,
    isValidatingCritical,
    canShowSubmitButton,
    onFieldInput,
    onFieldBlur,
    onCpfInput,
    onCpfBlur,
    onCepInput,
    onCepBlur,
    applyServerErrors,
    clearServerErrors,
    validateBeforeSubmit,
  }
}
