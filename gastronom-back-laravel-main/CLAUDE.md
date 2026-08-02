# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Setup
composer run setup          # Full setup: install deps, .env, migrate, build

# Development
composer run dev            # Concurrent: server + queue + logs + vite dev
npm run dev                 # Vite dev server only
npm run build               # Vite production build

# Testing
php artisan test --compact                    # Run all tests
php artisan test --compact --filter=TestName  # Run single test

# Code style
vendor/bin/pint --dirty --format agent        # Format changed files

# Utilities
php artisan migrate
php artisan pail            # Real-time log viewer
```

## Architecture

**Gastronom** is a food/grocery home delivery platform. It exposes a versioned REST API (Sanctum auth) consumed by a mobile app, plus a Filament v4 admin panel.

### Request Lifecycle

```
routes/api.php (v1)
  → app/Http/Controllers/Api/V1/
  → app/Services/           (business logic)
  → app/Repositories/       (data access)
  → app/Models/
```

Supporting patterns:
- **DTOs** (`app/DTO/`) — typed data passing between layers
- **Strategies** (`app/Strategies/`) — pluggable search implementations
- **Form Requests** (`app/Http/Requests/`) — all validation here, never in controllers
- **API Resources** (`app/Http/Resources/`) — all response shaping here

### Key Domains

| Domain | Models | Notes |
|--------|--------|-------|
| Catalog | `Product`, `Category`, `ProductAttribute` | Products use Spatie MediaLibrary with thumb/medium/large conversions |
| Orders | `Order`, `OrderItem`, `Cart`, `CartItem` | Cart cleared after order creation; `ProductSale` updated on completion |
| Auth | `Customer` (Authenticatable) | OTP flow via ichtrojan/laravel-otp → Sanctum token |
| Payments | `Transaction`, `PaymentMethod`, `DeliveryMethod` | Statuses tracked via Enums |
| CMS | `StaticPage`, `Setting` | Key-value settings via `SettingsService` |
| Support | `Support` | Customer support tickets |

### Background Jobs (`app/Jobs/`)

Heavy operations run on queue:
- `ImportFrom1CJob` / `ProcessOneCv2FileJob` — catalog sync from 1C accounting system
- `SearchProductImageJob` / `ProcessProductImagesJob` — image search and conversion
- `ProcessProductBatchJob` — bulk product operations

Queue listener: `php artisan queue:listen --tries=1`

### 1C Integration

Two route files handle 1C accounting sync:
- `routes/onec.php` — legacy format
- `routes/onec_v2.php` — v2 format (active)

Services: `ImportFrom1CService`, `ProcessOneCv2FileService`. All operations logged to `sync_logs`.

### Admin Panel

Filament v4 resources in `app/Filament/Resources/`. All major entities (products, orders, customers, categories, settings, support) have full CRUD interfaces there.

### Middleware & Bootstrap

Laravel 12 style — middleware configured in `bootstrap/app.php`, providers in `bootstrap/providers.php`. No `app/Http/Kernel.php`.

## Code Conventions

- **PHP 8.3** — use constructor property promotion, explicit return types, named arguments
- **Enums** in `app/Enums/` — keys in TitleCase; use these everywhere instead of string constants
- **`env()`** only in `config/` files; use `config('key')` elsewhere
- **Eager loading** — always load relationships to avoid N+1
- **Named routes** — use `route()` helper, not hardcoded URLs
- Before writing new code, check sibling files for existing patterns/approach
- Run `vendor/bin/pint --dirty` before finalizing any changes

## Testing

PHPUnit only (no Pest). Tests live in `tests/Feature/` and `tests/Unit/`.

`phpunit.xml` sets `APP_ENV=testing` and runs `config:clear` before the suite. The test database uses `DB_CONNECTION=sqlite` with `:memory:`.
