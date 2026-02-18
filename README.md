# LaraHub API Response Kit

**Vendor:** LaraHub | **Domain:** [larahub.io](https://www.larahub.io) | **Package:** `larahub/api-response-kit`

A Laravel package to standardize API responses, errors, and validation outputs across Laravel applications.

## Why

- Consistent JSON response schema across teams/projects
- Less boilerplate in controllers/services
- Predictable error + validation formats
- Optional request ID for tracing/debugging
- Environment-aware debug mode (hides sensitive info in production)
- Custom formatter support for project-specific schemas

---

## Features

| Feature | Description |
|---|---|
| Automatic response formatting | Wraps controller return values (arrays, models, collections) into standard JSON |
| Global error handling | Converts exceptions into standardized JSON error responses (404, 403, 401, 500, etc.) |
| Validation error standardization | Formats Laravel validation errors into a clean, predictable structure |
| Configurable response schema | Customize response keys via config file |
| Request ID generation | Unique request ID (`LH-XXXXXX`) per request for debugging/logging |
| Facade support | Manual response control when automatic formatting is not needed |
| Middleware integration | Intercepts outgoing responses and applies formatting automatically |
| Debug mode | Full stack traces in dev; sensitive info (SQL, file paths) hidden in production |
| Custom formatters | Define your own response structure via custom formatter classes |

---

## Installation

```bash
composer require larahub/api-response-kit
```

## Publish Config

```bash
php artisan vendor:publish --tag=api-response-kit-config
```

This will publish `config/api-response-kit.php`.

---

## Response Schema

### Success Response

```json
{
  "success": true,
  "message": "Success",
  "data": {},
  "meta": {
    "request_id": "LH-XXXXXX",
    "timestamp": "2026-01-27T12:00:00Z"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error message",
  "errors": null,
  "meta": {
    "request_id": "LH-XXXXXX",
    "timestamp": "2026-01-27T12:00:00Z"
  }
}
```

### Validation Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": "The email field is required"
  },
  "meta": {
    "request_id": "LH-XXXXXX",
    "timestamp": "2026-01-27T12:00:00Z"
  }
}
```

---

## Basic Usage

When middleware is enabled, normal controller returns are automatically wrapped into the standard response schema.

```php
// Controller action — no changes needed
return User::query()->latest()->paginate();
```

## Manual Usage (Facade)

```php
use LaraHub\ApiResponseKit\Facades\ApiResponseKit;

return ApiResponseKit::success(['user' => $user], 'User fetched');
```

---

## Middleware

The package registers a middleware alias:

- `api-response-kit`

Auto-registration is controlled by config keys:

- `api-response-kit.auto_middleware`
- `api-response-kit.middleware.enabled`
- `api-response-kit.middleware.global`
- `api-response-kit.middleware.exclude`

---

## Configuration

### Customizing Schema Keys and Defaults

Edit `config/api-response-kit.php`:

- `keys.*` — override key names (`success`, `message`, `data`, `errors`, `meta`)
- `default_message` — default success message
- `request_id_prefix` — prefix for generated request IDs

### Debug Mode

```php
return [
    'debug' => [
        'show_trace' => env('APP_DEBUG', false),
        'hide_sql_errors' => env('APP_ENV') === 'production',
    ],
];
```

In `APP_DEBUG=true` (development): full error details and stack traces are included.  
In production: SQL queries, stack traces, and file paths are hidden.

### Custom Formatters

You can provide your own formatter classes via config:

```php
return [
    'formatter' => null,
    'custom_formatters' => [
        // 'my_custom_format' => App\Formatters\MyCustomFormatter::class,
    ],
];
```

Config keys:

- `api-response-kit.formatter` — default formatter implementation
- `api-response-kit.custom_formatters` — named formatters
- `api-response-kit.formatters.*` — override built-in per type: `success` / `error` / `validation` / `exception`

For safety/backwards-compatibility, per-type overrides should extend the built-in formatter they replace.

---

## Package Architecture

### Folder Structure

```text
ApiResponseKit/
├── config/
│   └── api-response-kit.php
├── src/
│   ├── ApiResponseKitServiceProvider.php
│   ├── ApiResponseKit.php
│   ├── Facades/
│   │   └── ApiResponseKit.php
│   ├── Middleware/
│   │   └── ApiResponseKitMiddleware.php
│   ├── Formatters/
│   │   ├── SuccessFormatter.php
│   │   ├── ErrorFormatter.php
│   │   ├── ValidationFormatter.php
│   │   └── ExceptionFormatter.php
│   ├── Contracts/
│   │   └── ResponseFormatterInterface.php
│   └── Support/
│       ├── ResponseSchema.php
│       ├── RequestIdGenerator.php
│       └── ConfigResolver.php
├── composer.json
└── README.md
```

### Core Components

| Component | Responsibility |
|---|---|
| `ApiResponseKitServiceProvider` | Register services, publish config, bind into Laravel container |
| `ApiResponseKitMiddleware` | Intercept responses, detect type, apply appropriate formatter |
| `SuccessFormatter` | Format successful responses |
| `ErrorFormatter` | Format generic errors and HTTP exceptions |
| `ValidationFormatter` | Format validation error responses |
| `ExceptionFormatter` | Convert exceptions into standardized JSON errors |
| `ResponseSchema` | Build final response structure based on config |
| `RequestIdGenerator` | Generate unique request IDs |
| `ConfigResolver` | Resolve configuration values dynamically |
| `ApiResponseKit` (Facade) | Manual response control when automatic formatting is not desired |

---

## Target Users

- Laravel backend developers
- API-driven applications (REST APIs)
- Development teams requiring standardized API contracts
- SaaS and enterprise Laravel projects

---

## Roadmap

### v2 (Planned)

- API versioning support
- Multiple response schemas
- Advanced logging integration

### v3 (Planned)

- API documentation auto-generation
- Frontend SDK integration
- Enterprise-level customization
