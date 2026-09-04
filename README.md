# Task API Scaffold

Laravel 13 API scaffold for client-facing integrations.

The project is intended to demonstrate a structure suitable for:

- API clients and internal frontends
- third-party integrations
- LLM and agent tooling
- MCP-style server consumers
- DTO and multitenancy expansion

The app is currently single-tenant at runtime. Multitenancy-related structure is intentionally left as scaffolding for future implementation.

## Current Shape

- Versioned API routes under `routes/api.php`
- Auth endpoint using Sanctum personal access tokens
- Request validation classes under `app/Http/Api/Requests`
- API controllers under `app/Http/Api/Controllers`
- Resource serialization under `app/Http/Api/Resources`
- Response wrapper classes scaffolded under `app/Http/Api/Responses`

Current API routes:

- `POST /api/v1/auth/login`
- `GET /api/v1/users`
- `GET /api/v1/tasks`
- `POST /api/v1/tasks`
- `GET /api/v1/tasks/{task}`
- `PUT /api/v1/tasks/{task}`
- `DELETE /api/v1/tasks/{task}`
- `GET /api/v1/attachments`
- `POST /api/v1/attachments`
- `GET /api/v1/attachments/{attachment}`
- `PUT /api/v1/attachments/{attachment}`
- `DELETE /api/v1/attachments/{attachment}`
- `POST /api/webhooks/task-created`

All `/api/v1/users`, `/api/v1/tasks`, and `/api/v1/attachments` routes require a Sanctum bearer token. The login endpoint and task-created webhook do not.

## Authentication

Authentication uses Laravel Sanctum token creation via:

- `POST /api/v1/auth/login`

Request body:

```json
{
  "email": "client@example.com",
  "password": "password"
}
```

Optional header:

```text
X-Integration-Name: Johns-Modem
```

Successful login returns a bearer token.

The login endpoint is rate-limited by email and client IP.

## Seeded Client

The default database seeder creates a test client account:

- Email: `client@example.com`
- Password: `password`

Defined in `database/seeders/DatabaseSeeder.php`.

## Getting Started

### Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- MongoDB with the PHP MongoDB extension

### Initial setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

The example environment uses a local MongoDB instance at `mongodb://127.0.0.1:27017/` and database name `myjamjar`. Change `DB_URI` and `DB_DATABASE` in `.env` when using another MongoDB instance.

Or use the Composer helper:

```bash
composer setup
```

## Useful Commands

### Development

Start the full local dev stack:

```bash
composer dev
```

Start only the Laravel server:

```bash
php artisan serve
```

Tail Laravel logs:

```bash
php artisan pail
```

### Database

Run migrations:

```bash
php artisan migrate
```

Refresh and reseed:

```bash
php artisan migrate:fresh --seed
```

Run seeders only:

```bash
php artisan db:seed
```

### Testing

Run the full test suite:

```bash
composer test
```

Run a focused auth test:

```bash
php artisan test tests/Feature/Auth/LoginTest.php
```

### Introspection

List API routes:

```bash
php artisan route:list --path=api/v1
```

Open an interactive shell:

```bash
php artisan tinker
```

## Example Login Request

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Integration-Name: local-client" \
  -d '{
    "email": "client@example.com",
    "password": "password"
  }'
```

## Design Intent

This codebase is meant to show a clean starting structure for an API-first Laravel app rather than a finished production platform.

Intentional design choices:

- versioned routes from day one
- separated API controllers and requests
- token-based auth for machine clients
- room for DTO/action layering
- room for future multitenant scoping

Not implemented yet:

- Multitenancy
- Data Transfer Object mapping
- Resource policies
- Tenant-aware data scoping
- formal API schema or SDK generation

## Notes

- User IDs use ULIDs.
- Sanctum personal access tokens are configured to work with ULID-backed users.
- Database-backed tests require a reachable MongoDB instance because the domain models explicitly use the `mongodb` connection. The current `phpunit.xml` SQLite setting does not override those model connections.
