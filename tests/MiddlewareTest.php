<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Tests;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaraHub\ApiResponseKit\Middleware\ApiResponseKitMiddleware;

/**
 * Tests for ApiResponseKitMiddleware.
 *
 * Each test drives the middleware directly (unit-style) by supplying a fake
 * $next closure, avoiding the need for a real HTTP router.
 */
class MiddlewareTest extends TestCase
{
    private ApiResponseKitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = $this->app->make(ApiResponseKitMiddleware::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeRequest(string $path = '/test'): Request
    {
        return Request::create($path);
    }

    private function makeJsonResponse(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    private function handle(Request $request, JsonResponse|\Closure $responseOrNext): JsonResponse
    {
        $next = $responseOrNext instanceof \Closure
            ? $responseOrNext
            : fn () => $responseOrNext;

        /** @var JsonResponse $result */
        $result = $this->middleware->handle($request, $next);

        return $result;
    }

    // -----------------------------------------------------------------------
    // Pass-through conditions
    // -----------------------------------------------------------------------

    public function test_middleware_passes_through_when_disabled(): void
    {
        $this->app['config']->set('api-response-kit.middleware.enabled', false);

        // Re-resolve so ConfigResolver picks up the new config value.
        $this->middleware = $this->app->make(ApiResponseKitMiddleware::class);

        $raw      = $this->makeJsonResponse(['raw' => true]);
        $response = $this->handle($this->makeRequest(), $raw);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('raw', $data);
        $this->assertArrayNotHasKey('success', $data);
    }

    public function test_middleware_passes_through_excluded_route(): void
    {
        $this->app['config']->set('api-response-kit.middleware.exclude', ['api/health']);
        $this->middleware = $this->app->make(ApiResponseKitMiddleware::class);

        $raw      = $this->makeJsonResponse(['status' => 'ok']);
        $response = $this->handle($this->makeRequest('api/health'), $raw);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('status', $data);
        $this->assertArrayNotHasKey('success', $data);
    }

    public function test_middleware_passes_through_excluded_wildcard_route(): void
    {
        $this->app['config']->set('api-response-kit.middleware.exclude', ['api/webhooks/*']);
        $this->middleware = $this->app->make(ApiResponseKitMiddleware::class);

        $raw      = $this->makeJsonResponse(['event' => 'ping']);
        $response = $this->handle($this->makeRequest('api/webhooks/stripe'), $raw);
        $data     = $response->getData(true);

        $this->assertArrayHasKey('event', $data);
        $this->assertArrayNotHasKey('success', $data);
    }

    public function test_middleware_does_not_reformat_already_structured_response(): void
    {
        $alreadyFormatted = $this->makeJsonResponse([
            'success' => true,
            'message' => 'Already formatted',
            'data'    => ['id' => 1],
            'meta'    => [
                'request_id' => 'TEST-001',
                'timestamp'  => now()->toIso8601ZuluString(),
            ],
        ]);

        $data = $this->handle($this->makeRequest(), $alreadyFormatted)->getData(true);

        // Must pass through unchanged — no double-wrapping.
        $this->assertSame('Already formatted', $data['message']);
        $this->assertSame('TEST-001', $data['meta']['request_id']);
    }

    // -----------------------------------------------------------------------
    // Success responses (2xx)
    // -----------------------------------------------------------------------

    public function test_middleware_wraps_plain_json_into_standard_success_structure(): void
    {
        $raw  = $this->makeJsonResponse(['user' => 'John']);
        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('request_id', $data['meta']);
    }

    public function test_middleware_preserves_2xx_status_on_success_response(): void
    {
        $raw      = $this->makeJsonResponse(['id' => 1], 201);
        $response = $this->handle($this->makeRequest(), $raw);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_middleware_formats_paginated_payload(): void
    {
        $raw = $this->makeJsonResponse([
            'current_page' => 1,
            'data'         => [['id' => 1], ['id' => 2]],
            'last_page'    => 3,
            'per_page'     => 2,
            'total'        => 6,
            'from'         => 1,
            'to'           => 2,
            'next_page_url' => 'http://example.com?page=2',
            'prev_page_url' => null,
        ]);

        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertSame(1, $data['meta']['pagination']['current_page']);
        $this->assertSame(6, $data['meta']['pagination']['total']);
        $this->assertCount(2, $data['data']);
    }

    public function test_middleware_formats_cursor_paginated_payload(): void
    {
        $raw = $this->makeJsonResponse([
            'data'         => [['id' => 1]],
            'per_page'     => 15,
            'next_cursor'  => 'abc123',
            'prev_cursor'  => null,
            'next_page_url' => 'http://example.com?cursor=abc123',
            'prev_page_url' => null,
        ]);

        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('pagination', $data['meta']);
        $this->assertSame('abc123', $data['meta']['pagination']['next_cursor']);
    }

    // -----------------------------------------------------------------------
    // Error responses (4xx / 5xx)
    // -----------------------------------------------------------------------

    public function test_middleware_wraps_error_response_with_success_false(): void
    {
        $raw  = $this->makeJsonResponse(['message' => 'Internal server error'], 500);
        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function test_middleware_extracts_message_from_error_payload(): void
    {
        $raw  = $this->makeJsonResponse(['message' => 'Resource not found'], 404);
        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertSame(404, $this->handle($this->makeRequest(), $raw)->getStatusCode());
        $this->assertSame('Resource not found', $data['message']);
    }

    public function test_middleware_falls_back_to_config_status_message_when_no_message_key(): void
    {
        $raw  = $this->makeJsonResponse(['code' => 'ERR_404'], 404);
        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        // Config status_messages[404] = 'Not Found'
        $this->assertSame('Not Found', $data['message']);
    }

    // -----------------------------------------------------------------------
    // 422 Validation error detection
    // -----------------------------------------------------------------------

    public function test_middleware_detects_and_formats_validation_error_response(): void
    {
        $raw = $this->makeJsonResponse([
            'message' => 'The given data was invalid.',
            'errors'  => [
                'email' => ['The email field is required.'],
                'name'  => ['The name field is required.'],
            ],
        ], 422);

        $response = $this->handle($this->makeRequest(), $raw);
        $data     = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('The given data was invalid.', $data['message']);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertArrayHasKey('name', $data['errors']);
    }

    public function test_middleware_uses_default_validation_message_when_absent(): void
    {
        $raw = $this->makeJsonResponse([
            'errors' => ['field' => ['error']],
        ], 422);

        $data = $this->handle($this->makeRequest(), $raw)->getData(true);

        $this->assertSame('Validation failed', $data['message']);
    }

    // -----------------------------------------------------------------------
    // Exception handling
    // -----------------------------------------------------------------------

    public function test_middleware_catches_exceptions_and_returns_json_error(): void
    {
        $response = $this->handle($this->makeRequest(), function () {
            throw new Exception('Boom!');
        });

        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function test_middleware_exception_response_has_500_status(): void
    {
        $response = $this->handle($this->makeRequest(), function () {
            throw new \RuntimeException('Server error');
        });

        $this->assertSame(500, $response->getStatusCode());
    }
}
