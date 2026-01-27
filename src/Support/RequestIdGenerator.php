<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Support;

/**
 * Generates unique request IDs for API requests.
 *
 * Request IDs are useful for debugging, logging, and tracing
 * requests across distributed systems.
 */
class RequestIdGenerator
{
    /**
     * The prefix for generated request IDs.
     */
    protected string $prefix;

    /**
     * The current request ID (cached for the request lifecycle).
     */
    protected ?string $currentRequestId = null;

    /**
     * Create a new RequestIdGenerator instance.
     *
     * @param string $prefix The prefix for request IDs
     */
    public function __construct(string $prefix = 'LH-')
    {
        $this->prefix = $prefix;
    }

    /**
     * Generate a new unique request ID.
     *
     * @return string
     */
    public function generate(): string
    {
        if ($this->currentRequestId === null) {
            $this->currentRequestId = $this->createUniqueId();
        }

        return $this->currentRequestId;
    }

    /**
     * Get the current request ID or generate a new one.
     *
     * @return string
     */
    public function get(): string
    {
        return $this->generate();
    }

    /**
     * Reset the current request ID.
     *
     * This is useful for testing or when you need to generate
     * a new ID within the same request lifecycle.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->currentRequestId = null;
    }

    /**
     * Set a custom request ID.
     *
     * @param string $requestId
     * @return void
     */
    public function setRequestId(string $requestId): void
    {
        $this->currentRequestId = $requestId;
    }

    /**
     * Get the configured prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set the prefix for request IDs.
     *
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Create a unique ID string.
     *
     * @return string
     */
    protected function createUniqueId(): string
    {
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(bin2hex(random_bytes(4)));

        return $this->prefix . $timestamp . '-' . $random;
    }
}
