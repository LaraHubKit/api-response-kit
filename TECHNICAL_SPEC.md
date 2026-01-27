# LaraHub API Response Kit – v1 Technical Specification

## 1. Overview

**Package Name:** LaraHub API Response Kit  
**Vendor:** LaraHub  
**Domain:** `https://www.larahub.io`  
**Repository Name:** `larahub/api-response-kit`

### Purpose

LaraHub API Response Kit is a Laravel package designed to standardize API responses, errors, and validation outputs across Laravel applications. The package aims to eliminate inconsistent API formats, reduce boilerplate code, and enforce a unified response structure across teams and projects.

### Core Objectives

- Provide a unified API response schema.
- Automatically format successful responses and errors.
- Standardize validation error responses.
- Reduce repetitive response code in controllers.
- Improve team-level consistency and API predictability.

---

## 2. Target Users

- Laravel backend developers
- API-driven applications (REST APIs)
- Development teams requiring standardized API contracts
- SaaS and enterprise Laravel projects

---

## 3. Key Features (v1 Scope)

### 3.1 Automatic API Response Formatting

- Automatically wraps controller return values into a standard JSON structure.
- Works with arrays, Eloquent models, and collections.

### 3.2 Global Error Handling

- Converts exceptions into standardized JSON error responses.
- Handles HTTP errors (404, 403, 401, 500, etc.).

### 3.3 Validation Error Standardization

- Formats Laravel validation errors into a clean and predictable structure.

### 3.4 Configurable Response Schema

- Allows developers to customize response keys via a config file.

### 3.5 Request ID Generation

- Generates a unique request ID for each API request.
- Useful for debugging and logging.

### 3.6 Facade Support

- Provides an optional facade for manual response control.

### 3.7 Middleware Integration

- Intercepts outgoing responses and applies formatting automatically.

### 3.8 Environment-Aware Debug Mode

- Automatically detects application environment (`APP_ENV` / `APP_DEBUG`).
- In debug mode (development), full error details and stack traces are included.
- In production mode, sensitive information (SQL queries, stack traces, file paths) is hidden.
- Ensures security and clean API responses in production.

### 3.9 Custom Response Format Support

- Allows developers to define their own response structure.
- Developers can create custom formatter classes.
- Package dynamically loads user-defined formatters via configuration.
- Enables multiple response schemas for different projects or teams.

---

## 4. Standard Response Schema (Default)

### 4.1 Success Response

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

### 4.2 Error Response

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

### 4.3 Validation Error Response

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

## 5. Package Architecture

### 5.1 Folder Structure

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
├── README.md
└── TECHNICAL_SPEC.md
```

---

## 6. Core Components

### 6.1 Service Provider

Responsibilities:

- Register package services.
- Publish configuration file.
- Bind `ApiResponseKit` service into Laravel container.

### 6.2 Middleware (`ApiResponseKitMiddleware`)

Responsibilities:

- Intercept outgoing HTTP responses.
- Detect response type (success, error, validation error).
- Apply appropriate formatter.

### 6.3 Formatter Classes

- **SuccessFormatter**: Formats successful responses.
- **ErrorFormatter**: Formats generic errors and HTTP exceptions.
- **ValidationFormatter**: Formats validation error responses.
- **ExceptionFormatter**: Converts exceptions into standardized JSON errors.

### 6.4 Support Classes

- **ResponseSchema**: Builds final response structure based on config.
- **RequestIdGenerator**: Generates unique request IDs.
- **ConfigResolver**: Resolves configuration values dynamically.

### 6.5 Facade (`ApiResponseKit`)

- Provides manual control when automatic formatting is not desired.

---

## 7. Configuration

### 7.1 Debug Mode Configuration

```php
return [
    'debug' => [
        'show_trace' => env('APP_DEBUG', false),
        'hide_sql_errors' => env('APP_ENV') === 'production',
    ],
];
```

### 7.2 Custom Formatter Configuration

```php
return [
    'formatter' => null,
    'custom_formatters' => [
        // 'my_custom_format' => App\Formatters\MyCustomFormatter::class,
    ],
];
```

---

## 8. Usage Guide

### 8.1 Installation

```bash
composer require larahub/api-response-kit
```

### 8.2 Publish Config

```bash
php artisan vendor:publish --tag=api-response-kit-config
```

### 8.3 Automatic Usage

```php
return User::all();
```

### 8.4 Manual Usage

```php
return ApiResponseKit::success($data, 'Users fetched successfully');
```

---

## 9. Roadmap (Future Versions)

### v2 (Planned)

- API versioning support.
- Multiple response schemas.
- Advanced logging integration.

### v3 (Planned)

- API documentation auto-generation.
- Frontend SDK integration.
- Enterprise-level customization.

---

## 10. Summary

LaraHub API Response Kit v1 provides a foundational framework for standardized API responses in Laravel applications. By automating response formatting and error handling, it improves developer productivity, enforces consistency, and simplifies API maintenance across teams.

