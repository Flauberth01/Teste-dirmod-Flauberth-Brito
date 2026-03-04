import { reactive } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

const checkCpfAvailabilityMock = vi.fn()
const lookupCepMock = vi.fn()

vi.mock('../services/validationService', () => ({
  checkCpfAvailability: (...args) => checkCpfAvailabilityMock(...args),
}))

vi.mock('../services/cepService', () => ({
  lookupCep: (...args) => lookupCepMock(...args),
}))

import { useRegisterRealtimeValidation } from './useRegisterRealtimeValidation'

function createForm() {
  return reactive({
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
}

function delayedResolve(payload, delay = 0) {
  return new Promise((resolve) => {
    setTimeout(() => resolve(payload), delay)
  })
}

describe('useRegisterRealtimeValidation', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    checkCpfAvailabilityMock.mockReset()
    lookupCepMock.mockReset()
  })

  afterEach(() => {
    vi.runOnlyPendingTimers()
    vi.useRealTimers()
  })

  it('debounces cpf availability requests', async () => {
    checkCpfAvailabilityMock.mockResolvedValue({
      data: {
        data: {
          cpf: '52998224725',
          valid: true,
          available: true,
          reason: null,
        },
      },
    })

    const form = createForm()
    const validation = useRegisterRealtimeValidation(form)

    form.cpf = '52998224725'
    validation.onCpfInput()
    validation.onCpfInput()

    expect(checkCpfAvailabilityMock).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(349)
    expect(checkCpfAvailabilityMock).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(1)
    await Promise.resolve()

    expect(checkCpfAvailabilityMock).toHaveBeenCalledTimes(1)
    expect(checkCpfAvailabilityMock).toHaveBeenCalledWith('52998224725')
  })

  it('keeps local field error with higher priority than backend error', () => {
    const form = createForm()
    const validation = useRegisterRealtimeValidation(form)

    form.cpf = '123'
    validation.onCpfInput()
    validation.applyServerErrors({
      cpf: ['CPF já cadastrado.'],
    })

    expect(validation.fieldErrors.value.cpf?.[0]).toBe('CPF deve conter 11 dígitos.')
  })

  it('keeps duplicate cpf error fixed after blur and submit validation', async () => {
    checkCpfAvailabilityMock.mockResolvedValue({
      data: {
        data: {
          cpf: '52998224725',
          valid: true,
          available: false,
          reason: 'CPF_ALREADY_EXISTS',
        },
      },
    })

    const form = createForm()
    const validation = useRegisterRealtimeValidation(form)

    form.name = 'Ana'
    form.email = 'ana@example.com'
    form.password = 'secret123'
    form.password_confirmation = 'secret123'
    form.cep = '01001000'
    form.cpf = '52998224725'

    validation.onCpfInput()
    await vi.advanceTimersByTimeAsync(350)
    await Promise.resolve()

    expect(checkCpfAvailabilityMock).toHaveBeenCalledTimes(1)
    expect(validation.fieldErrors.value.cpf?.[0]).toBe('Este CPF já está cadastrado.')

    validation.onCpfBlur()
    await vi.advanceTimersByTimeAsync(0)
    await Promise.resolve()

    expect(validation.fieldErrors.value.cpf?.[0]).toBe('Este CPF já está cadastrado.')

    const canSubmit = await validation.validateBeforeSubmit()

    expect(canSubmit).toBe(false)
    expect(validation.fieldErrors.value.cpf?.[0]).toBe('Este CPF já está cadastrado.')

    validation.onCpfInput()
    expect(validation.fieldErrors.value.cpf?.[0]).toBe('Este CPF já está cadastrado.')

    form.cpf = '52998224724'
    validation.onCpfInput()

    expect(validation.fieldErrors.value.cpf?.[0]).toBe('CPF inválido.')
  })

  it('blocks submit when async cpf validation fails', async () => {
    checkCpfAvailabilityMock.mockImplementation(() =>
      delayedResolve(
        {
          data: {
            data: {
              cpf: '52998224725',
              valid: true,
              available: false,
              reason: 'CPF_ALREADY_EXISTS',
            },
          },
        },
        120,
      ),
    )

    lookupCepMock.mockResolvedValue({
      data: {
        data: {
          street: 'Praca da Se',
          neighborhood: 'Centro',
          city: 'Sao Paulo',
          state: 'SP',
        },
      },
    })

    const form = createForm()
    const validation = useRegisterRealtimeValidation(form)

    form.name = 'Ana'
    form.email = 'ana@example.com'
    form.password = 'secret123'
    form.password_confirmation = 'secret123'
    form.cpf = '52998224725'
    form.cep = '01001000'

    validation.onCpfInput()
    validation.onCepInput()

    let resolved = false
    const submitPromise = validation.validateBeforeSubmit().then((result) => {
      resolved = true
      return result
    })

    await Promise.resolve()
    expect(resolved).toBe(false)

    await vi.advanceTimersByTimeAsync(120)
    const canSubmit = await submitPromise

    expect(canSubmit).toBe(false)
    expect(validation.fieldErrors.value.cpf?.[0]).toContain('já está cadastrado')
  })
})
