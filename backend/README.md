# Backend (Laravel API)

API para cadastro de usuários com CPF/CEP, registro de despesas internacionais e conversão para BRL.

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

## Testes

```bash
php artisan test
```

## Observações

- Autenticação com Sanctum (`auth:sanctum`).
- Serviços externos:
  - CEP: ViaCEP
  - Câmbio: Frankfurter
- Em indisponibilidade de serviço externo, cadastro/despesa podem ser salvos como `pending` conforme regra de negócio.
