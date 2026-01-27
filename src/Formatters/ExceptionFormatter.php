<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Formatters;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use LaraHub\ApiResponseKit\Contracts\ResponseFormatterInterface;
use LaraHub\ApiResponseKit\Support\ConfigResolver;
use LaraHub\ApiResponseKit\Support\ResponseSchema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Formats exception responses.
 *
 * This formatter converts exceptions into standardized JSON error responses,
 * with environment-aware handling of sensitive information.
 */
class ExceptionFormatter implements ResponseFormatterInterface
{
    /**
     * The response schema builder.
     */
    protected ResponseSchema $schema;

    /**
     * The configuration resolver.
     */
    protected ConfigResolver $config;

    /**
     * The validation formatter for validation exceptions.
     */
    protected ValidationFormatter $validationFormatter;

    /**
     * Create a new ExceptionFormatter instance.
     *
     * @param ResponseSchema $schema
     * @param ConfigResolver $config
     * @param ValidationFormatter $validationFormatter
     */
    public function __construct(
        ResponseSchema $schema,
        ConfigResolver $config,
        ValidationFormatter $validationFormatter
    ) {
        $this->schema = $schema;
        $this->config = $config;
        $this->validationFormatter = $validationFormatter;
    }

    /**
     * Format the exception into a standardized JSON response.
     *
     * @param mixed $data The exception or error data
     * @param string $message The error message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function format(mixed $data, string $message, int $statusCode = 500, array $meta = []): JsonResponse
    {
        $errors = null;

        if ($data instanceof Throwable) {
            return $this->formatException($data, $meta);
        }

        $responseData = $this->schema->buildError($message, $errors, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Format a Throwable exception into a standardized JSON response.
     *
     * @param Throwable $exception The exception to format
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function formatException(Throwable $exception, array $meta = []): JsonResponse
    {
        // Handle validation exceptions separately
        if ($exception instanceof ValidationException) {
            return $this->validationFormatter->formatException($exception, $meta);
        }

        $statusCode = $this->getStatusCode($exception);
        $message = $this->getMessage($exception);
        $errors = $this->getErrorDetails($exception);

        $responseData = $this->schema->buildError($message, $errors, $meta);

        return new JsonResponse($responseData, $statusCode);
    }

    /**
     * Get the HTTP status code for the exception.
     *
     * @param Throwable $exception
     * @return int
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof QueryException) {
            return 500;
        }

        return 500;
    }

    /**
     * Get the error message for the exception.
     *
     * @param Throwable $exception
     * @return string
     */
    protected function getMessage(Throwable $exception): string
    {
        // Allow explicit message overrides
        $overrides = $this->config->getExceptionMessageOverrides();
        $exceptionClass = get_class($exception);
        if (isset($overrides[$exceptionClass]) && is_string($overrides[$exceptionClass]) && $overrides[$exceptionClass] !== '') {
            return $overrides[$exceptionClass];
        }

        // In production, hide sensitive SQL error messages
        if ($exception instanceof QueryException && $this->config->shouldHideSqlErrors()) {
            return 'A database error occurred';
        }

        if ($exception instanceof HttpException) {
            return $exception->getMessage() ?: $this->config->getStatusMessage($exception->getStatusCode(), 'An error occurred');
        }

        // In production, use generic message for unexpected exceptions
        if (!$this->config->isDebugMode()) {
            return 'An unexpected error occurred';
        }

        return $exception->getMessage() ?: 'An error occurred';
    }

    /**
     * Get error details based on environment.
     *
     * @param Throwable $exception
     * @return array<string, mixed>|null
     */
    protected function getErrorDetails(Throwable $exception): ?array
    {
        if (!$this->config->isDebugMode()) {
            return null;
        }

        $details = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if ($this->config->isDebugMode()) {
            $details['trace'] = $this->formatStackTrace($exception);
        }

        return $details;
    }

    /**
     * Format the stack trace for debug output.
     *
     * @param Throwable $exception
     * @return array<int, array<string, mixed>>
     */
    protected function formatStackTrace(Throwable $exception): array
    {
        $trace = [];
        $maxFrames = max(0, $this->config->getMaxTraceFrames());

        foreach ($exception->getTrace() as $index => $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? 'unknown',
                'line' => $frame['line'] ?? 0,
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
            ];

            // Limit trace frames (configurable)
            if ($maxFrames > 0 && $index >= ($maxFrames - 1)) {
                break;
            }
        }

        return $trace;
    }

    /**
     * Get the response type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'exception';
    }
}
