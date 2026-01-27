<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Formatters;

use Illuminate\Http\JsonResponse;
use LaraHub\ApiResponseKit\Contracts\ResponseFormatterInterface;
use LaraHub\ApiResponseKit\Support\ResponseSchema;

/**
 * Formats error API responses.
 *
 * This formatter handles generic errors and HTTP exceptions,
 * wrapping them in a standardized structure.
 */
class ErrorFormatter implements ResponseFormatterInterface
{
    /**
     * The response schema builder.
     */
    protected ResponseSchema $schema;

    /**
     * Create a new ErrorFormatter instance.
     *
     * @param ResponseSchema $schema
     */
    public function __construct(ResponseSchema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Format the error data into a standardized JSON response.
     *
     * @param mixed $data The error data (usually null for errors)
     * @param string $message The error message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function format(mixed $data, string $message, int $statusCode = 500, array $meta = []): JsonResponse
    {
        $responseData = $this->schema->buildError($message, $data, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Format an error with specific error details.
     *
     * @param string $message The error message
     * @param mixed $errors The error details
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function formatWithErrors(string $message, mixed $errors, int $statusCode = 500, array $meta = []): JsonResponse
    {
        $responseData = $this->schema->buildError($message, $errors, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Get the response type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'error';
    }
}
