<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Contracts;

use Illuminate\Http\JsonResponse;

/**
 * Interface for response formatters.
 *
 * This interface defines the contract that all response formatters must implement.
 * It allows developers to create custom formatters that can be dynamically loaded
 * via configuration.
 */
interface ResponseFormatterInterface
{
    /**
     * Format the response data into a standardized JSON response.
     *
     * @param mixed $data The data to be formatted
     * @param string $message The response message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function format(mixed $data, string $message, int $statusCode, array $meta = []): JsonResponse;

    /**
     * Get the response type identifier.
     *
     * @return string
     */
    public function getType(): string;
}
