<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default pagination behavior. The per_page value is used as
    | the default number of items per page when paginating query results.
    | Developers can override this per-call: paginate(15)
    |
    */
    'pagination' => [
        'per_page' => env('API_RESPONSE_KIT_PER_PAGE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Middleware Registration
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically register its middleware
    | alias (api-response-kit) and can optionally push it globally.
    |
    */
    'auto_middleware' => env('API_RESPONSE_KIT_AUTO_MIDDLEWARE', true),

    /*
    |--------------------------------------------------------------------------
    | Response Keys Configuration
    |--------------------------------------------------------------------------
    |
    | Define the keys used in the standardized API response structure.
    | You can customize these keys to match your API conventions.
    |
    */
    'keys' => [
        'success' => 'success',
        'message' => 'message',
        'data' => 'data',
        'errors' => 'errors',
        'meta' => 'meta',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request ID Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the prefix for generated request IDs. Request IDs are useful
    | for debugging, logging, and tracing requests across systems.
    |
    */
    'request_id_prefix' => env('API_RESPONSE_KIT_REQUEST_ID_PREFIX', 'LH-'),

    /*
    |--------------------------------------------------------------------------
    | Default Success Message
    |--------------------------------------------------------------------------
    |
    | The default message to use for successful responses when no message
    | is explicitly provided.
    |
    */
    'default_message' => env('API_RESPONSE_KIT_DEFAULT_MESSAGE', 'Success'),

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configure debug-related settings. In debug mode, full error details
    | and stack traces are included. In production, sensitive information
    | is hidden for security.
    |
    */
    'debug' => [
        // Show full stack traces in error responses (follows APP_DEBUG by default)
        'show_trace' => env('API_RESPONSE_KIT_SHOW_TRACE', env('APP_DEBUG', false)),

        // Hide SQL error details in production for security
        'hide_sql_errors' => env('API_RESPONSE_KIT_HIDE_SQL_ERRORS', env('APP_ENV') === 'production'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the automatic response formatting middleware.
    |
    */
    'middleware' => [
        // Enable or disable the middleware
        'enabled' => env('API_RESPONSE_KIT_MIDDLEWARE_ENABLED', true),

        // Push middleware to global stack (applies to all routes)
        'global' => env('API_RESPONSE_KIT_MIDDLEWARE_GLOBAL', false),

        // Routes to exclude from automatic formatting (supports wildcards)
        'exclude' => [
            // 'api/webhooks/*',
            // 'api/health',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Formatter Configuration
    |--------------------------------------------------------------------------
    |
    | Register custom response formatters. You can create your own formatter
    | classes that implement ResponseFormatterInterface and register them here.
    |
    */
    'formatter' => null, // Default formatter class (null uses built-in formatters)

    'custom_formatters' => [
        // 'my_custom_format' => App\Formatters\MyCustomFormatter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatter Overrides (Per Type)
    |--------------------------------------------------------------------------
    |
    | Override built-in formatter classes per response type. For safety and
    | backwards compatibility, overrides should extend the built-in formatter.
    |
    */
    'formatters' => [
        'success' => null,    // e.g. App\Formatters\MySuccessFormatter::class
        'error' => null,      // e.g. App\Formatters\MyErrorFormatter::class
        'validation' => null, // e.g. App\Formatters\MyValidationFormatter::class
        'exception' => null,  // e.g. App\Formatters\MyExceptionFormatter::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how exceptions are handled and formatted.
    |
    */
    'exceptions' => [
        // Maximum number of stack trace frames to include in debug mode
        'max_trace_frames' => env('API_RESPONSE_KIT_MAX_TRACE_FRAMES', 10),

        // Custom exception messages (override default messages for specific exceptions)
        'messages' => [
            // \Illuminate\Database\Eloquent\ModelNotFoundException::class => 'Resource not found',
            // \Illuminate\Auth\AuthenticationException::class => 'Unauthenticated',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Status Code Messages
    |--------------------------------------------------------------------------
    |
    | Default messages for HTTP status codes. These are used when no custom
    | message is provided for error responses.
    |
    */
    'status_messages' => [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ],
];
