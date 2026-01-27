# LaraHub API Response Kit

A Laravel package to standardize API responses, errors, and validation outputs across Laravel applications.

## Why

- Consistent JSON response schema across teams/projects
- Less boilerplate in controllers/services
- Predictable error + validation formats
- Optional request ID for tracing/debugging

## Installation

```bash
composer require larahub/api-response-kit
```

## Publish config

```bash
php artisan vendor:publish --tag=api-response-kit-config
```

This will publish `config/api-response-kit.php`.

## Basic usage

When middleware is enabled, normal controller returns are wrapped into the standard response schema.

```php
// Controller action
return User::query()->latest()->paginate();
```

## Manual usage (Facade)

```php
use LaraHub\ApiResponseKit\Facades\ApiResponseKit;

return ApiResponseKit::success(['user' => $user], 'User fetched');
```

## Middleware behavior

The package registers a middleware alias:

- `api-response-kit`

Auto-registration is controlled by:

- `api-response-kit.auto_middleware`
- `api-response-kit.middleware.enabled`
- `api-response-kit.middleware.global`
- `api-response-kit.middleware.exclude`

## Customizing schema keys and defaults

Edit `config/api-response-kit.php`:

- `keys.*` (success/message/data/errors/meta)
- `default_message`
- `request_id_prefix`

## Custom formatters

You can provide your own formatter classes via config:

- `api-response-kit.formatter` (default formatter implementation)
- `api-response-kit.custom_formatters` (named formatters)
- `api-response-kit.formatters.*` (override built-in per type: success/error/validation/exception)

For safety/backwards-compatibility, per-type overrides should extend the built-in formatter they replace.

## Docs

- `TECHNICAL_SPEC.md`
