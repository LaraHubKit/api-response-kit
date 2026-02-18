<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Support;

/**
 * Builds the final response structure based on configuration.
 *
 * This class is responsible for constructing the standardized response
 * schema with configurable keys and metadata.
 */
class ResponseSchema
{
    /**
     * The configuration resolver instance.
     */
    protected ConfigResolver $config;

    /**
     * The request ID generator instance.
     */
    protected RequestIdGenerator $requestIdGenerator;

    /**
     * Create a new ResponseSchema instance.
     *
     * @param ConfigResolver $config
     * @param RequestIdGenerator $requestIdGenerator
     */
    public function __construct(ConfigResolver $config, RequestIdGenerator $requestIdGenerator)
    {
        $this->config = $config;
        $this->requestIdGenerator = $requestIdGenerator;
    }

    /**
     * Build a success response schema.
     *
     * @param mixed $data The response data
     * @param string|null $message The success message
     * @param array<string, mixed> $additionalMeta Additional metadata
     * @return array<string, mixed>
     */
    public function buildSuccess(mixed $data, ?string $message = null, array $additionalMeta = []): array
    {
        $keys = $this->config->getKeys();

        return [
            $keys['success'] => true,
            $keys['message'] => $message ?? $this->config->getDefaultMessage(),
            $keys['data'] => $data,
            $keys['meta'] => $this->buildMeta($additionalMeta),
        ];
    }

    /**
     * Build an error response schema.
     *
     * @param string $message The error message
     * @param mixed $errors The error details (optional)
     * @param array<string, mixed> $additionalMeta Additional metadata
     * @return array<string, mixed>
     */
    public function buildError(string $message, mixed $errors = null, array $additionalMeta = []): array
    {
        $keys = $this->config->getKeys();

        return [
            $keys['success'] => false,
            $keys['message'] => $message,
            $keys['errors'] => $errors,
            $keys['meta'] => $this->buildMeta($additionalMeta),
        ];
    }

    /**
     * Build a validation error response schema.
     *
     * @param array<string, mixed> $errors The validation errors
     * @param string $message The error message
     * @param array<string, mixed> $additionalMeta Additional metadata
     * @return array<string, mixed>
     */
    public function buildValidationError(array $errors, string $message = 'Validation failed', array $additionalMeta = []): array
    {
        $keys = $this->config->getKeys();

        return [
            $keys['success'] => false,
            $keys['message'] => $message,
            $keys['errors'] => $this->formatValidationErrors($errors),
            $keys['meta'] => $this->buildMeta($additionalMeta),
        ];
    }

    /**
     * Build a paginated response schema.
     *
     * Pagination metadata is hoisted into the meta block under the
     * "pagination" key; the data key contains only the page items.
     *
     * @param array<mixed> $items The items for the current page
     * @param array<string, mixed> $pagination Pagination metadata
     * @param string|null $message The success message
     * @param array<string, mixed> $additionalMeta Additional metadata
     * @return array<string, mixed>
     */
    public function buildPaginated(array $items, array $pagination, ?string $message = null, array $additionalMeta = []): array
    {
        $keys = $this->config->getKeys();

        $meta = $this->buildMeta($additionalMeta);
        $meta['pagination'] = $pagination;

        return [
            $keys['success'] => true,
            $keys['message'] => $message ?? $this->config->getDefaultMessage(),
            $keys['data'] => $items,
            $keys['meta'] => $meta,
        ];
    }

    /**
     * Build the metadata array.
     *
     * @param array<string, mixed> $additional Additional metadata to merge
     * @return array<string, mixed>
     */
    public function buildMeta(array $additional = []): array
    {
        $meta = [
            'request_id' => $this->requestIdGenerator->get(),
            // Match the spec example style (UTC with "Z")
            'timestamp' => now()->utc()->toIso8601ZuluString(),
        ];

        return array_merge($meta, $additional);
    }

    /**
     * Format validation errors into a clean structure.
     *
     * @param array<string, mixed> $errors The raw validation errors
     * @return array<string, string>
     */
    protected function formatValidationErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            // Take the first error message for each field
            $formatted[$field] = is_array($messages) ? ($messages[0] ?? '') : $messages;
        }

        return $formatted;
    }

    /**
     * Get the configuration resolver.
     *
     * @return ConfigResolver
     */
    public function getConfig(): ConfigResolver
    {
        return $this->config;
    }

    /**
     * Get the request ID generator.
     *
     * @return RequestIdGenerator
     */
    public function getRequestIdGenerator(): RequestIdGenerator
    {
        return $this->requestIdGenerator;
    }
}
