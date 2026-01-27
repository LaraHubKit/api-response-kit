<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Formatters;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use LaraHub\ApiResponseKit\Contracts\ResponseFormatterInterface;
use LaraHub\ApiResponseKit\Support\ResponseSchema;

/**
 * Formats validation error responses.
 *
 * This formatter handles Laravel validation errors, converting them
 * into a clean and predictable structure.
 */
class ValidationFormatter implements ResponseFormatterInterface
{
    /**
     * The response schema builder.
     */
    protected ResponseSchema $schema;

    /**
     * The default validation error message.
     */
    protected string $defaultMessage = 'Validation failed';

    /**
     * Create a new ValidationFormatter instance.
     *
     * @param ResponseSchema $schema
     */
    public function __construct(ResponseSchema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Format the validation errors into a standardized JSON response.
     *
     * @param mixed $data The validation errors
     * @param string $message The error message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function format(mixed $data, string $message, int $statusCode = 422, array $meta = []): JsonResponse
    {
        $errors = is_array($data) ? $data : [];
        $responseData = $this->schema->buildValidationError($errors, $message, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Format a ValidationException into a standardized JSON response.
     *
     * @param ValidationException $exception The validation exception
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function formatException(ValidationException $exception, array $meta = []): JsonResponse
    {
        $errors = $exception->errors();
        $message = $exception->getMessage() ?: $this->defaultMessage;

        return $this->format($errors, $message, 422, $meta);
    }

    /**
     * Format raw validation errors array.
     *
     * @param array<string, array<string>> $errors The validation errors
     * @param string|null $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function formatErrors(array $errors, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->format($errors, $message ?? $this->defaultMessage, 422, $meta);
    }

    /**
     * Get the response type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'validation';
    }

    /**
     * Set the default validation error message.
     *
     * @param string $message
     * @return void
     */
    public function setDefaultMessage(string $message): void
    {
        $this->defaultMessage = $message;
    }
}
