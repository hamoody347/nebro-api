# Nebro API

Multi-tenant REST API skeleton built on Laravel 13 + PHP 8.4. Provides database-per-tenant isolation, Google SSO, Sanctum auth, Meilisearch, Redis, and a custom payment abstraction layer — ready to add business logic on top.

## Stack

| Layer | Package / Service |
|---|---|
| Framework | Laravel 13, PHP 8.4 |
| Multi-tenancy | stancl/tenancy v3 — database-per-tenant |
| Database | MySQL 8+ (central DB + per-tenant DBs) |
| Auth | Laravel Sanctum — cookie (SPA) + Bearer token (mobile/API) |
| SSO | Google OAuth2 — custom adapter, no Socialite |
| Search | Meilisearch via laravel/scout |
| Queue / Cache / Session | Redis (separate databases per driver) |
| Permissions | spatie/laravel-permission v6 — teams mode (team = tenant) |
| Payment | Custom adapter — `PaymentGateway` interface + `StripeGateway` |
| Logging | `LoggingService` — four channels: `tenant`, `auth`, `payment`, `system` |
| Activity log | spatie/laravel-activitylog v4 |
| Dev tools | Laravel Telescope (local only) |

---

## Requirements

- **PHP 8.4** with extensions: `pdo_mysql`, `redis` (phpredis), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- **MySQL 8+**
- **Redis 6+**
- **Meilisearch** (local binary or Docker)
- **Composer 2**
- **Node.js / npm** (optional — only if working on frontend assets)

---

## Local Setup

### 1. Clone and install dependencies

```bash
git clone <repo-url> nebro
cd nebro
composer install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and fill in the values marked with `...` or left blank (see [Environment Variables](#environment-variables) below).

### 3. Create MySQL databases

Two databases are required — one central and one template (used by stancl/tenancy as the base for per-tenant DBs).

```bash
mysql -u root -p -e "
  CREATE DATABASE nebro_central  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE DATABASE nebro_default  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"
```

Update `.env` with your MySQL credentials:

```dotenv
DB_HOST=127.0.0.1
DB_DATABASE=nebro_default
DB_USERNAME=root
DB_PASSWORD=your_password

DB_CENTRAL_DATABASE=nebro_central
DB_CENTRAL_USERNAME=root
DB_CENTRAL_PASSWORD=your_password
```

### 4. Run central migrations

```bash
php artisan migrate --database=central
```

This sets up the `nebro_central` database: `users`, `social_identities`, `tenants`, `domains`, `telescope_entries`, `failed_jobs`, `job_batches`.

> Tenant migrations run automatically when a tenant is created (see [Tenant Management](#tenant-management)).

### 5. Start services

```bash
# Redis (if not already running)
redis-server

# Meilisearch
meilisearch --master-key="your-master-key"
# Set MEILISEARCH_KEY=your-master-key in .env
```

### 6. Start the dev server

```bash
php artisan serve
```

The API is available at `http://localhost:8000`.

---

## Environment Variables

Key variables grouped by service. Full list is in [.env.example](.env.example).

### Application
```dotenv
APP_NAME=Nebro
APP_ENV=local          # local | staging | production
APP_KEY=               # php artisan key:generate
APP_URL=http://localhost:8000
```

### Databases
```dotenv
# Tenant template connection (stancl switches this per request)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=nebro_default
DB_USERNAME=root
DB_PASSWORD=

# Central database (global users, tenants, telescope)
DB_CENTRAL_HOST=127.0.0.1
DB_CENTRAL_DATABASE=nebro_central
DB_CENTRAL_USERNAME=root
DB_CENTRAL_PASSWORD=
```

### Redis
```dotenv
REDIS_CLIENT=phpredis   # or predis (see Troubleshooting)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=0              # general
REDIS_CACHE_DB=1        # cache
REDIS_SESSION_DB=2      # sessions
REDIS_QUEUE_DB=3        # queue
```

### Sanctum
```dotenv
# Domains that receive stateful session cookies (comma-separated)
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,127.0.0.1:8000
```

### Google SSO
```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
# Must match an Authorized Redirect URI in Google Cloud Console
GOOGLE_REDIRECT_URI=http://localhost:8000/api/v1/auth/google/callback
```

### Meilisearch
```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your-master-key
```

### Payment (Stripe)
```dotenv
PAYMENT_GATEWAY=stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLISHABLE_KEY=pk_test_...
# From Stripe Dashboard → Webhooks → signing secret
STRIPE_WEBHOOK_SECRET=whsec_...
```

---

## Architecture Overview

### Tenant resolution

All tenant-scoped routes require an `X-Tenant-ID` header set to the tenant's slug:

```
X-Tenant-ID: acme-corp
```

The `tenant` route middleware (`InitializeTenancyByRequestData`) reads this header and switches the active database connection to `tenant_acme-corp` for the duration of the request.

### Database layout

```
nebro_central          ← shared / central DB
  users                  global identity (nullable password for SSO users)
  social_identities      Google OAuth tokens (encrypted)
  tenants                tenant registry
  domains                (future) custom domain mapping
  telescope_entries      dev tool — never per-tenant
  failed_jobs
  job_batches

tenant_{slug}          ← one DB per tenant (created automatically)
  users                  tenant-scoped users (central_user_id FK cross-ref)
  personal_access_tokens Sanctum Bearer tokens
  roles / permissions    spatie — teams mode, tenant_id as team key
  activity_log
  payments
  payment_transactions
  webhook_logs
```

### Redis database allocation

| DB | Connection | Used for | Tenant-prefixed |
|---|---|---|---|
| 0 | `default` | General / direct Redis calls | Yes |
| 1 | `cache` | `Cache` facade | Yes |
| 2 | `session` | Sessions | No — per cookie |
| 3 | `queue` | Queue jobs | No — tenant ID in payload |

### Auth flow

Two mechanisms are active simultaneously via `auth:sanctum`:

**Cookie (SPA / browser)**
1. `GET /sanctum/csrf-cookie` — sets `XSRF-TOKEN` cookie
2. Include `X-XSRF-TOKEN` header on mutating requests
3. Authenticate via `POST /api/v1/auth/login`

**Bearer token (mobile / server-to-server)**
1. `POST /api/v1/auth/login` — returns `{ "token": "..." }` in response body
2. Include `Authorization: Bearer <token>` on subsequent requests

Both flows issue a personal access token in the login response — the token can be used immediately for Bearer auth even in a cookie-first SPA.

---

## Tenant Management

### Create a tenant

```bash
php artisan tinker
```

```php
App\Models\Central\Tenant::create(['id' => 'acme-corp', 'name' => 'Acme Corp']);
```

This automatically:
1. Creates the MySQL database `tenant_acme-corp`
2. Runs all migrations in `database/migrations/tenant/`
3. Seeds default roles: `super-admin`, `admin`, `member`

### Run migrations on existing tenants

```bash
# All tenants
php artisan tenants:migrate

# Single tenant
php artisan tenants:migrate --tenants=acme-corp
```

### Run a command in tenant context

```bash
php artisan tinker
```

```php
$tenant = App\Models\Central\Tenant::find('acme-corp');
$tenant->run(function () {
    // Code here runs with the tenant DB active
    echo App\Models\Tenant\User::count();
});
```

### Import searchable models into Meilisearch

```bash
# Never use artisan scout:import directly — it won't set the correct tenant index prefix.
php artisan tenants:scout-import "App\Models\Tenant\YourModel"
```

---

## API Routes

```
GET    /health                         Status check — no auth, no tenant context

GET    /sanctum/csrf-cookie            Get CSRF cookie (required before cookie-auth login)

POST   /api/v1/auth/login              Login  (requires X-Tenant-ID)
POST   /api/v1/auth/register           Register (requires X-Tenant-ID)
GET    /api/v1/auth/{provider}/redirect  Start SSO flow (requires X-Tenant-ID)
GET    /api/v1/auth/{provider}/callback  SSO callback (requires X-Tenant-ID)
POST   /api/v1/auth/logout             Logout (auth required)
GET    /api/v1/auth/user               Current user (auth required)

GET    /api/v1/ping                    Tenant-scoped ping (auth + X-Tenant-ID)

POST   /webhooks/{gateway}/{tenant}    Webhook receiver — no auth, signature-verified
```

All `/api/v1/*` routes except the health check and webhook receiver require `X-Tenant-ID`.

---

## Adding a New Tenant-Scoped Feature

1. **Migration** — create file in `database/migrations/tenant/`, run `php artisan tenants:migrate`
2. **Model** — place in `app/Models/Tenant/`, do NOT set `$connection` (stancl manages it)
3. **Controller** — place in `app/Http/Controllers/`, keep it lean
4. **Route** — add inside the `middleware(['tenant', 'auth:sanctum', 'set_team'])` group in `routes/api.php`
5. **Permissions** — assign roles/permissions in `TenantDatabaseSeeder` or via a management endpoint

---

## Adding a Payment Gateway

Implement `App\Contracts\Payment\PaymentGateway` and register the driver in `PaymentGatewayManager::resolve()`:

```php
// app/Gateways/PaypalGateway.php
class PaypalGateway implements PaymentGateway { ... }

// app/Services/PaymentGatewayManager.php
'paypal' => new PaypalGateway($config),
```

Add config to `config/payment.php` and set `PAYMENT_GATEWAY=paypal` in `.env`.

---

## Adding an SSO Provider

Implement `App\Contracts\SSO\SSOAdapterInterface` and register it in `SSOManager`:

```php
// app/Adapters/SSO/GithubAdapter.php
class GithubAdapter implements SSOAdapterInterface { ... }

// app/Services/SSOManager.php (or AppServiceProvider binding)
'github' => fn () => new GithubAdapter(config('sso.github')),
```

Add config block to `config/sso.php`.

---

## Development Tools

### Telescope

Available at `/telescope` in local environment only. Automatically disabled in tests (`TELESCOPE_ENABLED=false` in `phpunit.xml`).

Stores data in the **central** DB — shows traffic across all tenants in a single UI.

### Logging

Use `LoggingService` instead of the `Log` facade directly — it automatically injects the current tenant ID into every entry:

```php
app(\App\Services\LoggingService::class)->auth()->info('User logged in', [
    'email' => $user->email,
]);
```

| Channel | File | Retention | Use for |
|---|---|---|---|
| `tenant` | `logs/tenant/` | 30 days | General tenant activity |
| `auth` | `logs/auth/` | 90 days | Login, logout, SSO events |
| `payment` | `logs/payment/` | 365 days | All payment operations |
| `system` | `logs/system/` | 14 days | Infrastructure, queue errors |

### Queue worker

```bash
php artisan queue:work --queue=default
```

Tenant context is automatically restored for queued jobs via `QueueTenancyBootstrapper`.

---

## Testing

```bash
# Unit + Feature (SQLite in-memory for the central DB)
php artisan test

# Integration tests (requires MySQL + Redis)
php artisan test --group=integration
```

Tests use `SCOUT_DRIVER=null` and `TELESCOPE_ENABLED=false` — no external services required for the default suite.

---

## Troubleshooting

**`Class "Redis" not found`**
The `phpredis` PHP extension is not installed. Either install it (`pecl install redis` using PHP 8.4's pecl binary) or switch to predis:
```dotenv
REDIS_CLIENT=predis
```
`predis/predis` is already in `composer.json`.

**`Tenant could not be identified`**
The `X-Tenant-ID` header is missing or the slug doesn't match any row in `nebro_central.tenants`. Verify with:
```sql
SELECT id FROM nebro_central.tenants;
```

**`SQLSTATE[42S02]: Table not found` in tenant context**
The tenant DB hasn't been migrated. Run:
```bash
php artisan tenants:migrate --tenants=your-tenant-slug
```

**Permission checks always return `false`**
The `SetPermissionsTeamId` middleware must run before any permission check. Ensure the route is inside a group that includes the `set_team` middleware. Also clear the permission cache:
```bash
php artisan permission:cache-reset
```

**`artisan migrate` touches the wrong database**
Always use `--database=central` for central migrations:
```bash
php artisan migrate --database=central
```
Running `artisan migrate` without this flag during development will migrate against `DB_DATABASE` (the tenant template DB).
