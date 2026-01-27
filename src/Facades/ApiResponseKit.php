<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * ApiResponseKit Facade.
 *
 * Provides a static interface to the ApiResponseKit service.
 *
 * @method static \Illuminate\Http\JsonResponse success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse error(string $message, mixed $errors = null, int $statusCode = 500, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse validationError(array $errors, string $message = 'Validation failed', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse exception(\Throwable $exception, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse created(mixed $data = null, string $message = 'Resource created successfully', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse accepted(mixed $data = null, string $message = 'Request accepted', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse noContent(array $meta = [])
 * @method static \Illuminate\Http\JsonResponse badRequest(string $message = 'Bad request', mixed $errors = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse unauthorized(string $message = 'Unauthorized', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse forbidden(string $message = 'Forbidden', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse notFound(string $message = 'Resource not found', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse methodNotAllowed(string $message = 'Method not allowed', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse conflict(string $message = 'Conflict', mixed $errors = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse unprocessableEntity(string $message = 'Unprocessable entity', mixed $errors = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse tooManyRequests(string $message = 'Too many requests', array $meta = [])
 * @method static \Illuminate\Http\JsonResponse serverError(string $message = 'Internal server error', mixed $errors = null, array $meta = [])
 * @method static \Illuminate\Http\JsonResponse serviceUnavailable(string $message = 'Service unavailable', array $meta = [])
 * @method static string getRequestId()
 * @method static void setRequestId(string $requestId)
 * @method static \LaraHub\ApiResponseKit\Support\ConfigResolver getConfig()
 * @method static \LaraHub\ApiResponseKit\Support\ResponseSchema getSchema()
 *
 * @see \LaraHub\ApiResponseKit\ApiResponseKit
 */
class ApiResponseKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \LaraHub\ApiResponseKit\ApiResponseKit::class;
    }
}
