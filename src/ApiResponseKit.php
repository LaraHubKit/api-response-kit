<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use LaraHub\ApiResponseKit\Formatters\ErrorFormatter;
use LaraHub\ApiResponseKit\Formatters\ExceptionFormatter;
use LaraHub\ApiResponseKit\Formatters\SuccessFormatter;
use LaraHub\ApiResponseKit\Formatters\ValidationFormatter;
use LaraHub\ApiResponseKit\Support\ConfigResolver;
use LaraHub\ApiResponseKit\Support\RequestIdGenerator;
use LaraHub\ApiResponseKit\Support\ResponseSchema;
use Throwable;

/**
 * Main API Response Kit class.
 *
 * Provides a unified interface for creating standardized API responses.
 * This class serves as the primary entry point for the package.
 */
class ApiResponseKit
{
    /**
     * The success formatter instance.
     */
    protected SuccessFormatter $successFormatter;

    /**
     * The error formatter instance.
     */
    protected ErrorFormatter $errorFormatter;

    /**
     * The validation formatter instance.
     */
    protected ValidationFormatter $validationFormatter;

    /**
     * The exception formatter instance.
     */
    protected ExceptionFormatter $exceptionFormatter;

    /**
     * The configuration resolver instance.
     */
    protected ConfigResolver $config;

    /**
     * The request ID generator instance.
     */
    protected RequestIdGenerator $requestIdGenerator;

    /**
     * The response schema builder instance.
     */
    protected ResponseSchema $schema;

    /**
     * Create a new ApiResponseKit instance.
     *
     * @param ConfigResolver $config
     * @param RequestIdGenerator $requestIdGenerator
     * @param ResponseSchema $schema
     * @param SuccessFormatter $successFormatter
     * @param ErrorFormatter $errorFormatter
     * @param ValidationFormatter $validationFormatter
     * @param ExceptionFormatter $exceptionFormatter
     */
    public function __construct(
        ConfigResolver $config,
        RequestIdGenerator $requestIdGenerator,
        ResponseSchema $schema,
        SuccessFormatter $successFormatter,
        ErrorFormatter $errorFormatter,
        ValidationFormatter $validationFormatter,
        ExceptionFormatter $exceptionFormatter
    ) {
        $this->config = $config;
        $this->requestIdGenerator = $requestIdGenerator;
        $this->schema = $schema;
        $this->successFormatter = $successFormatter;
        $this->errorFormatter = $errorFormatter;
        $this->validationFormatter = $validationFormatter;
        $this->exceptionFormatter = $exceptionFormatter;
    }

    /**
     * Create a success response.
     *
     * @param mixed $data The response data
     * @param string|null $message The success message
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $normalizedData = $this->normalizeData($data);
        $message = $message ?? $this->config->getDefaultMessage();

        return $this->successFormatter->format($normalizedData, $message, $statusCode, $meta);
    }

    /**
     * Create an error response.
     *
     * @param string $message The error message
     * @param mixed $errors The error details (optional)
     * @param int $statusCode The HTTP status code
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function error(string $message, mixed $errors = null, int $statusCode = 500, array $meta = []): JsonResponse
    {
        return $this->errorFormatter->formatWithErrors($message, $errors, $statusCode, $meta);
    }

    /**
     * Create a validation error response.
     *
     * @param array<string, mixed> $errors The validation errors
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function validationError(array $errors, string $message = 'Validation failed', array $meta = []): JsonResponse
    {
        return $this->validationFormatter->formatErrors($errors, $message, $meta);
    }

    /**
     * Create an exception response.
     *
     * @param Throwable $exception The exception to format
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function exception(Throwable $exception, array $meta = []): JsonResponse
    {
        return $this->exceptionFormatter->formatException($exception, $meta);
    }

    /**
     * Create a created response (201).
     *
     * @param mixed $data The created resource data
     * @param string $message The success message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function created(mixed $data = null, string $message = 'Resource created successfully', array $meta = []): JsonResponse
    {
        return $this->success($data, $message, 201, $meta);
    }

    /**
     * Create an accepted response (202).
     *
     * @param mixed $data The response data
     * @param string $message The success message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function accepted(mixed $data = null, string $message = 'Request accepted', array $meta = []): JsonResponse
    {
        return $this->success($data, $message, 202, $meta);
    }

    /**
     * Create a no content response (204).
     *
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function noContent(array $meta = []): JsonResponse
    {
        return $this->success(null, 'No content', 204, $meta);
    }

    /**
     * Create a bad request response (400).
     *
     * @param string $message The error message
     * @param mixed $errors The error details
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function badRequest(string $message = 'Bad request', mixed $errors = null, array $meta = []): JsonResponse
    {
        return $this->error($message, $errors, 400, $meta);
    }

    /**
     * Create an unauthorized response (401).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 401, $meta);
    }

    /**
     * Create a forbidden response (403).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 403, $meta);
    }

    /**
     * Create a not found response (404).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 404, $meta);
    }

    /**
     * Create a method not allowed response (405).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function methodNotAllowed(string $message = 'Method not allowed', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 405, $meta);
    }

    /**
     * Create a conflict response (409).
     *
     * @param string $message The error message
     * @param mixed $errors The error details
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function conflict(string $message = 'Conflict', mixed $errors = null, array $meta = []): JsonResponse
    {
        return $this->error($message, $errors, 409, $meta);
    }

    /**
     * Create an unprocessable entity response (422).
     *
     * @param string $message The error message
     * @param mixed $errors The error details
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function unprocessableEntity(string $message = 'Unprocessable entity', mixed $errors = null, array $meta = []): JsonResponse
    {
        return $this->error($message, $errors, 422, $meta);
    }

    /**
     * Create a too many requests response (429).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function tooManyRequests(string $message = 'Too many requests', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 429, $meta);
    }

    /**
     * Create an internal server error response (500).
     *
     * @param string $message The error message
     * @param mixed $errors The error details
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function serverError(string $message = 'Internal server error', mixed $errors = null, array $meta = []): JsonResponse
    {
        return $this->error($message, $errors, 500, $meta);
    }

    /**
     * Create a service unavailable response (503).
     *
     * @param string $message The error message
     * @param array<string, mixed> $meta Additional metadata
     * @return JsonResponse
     */
    public function serviceUnavailable(string $message = 'Service unavailable', array $meta = []): JsonResponse
    {
        return $this->error($message, null, 503, $meta);
    }

    /**
     * Get the current request ID.
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestIdGenerator->get();
    }

    /**
     * Set a custom request ID.
     *
     * @param string $requestId
     * @return void
     */
    public function setRequestId(string $requestId): void
    {
        $this->requestIdGenerator->setRequestId($requestId);
    }

    /**
     * Normalize data for response.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function normalizeData(mixed $data): mixed
    {
        if ($data instanceof Model) {
            return $data->toArray();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        if ($data instanceof Arrayable) {
            return $data->toArray();
        }

        return $data;
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
     * Get the response schema builder.
     *
     * @return ResponseSchema
     */
    public function getSchema(): ResponseSchema
    {
        return $this->schema;
    }
}
