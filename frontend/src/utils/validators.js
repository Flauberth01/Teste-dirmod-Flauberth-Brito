const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const amountRegex = /^\d{1,13}(\.\d{1,2})?$/
const currencyRegex = /^[A-Za-z]{3}$/

function digitsOnly(value) {
  return String(value ?? '').replace(/\D/g, '')
}

function isBlank(value) {
  return String(value ?? '').trim() === ''
}

function toFieldErrors(resultByField) {
  const errors = {}

  Object.entries(resultByField).forEach(([field, message]) => {
    if (message) {
      errors[field] = [message]
    }
  })

  return errors
}

export function toDigits(value) {
  return digitsOnly(value)
}

export function unformatCpf(value) {
  return digitsOnly(value)
}

export function formatCpf(value) {
  const digits = digitsOnly(value).slice(0, 11)
  const section1 = digits.slice(0, 3)
  const section2 = digits.slice(3, 6)
  const section3 = digits.slice(6, 9)
  const section4 = digits.slice(9, 11)

  if (digits.length <= 3) {
    return section1
  }

  if (digits.length <= 6) {
    return `${section1}.${section2}`
  }

  if (digits.length <= 9) {
    return `${section1}.${section2}.${section3}`
  }

  return `${section1}.${section2}.${section3}-${section4}`
}

export function unformatCep(value) {
  return digitsOnly(value)
}

export function formatCep(value) {
  const digits = digitsOnly(value).slice(0, 8)
  const section1 = digits.slice(0, 5)
  const section2 = digits.slice(5, 8)

  if (digits.length <= 5) {
    return section1
  }

  return `${section1}-${section2}`
}

export function isValidCpfAlgorithm(value) {
  const cpf = unformatCpf(value)

  if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
    return false
  }

  for (let position = 9; position < 11; position += 1) {
    let sum = 0

    for (let index = 0; index < position; index += 1) {
      sum += Number(cpf[index]) * (position + 1 - index)
    }

    const digit = ((10 * sum) % 11) % 10

    if (Number(cpf[position]) !== digit) {
      return false
    }
  }

  return true
}

export function validateRegisterField(field, form) {
  const name = String(form.name ?? '').trim()
  const email = String(form.email ?? '').trim()
  const password = String(form.password ?? '')
  const passwordConfirmation = String(form.password_confirmation ?? '')
  const cpf = unformatCpf(form.cpf)
  const cep = unformatCep(form.cep)

  if (field === 'name') {
    if (name === '') {
      return 'Nome é obrigatório.'
    }

    return ''
  }

  if (field === 'email') {
    if (email === '') {
      return 'E-mail é obrigatório.'
    }

    if (!emailRegex.test(email)) {
      return 'E-mail inválido.'
    }

    return ''
  }

  if (field === 'password') {
    if (password === '') {
      return 'Senha é obrigatória.'
    }

    if (password.length < 8) {
      return 'Senha deve ter no mínimo 8 caracteres.'
    }

    return ''
  }

  if (field === 'password_confirmation') {
    if (passwordConfirmation === '') {
      return 'Confirmação de senha é obrigatória.'
    }

    if (password !== passwordConfirmation) {
      return 'As senhas não coincidem.'
    }

    return ''
  }

  if (field === 'cpf') {
    if (cpf === '') {
      return 'CPF é obrigatório.'
    }

    if (cpf.length !== 11) {
      return 'CPF deve conter 11 dígitos.'
    }

    if (!isValidCpfAlgorithm(cpf)) {
      return 'CPF inválido.'
    }

    return ''
  }

  if (field === 'cep') {
    if (cep === '') {
      return 'CEP é obrigatório.'
    }

    if (cep.length !== 8) {
      return 'CEP deve conter 8 dígitos.'
    }

    if (cep === '00000000') {
      return 'CEP inválido ou inexistente.'
    }

    return ''
  }

  return ''
}

export function validateExpenseField(field, form) {
  const amountOriginal = String(form.amount_original ?? '').replace(',', '.').trim()
  const currency = String(form.currency ?? '').trim()

  if (field === 'amount_original') {
    if (isBlank(amountOriginal)) {
      return 'Valor é obrigatório.'
    }

    if (!amountRegex.test(amountOriginal)) {
      return 'Valor inválido. Use até 2 casas decimais.'
    }

    return ''
  }

  if (field === 'currency') {
    if (currency === '') {
      return 'Moeda é obrigatória.'
    }

    if (!currencyRegex.test(currency)) {
      return 'Moeda deve conter 3 letras (ex: USD).'
    }

    return ''
  }

  return ''
}

export function validateLoginForm(form) {
  const email = String(form.email ?? '').trim()
  const password = String(form.password ?? '')

  const emailError = email === '' ? 'E-mail é obrigatório.' : emailRegex.test(email) ? '' : 'E-mail inválido.'
  const passwordError = password === '' ? 'Senha é obrigatória.' : ''

  return toFieldErrors({
    email: emailError,
    password: passwordError,
  })
}

export function validateRegisterForm(form) {
  return toFieldErrors({
    name: validateRegisterField('name', form),
    email: validateRegisterField('email', form),
    password: validateRegisterField('password', form),
    password_confirmation: validateRegisterField('password_confirmation', form),
    cpf: validateRegisterField('cpf', form),
    cep: validateRegisterField('cep', form),
  })
}

export function validateExpenseForm(form) {
  return toFieldErrors({
    amount_original: validateExpenseField('amount_original', form),
    currency: validateExpenseField('currency', form),
  })
}

export function hasFieldErrors(errors) {
  return Object.keys(errors).length > 0
}
