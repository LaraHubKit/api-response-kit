<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Formatters;

use Illuminate\Http\JsonResponse;
use LaraHub\ApiResponseKit\Contracts\ResponseFormatterInterface;
use LaraHub\ApiResponseKit\Support\ResponseSchema;

/**
 * Formats successful API responses.
 *
 * This formatter handles all successful responses, wrapping data
 * in a standardized structure with metadata.
 */
class SuccessFormatter implements ResponseFormatterInterface
{
    /**
     * The response schema builder.
     */
    protected ResponseSchema $schema;

    /**
     * Create a new SuccessFormatter instance.
     *
     * @param ResponseSchema $schema
     */
    public function __construct(ResponseSchema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Format the response data into a standardized JSON response.
     *
     * @param mixed $data The data to be formatted
     * @param string $message The response message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function format(mixed $data, string $message, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $responseData = $this->schema->buildSuccess($data, $message, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Get the response type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'success';
    }
}
