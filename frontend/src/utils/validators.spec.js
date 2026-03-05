import { describe, expect, it } from 'vitest'
import {
  formatCep,
  formatCpf,
  isValidCpfAlgorithm,
  unformatCep,
  unformatCpf,
  validateExpenseForm,
  validateLoginForm,
  validateRegisterForm,
} from './validators'

describe('form validators', () => {
  it('validates login required fields', () => {
    const errors = validateLoginForm({ email: '', password: '' })

    expect(errors.email?.[0]).toContain('obrigatório')
    expect(errors.password?.[0]).toContain('obrigatória')
  })

  it('validates register cpf algorithm and cep format', () => {
    const errors = validateRegisterForm({
      name: 'Ana',
      email: 'ana@example.com',
      password: 'secret123',
      password_confirmation: 'secret123',
      cpf: '11111111111',
      cep: '123',
    })

    expect(errors.cpf?.[0]).toContain('inválido')
    expect(errors.cep?.[0]).toContain('8 dígitos')
  })

  it('rejects zeroed cep', () => {
    const errors = validateRegisterForm({
      name: 'Ana',
      email: 'ana@example.com',
      password: 'secret123',
      password_confirmation: 'secret123',
      cpf: '52998224725',
      cep: '00000000',
    })

    expect(errors.cep?.[0]).toContain('inválido ou inexistente')
  })

  it('validates expense decimal precision', () => {
    const errors = validateExpenseForm({ amount_original: '10.999', currency: 'US' })

    expect(errors.amount_original?.[0]).toContain('2 casas')
    expect(errors.currency?.[0]).toContain('3 letras')
  })

  it('formats and unformats cpf and cep values', () => {
    expect(formatCpf('52998224725')).toBe('529.982.247-25')
    expect(unformatCpf('529.982.247-25')).toBe('52998224725')
    expect(formatCep('01001000')).toBe('01001-000')
    expect(unformatCep('01001-000')).toBe('01001000')
  })

  it('validates cpf check digits', () => {
    expect(isValidCpfAlgorithm('52998224725')).toBe(true)
    expect(isValidCpfAlgorithm('52998224724')).toBe(false)
  })
})
