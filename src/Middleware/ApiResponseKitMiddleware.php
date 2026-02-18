<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaraHub\ApiResponseKit\ApiResponseKit;
use LaraHub\ApiResponseKit\Support\ConfigResolver;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Middleware for automatic API response formatting.
 *
 * This middleware intercepts outgoing HTTP responses and applies
 * standardized formatting automatically.
 */
class ApiResponseKitMiddleware
{
    /**
     * The API Response Kit instance.
     */
    protected ApiResponseKit $apiResponseKit;

    /**
     * The configuration resolver instance.
     */
    protected ConfigResolver $config;

    /**
     * Create a new middleware instance.
     *
     * @param ApiResponseKit $apiResponseKit
     * @param ConfigResolver $config
     */
    public function __construct(ApiResponseKit $apiResponseKit, ConfigResolver $config)
    {
        $this->apiResponseKit = $apiResponseKit;
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return SymfonyResponse
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Check if middleware is enabled
        if (!$this->config->isMiddlewareEnabled()) {
            return $next($request);
        }

        // Check if route should be excluded
        if ($this->shouldExclude($request)) {
            return $next($request);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            // Convert exceptions into standardized JSON errors
            return $this->apiResponseKit->exception($e);
        }

        // Only format JSON responses or array/object responses
        if ($this->shouldFormat($response)) {
            return $this->formatResponse($response);
        }

        return $response;
    }

    /**
     * Check if the request should be excluded from formatting.
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldExclude(Request $request): bool
    {
        $path = $request->path();

        return $this->config->isRouteExcluded($path);
    }

    /**
     * Check if the response should be formatted.
     *
     * @param mixed $response
     * @return bool
     */
    protected function shouldFormat(mixed $response): bool
    {
        // Don't format if already a JsonResponse with our structure
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);

            // Check if response already has our standard structure
            $keys = $this->config->getKeys();
            if (isset($data[$keys['success']]) && isset($data[$keys['meta']])) {
                return false;
            }

            return true;
        }

        // Format Response objects with JSON content
        if ($response instanceof Response) {
            $contentType = $response->headers->get('Content-Type', '');

            return str_contains($contentType, 'application/json');
        }

        return false;
    }

    /**
     * Format the response into standardized structure.
     *
     * @param SymfonyResponse $response
     * @return JsonResponse
     */
    protected function formatResponse(SymfonyResponse $response): JsonResponse
    {
        $statusCode = $response->getStatusCode();
        $data = $this->extractData($response);

        // Detect validation errors (common Laravel 422 structure: { message, errors: { field: [...] } })
        if ($statusCode === 422 && is_array($data)) {
            $message = is_string($data['message'] ?? null) ? (string) $data['message'] : 'Validation failed';
            $errors = is_array($data['errors'] ?? null) ? $data['errors'] : null;

            if (is_array($errors)) {
                return $this->apiResponseKit->validationError($errors, $message);
            }
        }

        // Determine if this is a success or error response based on status code
        if ($statusCode >= 200 && $statusCode < 300) {
            // Detect a serialised LengthAwarePaginator or CursorPaginator payload
            if (is_array($data) && $this->isPaginatorPayload($data)) {
                return $this->formatPaginatedPayload($data);
            }

            return $this->apiResponseKit->success($data, null, $statusCode);
        }

        // Handle error responses
        $message = $this->getErrorMessage($statusCode, $data);

        return $this->apiResponseKit->error($message, $data, $statusCode);
    }

    /**
     * Extract data from the response.
     *
     * @param SymfonyResponse $response
     * @return mixed
     */
    protected function extractData(SymfonyResponse $response): mixed
    {
        if ($response instanceof JsonResponse) {
            return $response->getData(true);
        }

        $content = $response->getContent();

        if (is_string($content)) {
            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $content;
    }

    /**
     * Get an appropriate error message based on status code.
     *
     * @param int $statusCode
     * @param mixed $data
     * @return string
     */
    protected function getErrorMessage(int $statusCode, mixed $data): string
    {
        // Try to extract message from data
        if (is_array($data) && isset($data['message'])) {
            return $data['message'];
        }

        return $this->config->getStatusMessage($statusCode, 'An error occurred');
    }

    /**
     * Detect whether the decoded response payload is a serialised paginator.
     *
     * Laravel serialises both LengthAwarePaginator (has current_page + total)
     * and CursorPaginator (has next_cursor key) to JSON automatically.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    protected function isPaginatorPayload(array $data): bool
    {
        // LengthAwarePaginator: has data array + current_page + total
        if (isset($data['data'], $data['current_page'], $data['total']) && is_array($data['data'])) {
            return true;
        }

        // CursorPaginator: has data array + next_cursor / prev_cursor keys
        if (isset($data['data']) && is_array($data['data']) && array_key_exists('next_cursor', $data)) {
            return true;
        }

        return false;
    }

    /**
     * Reformat a serialised paginator payload into the standard paginated shape.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    protected function formatPaginatedPayload(array $data): JsonResponse
    {
        $items = array_values((array) $data['data']);

        // Build pagination metadata depending on paginator type
        if (isset($data['total'])) {
            $pagination = [
                'current_page'  => $data['current_page'] ?? null,
                'last_page'     => $data['last_page'] ?? null,
                'per_page'      => $data['per_page'] ?? null,
                'total'         => $data['total'] ?? null,
                'from'          => $data['from'] ?? null,
                'to'            => $data['to'] ?? null,
                'next_page_url' => $data['next_page_url'] ?? null,
                'prev_page_url' => $data['prev_page_url'] ?? null,
            ];
        } else {
            $pagination = [
                'per_page'      => $data['per_page'] ?? null,
                'next_cursor'   => $data['next_cursor'] ?? null,
                'prev_cursor'   => $data['prev_cursor'] ?? null,
                'next_page_url' => $data['next_page_url'] ?? null,
                'prev_page_url' => $data['prev_page_url'] ?? null,
            ];
        }

        $responseData = $this->apiResponseKit->getSchema()->buildPaginated($items, $pagination);

        return new JsonResponse($responseData, 200);
    }
}
