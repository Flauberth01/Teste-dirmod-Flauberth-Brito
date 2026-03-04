import { expect, test } from '@playwright/test'

test('register, create expense and open details', async ({ page }) => {
  await page.route('**/api/**', async (route) => {
    const url = route.request().url()
    const method = route.request().method()

    if (url.endsWith('/api/cep/01001000') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            street: 'Praca da Se',
            neighborhood: 'Centro',
            city: 'Sao Paulo',
            state: 'SP',
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/validation/cpf/52998224725') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            cpf: '52998224725',
            valid: true,
            available: true,
            reason: null,
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/auth/register') && method === 'POST') {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            token: 'token-1',
            user: {
              id: 1,
              name: 'Ana',
              email: 'ana@example.com',
              address_status: 'resolved',
            },
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/expenses') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: [] }),
      })
      return
    }

    if (url.endsWith('/api/expenses') && method === 'POST') {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 1,
            amount_original: '10.00',
            currency: 'USD',
            exchange_rate: '5.000000',
            amount_brl: '50.00',
            status: 'converted',
            failure_reason: null,
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/expenses/1') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 1,
            amount_original: '10.00',
            currency: 'USD',
            exchange_rate: '5.000000',
            amount_brl: '50.00',
            status: 'converted',
            failure_reason: null,
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/auth/me') && method === 'GET') {
      await route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({
          message: 'Unauthenticated',
          errors: null,
          code: 'UNAUTHENTICATED',
        }),
      })
      return
    }

    await route.fallback()
  })

  await page.goto('/register')

  await page.fill('#name', 'Ana')
  await page.fill('#email', 'ana@example.com')
  await page.fill('#password', 'secret123')
  await page.fill('#password_confirmation', 'secret123')
  await page.fill('#cpf', '52998224725')
  await page.fill('#cep', '01001000')
  await page.locator('#cep').blur()

  await expect(page.getByRole('button', { name: 'Cadastrar' })).toBeVisible()
  await page.click('button[type="submit"]')
  await expect(page).toHaveURL(/\/expenses$/)

  await page.click('a:has-text("Nova despesa")')
  await expect(page).toHaveURL(/\/expenses\/new$/)

  await page.fill('#amount_original', '10.00')
  await page.fill('#currency', 'USD')
  await page.click('button[type="submit"]')

  await expect(page).toHaveURL(/\/expenses\/1$/)
  await expect(page.getByText('Detalhes da despesa')).toBeVisible()
})

test('redirects to login when accessing private route without session', async ({ page }) => {
  await page.route('**/api/auth/me', async (route) => {
    await route.fulfill({
      status: 401,
      contentType: 'application/json',
      body: JSON.stringify({
        message: 'Unauthenticated',
        errors: null,
        code: 'UNAUTHENTICATED',
      }),
    })
  })

  await page.goto('/expenses')
  await expect(page).toHaveURL(/\/login/)
})

test('keeps duplicate cpf error visible after blur and hides submit button', async ({ page }) => {
  await page.route('**/api/**', async (route) => {
    const url = route.request().url()
    const method = route.request().method()

    if (url.endsWith('/api/validation/cpf/52998224725') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            cpf: '52998224725',
            valid: true,
            available: false,
            reason: 'CPF_ALREADY_EXISTS',
          },
        }),
      })
      return
    }

    if (url.endsWith('/api/cep/69902758') && method === 'GET') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            street: 'Rua Exemplo',
            neighborhood: 'Centro',
            city: 'Rio Branco',
            state: 'AC',
          },
        }),
      })
      return
    }

    await route.fallback()
  })

  await page.goto('/register')

  await page.fill('#name', 'Flauberth Brito')
  await page.fill('#email', 'flauberth@gmail.com')
  await page.fill('#password', 'secret123')
  await page.fill('#password_confirmation', 'secret123')
  await page.fill('#cpf', '52998224725')
  await page.fill('#cep', '69902758')
  await page.locator('#cpf').blur()
  await page.locator('#name').click()

  await expect(page.getByText('Este CPF já está cadastrado.')).toBeVisible()
  await expect(page.getByRole('button', { name: 'Cadastrar' })).toBeDisabled()
})
