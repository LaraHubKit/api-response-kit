<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Support;

use Illuminate\Support\Facades\Config;

/**
 * Resolves configuration values dynamically.
 *
 * This class provides a centralized way to access package configuration
 * with fallback defaults and environment-aware settings.
 */
class ConfigResolver
{
    /**
     * The configuration prefix.
     */
    protected const CONFIG_PREFIX = 'api-response-kit';

    /**
     * Get a configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $default The default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Config::get(self::CONFIG_PREFIX . '.' . $key, $default);
    }

    /**
     * Get the response keys configuration.
     *
     * @return array<string, string>
     */
    public function getKeys(): array
    {
        return $this->get('keys', [
            'success' => 'success',
            'message' => 'message',
            'data' => 'data',
            'errors' => 'errors',
            'meta' => 'meta',
        ]);
    }

    /**
     * Get a specific response key.
     *
     * @param string $key The key name (success, message, data, errors, meta)
     * @return string
     */
    public function getKey(string $key): string
    {
        $keys = $this->getKeys();

        return $keys[$key] ?? $key;
    }

    /**
     * Get the request ID prefix.
     *
     * @return string
     */
    public function getRequestIdPrefix(): string
    {
        return $this->get('request_id_prefix', 'LH-');
    }

    /**
     * Get the default success message.
     *
     * @return string
     */
    public function getDefaultMessage(): string
    {
        return $this->get('default_message', 'Success');
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return $this->get('debug.show_trace', Config::get('app.debug', false));
    }

    /**
     * Check if SQL errors should be hidden.
     *
     * @return bool
     */
    public function shouldHideSqlErrors(): bool
    {
        return $this->get('debug.hide_sql_errors', Config::get('app.env') === 'production');
    }

    /**
     * Get maximum number of stack trace frames to include.
     *
     * @return int
     */
    public function getMaxTraceFrames(): int
    {
        return (int) $this->get('exceptions.max_trace_frames', 10);
    }

    /**
     * Get custom exception message overrides.
     *
     * @return array<string, string>
     */
    public function getExceptionMessageOverrides(): array
    {
        /** @var array<string, string> $messages */
        $messages = $this->get('exceptions.messages', []);

        return $messages;
    }

    /**
     * Get default messages for HTTP status codes.
     *
     * @return array<int, string>
     */
    public function getStatusMessages(): array
    {
        /** @var array<int, string> $messages */
        $messages = $this->get('status_messages', []);

        return $messages;
    }

    /**
     * Get the default message for a status code.
     *
     * @param int $statusCode
     * @param string $fallback
     * @return string
     */
    public function getStatusMessage(int $statusCode, string $fallback = 'An error occurred'): string
    {
        $messages = $this->getStatusMessages();

        return $messages[$statusCode] ?? $fallback;
    }

    /**
     * Get formatter overrides per response type.
     *
     * @return array{success?: string|null, error?: string|null, validation?: string|null, exception?: string|null}
     */
    public function getFormatterOverrides(): array
    {
        /** @var array{success?: string|null, error?: string|null, validation?: string|null, exception?: string|null} $formatters */
        $formatters = $this->get('formatters', []);

        return $formatters;
    }

    /**
     * Get a formatter override for a specific type.
     *
     * @param string $type success|error|validation|exception
     * @return string|null
     */
    public function getFormatterForType(string $type): ?string
    {
        $formatters = $this->getFormatterOverrides();

        $value = $formatters[$type] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Get the default formatter class.
     *
     * @return string|null
     */
    public function getDefaultFormatter(): ?string
    {
        return $this->get('formatter');
    }

    /**
     * Get custom formatters configuration.
     *
     * @return array<string, string>
     */
    public function getCustomFormatters(): array
    {
        return $this->get('custom_formatters', []);
    }

    /**
     * Get a custom formatter by name.
     *
     * @param string $name The formatter name
     * @return string|null
     */
    public function getCustomFormatter(string $name): ?string
    {
        $formatters = $this->getCustomFormatters();

        return $formatters[$name] ?? null;
    }

    /**
     * Check if the middleware is enabled.
     *
     * @return bool
     */
    public function isMiddlewareEnabled(): bool
    {
        return $this->get('middleware.enabled', true);
    }

    /**
     * Get the routes that should be excluded from formatting.
     *
     * @return array<string>
     */
    public function getExcludedRoutes(): array
    {
        return $this->get('middleware.exclude', []);
    }

    /**
     * Check if a route should be excluded from formatting.
     *
     * @param string $route The route path
     * @return bool
     */
    public function isRouteExcluded(string $route): bool
    {
        $excludedRoutes = $this->getExcludedRoutes();

        foreach ($excludedRoutes as $pattern) {
            if (fnmatch($pattern, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the entire configuration array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Config::get(self::CONFIG_PREFIX, []);
    }
}
