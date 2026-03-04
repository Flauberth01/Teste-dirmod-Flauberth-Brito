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

## Contrato de erro (fixo)

- `422` -> `{ "message": "Validation error", "errors": { ... }, "code": "VALIDATION_ERROR" }`
- `401` -> `{ "message": "Unauthenticated", "errors": null, "code": "UNAUTHENTICATED" }`
- `403` -> `{ "message": "Forbidden", "errors": null, "code": "FORBIDDEN" }`
- `503/504` -> `{ "message": "External service unavailable", "errors": null, "code": "EXTERNAL_SERVICE_UNAVAILABLE" }`
