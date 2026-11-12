# Evento

**Evento** is a headless event management, ticketing, financial settlement, and attribution analytics API platform built as a modular monolith on **Laravel 13**, **PHP 8.3+**, and **PostgreSQL 15**.

---

## Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Runtime** | PHP 8.3+ (`strict_types=1`) |
| **Framework** | Laravel 13 (`laravel/framework` ^13.0) |
| **Database** | PostgreSQL 15+ (`citext`, `pgcrypto`, JSONB) |
| **API Specification** | JSON:API v1.0 (`laravel-json-api/laravel` ^5.3) + REST Actions |
| **OpenAPI Documentation** | Scramble (`dedoc/scramble`, interactive docs at `/docs/api`) |
| **Authentication & RBAC** | Laravel Passport 13 (OAuth2) + Spatie Permission |
| **DTOs & Value Objects** | `spatie/laravel-data` ^4.15 + `brick/money` |
| **Payments & Billing** | Stripe (`laravel/cashier`, `stripe/stripe-php`) |
| **Tickets & Calendar** | DomPDF (`barryvdh/laravel-dompdf`), Simple QR Code, iCalendar (`spatie/icalendar-generator`), Google Calendar |
| **Messaging & Webhooks** | SendGrid (`sendgrid/sendgrid`), Spatie Webhook Server (`spatie/laravel-webhook-server`) |
| **Testing & Quality** | PHPUnit 12 (`phpunit/phpunit` ^12.0), Laravel Pint (`laravel/pint`) |

---

## Architecture & Modules

Evento is organized as a **Modular Monolith** under `app/Modules/` with shared foundational primitives in `app/Core/` and JSON:API v1 schemas in `app/JsonApi/V1/`:

```text
app/
├── Core/                  # Base repositories, DomainObjects, MoneyValue, Retrier, JsonApi engine
├── JsonApi/V1/            # 21 JSON:API v1 resource schemas
├── Models/                # Eloquent models & relationships
└── Modules/
    ├── Platform/          # Health check (/api/v1/health), rate limiters, SpamGuard & JsonApi middleware
    ├── Identity/          # In-process OAuth2 token issuance, auth actions, /me profile, RBAC
    ├── Catalog/           # Events (items), categories, tags, venues, speakers, sponsors, announcements, surveys, tasks
    ├── Ticketing/         # Tickets, orders, transactional checkout, QR/PDF tickets, door check-in
    ├── Payments/          # Vendors, fees, payouts, transactions, Stripe PaymentIntents & subscriptions
    ├── Tracking/          # Affiliates, UTM & click-ID attribution, optimistically-locked event statistics, tracking pixels
    ├── Messaging/         # Organizer contact emails & SendGrid delivery statistics
    ├── Assets/            # S3/local media and PDF ticket asset management
    └── Webhooks/          # Inbound (Stripe, Snipcart, transactions) & outbound signed event webhooks
```

---

## Key Features

- **In-Process OAuth2 & RBAC (`Identity`)**:
  - Direct OAuth2 token issuance via Laravel Passport 13 without self-HTTP roundtrips.
  - Role and permission management (`admin`, `organizer`, `creator`, `vendor`, `speaker`, `sponsor`, `member`, `guest`) powered by `spatie/laravel-permission`.
- **Event Catalog & Public Discovery (`Catalog`)**:
  - Full CRUD and relationship management across `items`, `categories`, `tags`, `venues`, `speakers`, `sponsors`, `announcements`, `surveys`, and `tasks`.
  - Fast in-process public discovery endpoints (`/api/v1/public/items/{id}`, `/api/v1/public/items/{uuid}/calendar`, `/api/v1/public/speakers/{id}`, `/api/v1/public/sponsors/{id}`) and RFC 5545 `.ics` generation.
- **Transactional Ticketing & Door Check-In (`Ticketing`)**:
  - Atomic order checkout (`POST /api/v1/orders`) with row-level inventory locking, automatic attendee creation, and QR/PDF ticket generation.
  - Hardened door check-in (`PATCH /api/v1/check-in/{id}`) supporting `auth:api` or `X-Door-Key` authentication and strict quantity bounds validation (`0 <= checked_in_qty <= quantity`).
- **Exact-Decimal Payments & Settlement (`Payments`)**:
  - Monetary calculations backed by `brick/money` (`MoneyValue`) to eliminate floating-point drift across vendor percentage and fixed fees.
  - Stripe PaymentIntent generation (`/api/v1/payment/generate`) and subscription lifecycle endpoints (`/api/v1/subscriptions*`).
- **Event Tracking & Attribution Analytics (`Tracking`)**:
  - **Affiliates**: Case-insensitive affiliate code management, sales attribution rollups, and CSV export (`/api/v1/events/{event_id}/affiliates`).
  - **UTM & Click-ID Capture**: Order-level capture and aggregation of `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `gclid`, `fbclid`, and `referrer_url`.
  - **Optimistically-Locked Event Statistics**: Concurrency-safe `event_statistics` and `event_daily_statistics` (`version`-checked atomic updates with `Retrier` backoff) across order creation, partial/full refunds, cancellation, and reactivation.
  - **Tracking Pixels**: Validated Facebook Pixel, Google Analytics 4 (`G-XXXXXXXX`), Google Tag Manager (`GTM-XXXXXX` with SaaS XSS protection), and TikTok Pixel configuration.
- **Security Hardening**:
  - Named rate limiters (`auth-login`, `auth-register`, `auth-password`, `public-checkout`, `public-attendees`, `public-bookings`, `contact-organizer`, `door-checkin`) and honeypot/link-count spam protection (`SpamGuardMiddleware`).

---

## Prerequisites

- **PHP** >= 8.3 (with `pdo_pgsql`, `bcmath`, `mbstring`, `xml`, `gd`/`imagick` extensions)
- **Composer** 2.x
- **PostgreSQL** 15+ (with `citext` and `pgcrypto` extensions available)
- **Redis** 7+ *(optional — queues and cache default to PostgreSQL `database` driver)*

---

## Installation

### 1. Clone the Repository & Install Dependencies

```bash
composer install
```

### 2. Configure Environment Variables

Copy `.env.example` to `.env`, generate the application key, and generate the Hashids salt:

```bash
cp .env.example .env
php artisan key:generate
php artisan salt:generate
```

Configure your database and service credentials in `.env`:
- `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=evento`, `DB_USERNAME=evento`, `DB_PASSWORD=secret`
- `APP_SALT`: Salt used for Hashids event short codes
- `EVENTO_DOOR_CHECKIN_KEY`: Shared secret for `X-Door-Key` scanner check-in requests
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `SENDGRID_API_KEY`

### 3. Run Database Migrations & Seeders

Ensure the PostgreSQL database exists, then run database schema migrations and seed initial RBAC roles and permissions:

```bash
php artisan migrate --seed
```

### 4. Generate Laravel Passport Keys

Generate the OAuth2 encryption keys required by Laravel Passport to sign and verify tokens:

```bash
php artisan passport:keys
```

### 5. Create Storage Symlink

Create the symbolic link to expose public storage uploads and ticket assets:

```bash
php artisan storage:link
```

### Quick Installation Summary

To streamline local setup and minimize onboarding time, run the consolidated sequence:

```bash
php artisan key:generate && php artisan salt:generate && php artisan migrate --seed && php artisan passport:keys && php artisan storage:link
```

---

## Running the Application

### 1. Start the Local Server

```bash
php artisan serve
```

- **Health Check**: `GET http://localhost:8000/api/v1/health`
- **OpenAPI Documentation**: `GET http://localhost:8000/docs/api`
- **API Base**: `GET http://localhost:8000/api/v1`

### 2. Start the Queue Worker

Because background workloads (emails, webhooks, and analytics) rely on database queues (`QUEUE_CONNECTION=database`), run the queue worker in a dedicated terminal:

```bash
php artisan queue:work
```

---

## Console Commands

### `php artisan salt:generate`

Generates a cryptographically secure 32-character random salt key for Hashids encoding and automatically updates `APP_SALT` in your `.env` file.

```bash
# Generate salt and persist to .env
php artisan salt:generate

# Display generated salt without modifying .env
php artisan salt:generate --show

# Force generation in production environments
php artisan salt:generate --force
```

---

## Testing & Code Quality

Run the automated test suite (Unit, Feature, and Deviation Register Contract tests):

```bash
./vendor/bin/phpunit --testdox
```

Or via Composer:

```bash
composer test
```

Check code formatting with Laravel Pint:

```bash
composer lint
```

Automatically fix code style issues:

```bash
./vendor/bin/pint
```

---

## License

Evento is open-sourced software licensed under the [Apache License 2.0](LICENSE).

