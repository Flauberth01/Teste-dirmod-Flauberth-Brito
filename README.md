# Teste 02 — Gestão de Despesas Internacionais

Projeto full-stack com backend Laravel + PostgreSQL e frontend Vue 3 + Vite (JavaScript), implementando cadastro com CPF/CEP, despesas com conversão automática para BRL e controle de acesso por usuário.

## Estrutura

- `backend/`: API Laravel
- `frontend/`: SPA Vue 3
- `Testes - SEAD.md`: enunciado original

## Rodar com Docker (recomendado)

```bash
docker compose up --build
```

Aplicação:
- Frontend: `http://localhost:5173`
- Backend: `http://localhost:8000`
- Postgres (host): `127.0.0.1:5433` (`postgres/postgres`, database `sead_expenses`)

Comandos úteis:

```bash
docker compose down
docker compose down -v
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f postgres
```

## Requisitos

- PHP 8.2+
- Composer 2+
- PostgreSQL 14+
- Node.js 22+
- npm 10+

## Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

### Testes backend

```bash
cd backend
php artisan test
```

## Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
```

### Testes frontend

```bash
cd frontend
npm run test:unit
npm run test:e2e
```

## Variáveis importantes

### Backend (`backend/.env`)

- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `CEP_API_URL`
- `EXCHANGE_RATE_API_URL`

### Frontend (`frontend/.env`)

- `VITE_API_BASE_URL=http://localhost:8000/api`

## Endpoints principais

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/cep/{cep}`
- `GET /api/expenses`
- `POST /api/expenses`
- `GET /api/expenses/{id}`
- `POST /api/expenses/{id}/retry-conversion`

## Regras de CEP

- O CEP `00000000` é considerado inválido e retorna erro de validação.
- Em indisponibilidade da API externa de CEP, o cadastro só pode seguir como `pending` para CEP sintaticamente válido e diferente de `00000000`.

## Contrato de erro (fixo)

- Campos base de erro:
  - `{ "message": "...", "errors": null|{...}, "code": "...", "status": 000, "request_id": "..." }`
- `422` -> `code: "VALIDATION_ERROR"`
- `401` -> `code: "UNAUTHENTICATED"`
- `403` -> `code: "FORBIDDEN"`
- `503/504` (serviço externo) -> `code: "EXTERNAL_SERVICE_UNAVAILABLE"`
- `503` (banco indisponível) -> `code: "DATABASE_UNAVAILABLE"`
- `500` (falha de escrita no banco) -> `code: "DATABASE_WRITE_ERROR"`
- `500` (falha interna não mapeada) -> `code: "INTERNAL_ERROR"`
