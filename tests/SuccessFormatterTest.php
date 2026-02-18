<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Tests;

use Illuminate\Pagination\LengthAwarePaginator;
use LaraHub\ApiResponseKit\ApiResponseKit;
use LaraHub\ApiResponseKit\Formatters\SuccessFormatter;

class SuccessFormatterTest extends TestCase
{
    private SuccessFormatter $formatter;

    private ApiResponseKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatter = $this->app->make(SuccessFormatter::class);
        $this->kit       = $this->app->make(ApiResponseKit::class);
    }

    // -----------------------------------------------------------------------
    // SuccessFormatter::format()
    // -----------------------------------------------------------------------

    public function test_format_returns_200_by_default(): void
    {
        $response = $this->formatter->format(['id' => 1], 'OK');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_format_honours_custom_status_code(): void
    {
        $response = $this->formatter->format(null, 'Created', 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_format_sets_success_true(): void
    {
        $data = $this->formatter->format(['name' => 'test'], 'OK')->getData(true);

        $this->assertTrue($data['success']);
    }

    public function test_format_includes_message(): void
    {
        $data = $this->formatter->format(null, 'Test message')->getData(true);

        $this->assertSame('Test message', $data['message']);
    }

    public function test_format_embeds_data_payload(): void
    {
        $payload  = ['id' => 42, 'name' => 'item'];
        $data     = $this->formatter->format($payload, 'OK')->getData(true);

        $this->assertSame($payload, $data['data']);
    }

    public function test_format_includes_meta_with_request_id_and_timestamp(): void
    {
        $data = $this->formatter->format(null, 'OK')->getData(true);

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('request_id', $data['meta']);
        $this->assertArrayHasKey('timestamp', $data['meta']);
    }

    public function test_format_merges_additional_meta(): void
    {
        $data = $this->formatter->format(null, 'OK', 200, ['custom_key' => 'custom_value'])->getData(true);

        $this->assertSame('custom_value', $data['meta']['custom_key']);
    }

    public function test_format_null_data_is_preserved(): void
    {
        $data = $this->formatter->format(null, 'OK')->getData(true);

        $this->assertNull($data['data']);
    }

    public function test_get_type_returns_success(): void
    {
        $this->assertSame('success', $this->formatter->getType());
    }

    // -----------------------------------------------------------------------
    // ApiResponseKit::success() and convenience helpers
    // -----------------------------------------------------------------------

    public function test_kit_success_returns_200_with_data(): void
    {
        $response = $this->kit->success(['id' => 1], 'Fetched');
        $data     = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Fetched', $data['message']);
        $this->assertSame(['id' => 1], $data['data']);
    }

    public function test_kit_success_uses_default_message_when_null(): void
    {
        $data = $this->kit->success()->getData(true);

        $this->assertSame('Success', $data['message']);
    }

    public function test_kit_success_with_extra_meta(): void
    {
        $data = $this->kit->success(null, 'OK', 200, ['version' => 'v1'])->getData(true);

        $this->assertSame('v1', $data['meta']['version']);
    }

    public function test_kit_created_returns_201(): void
    {
        $response = $this->kit->created(['id' => 5]);
        $data     = $response->getData(true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Resource created successfully', $data['message']);
    }

    public function test_kit_accepted_returns_202(): void
    {
        $response = $this->kit->accepted();

        $this->assertSame(202, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    public function test_kit_no_content_returns_204(): void
    {
        $response = $this->kit->noContent();

        $this->assertSame(204, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
    }

    // -----------------------------------------------------------------------
    // Data normalisation
    // -----------------------------------------------------------------------

    public function test_kit_success_normalises_illuminate_collection(): void
    {
        $collection = collect([['id' => 1], ['id' => 2]]);
        $data       = $this->kit->success($collection, 'List')->getData(true);

        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data']);
    }

    public function test_kit_success_normalises_arrayable(): void
    {
        // Illuminate Collections implement Arrayable
        $arrayable = collect(['a' => 1, 'b' => 2]);
        $data      = $this->kit->success($arrayable, 'OK')->getData(true);

        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('a', $data['data']);
    }

    // -----------------------------------------------------------------------
    // Paginated responses
    // -----------------------------------------------------------------------

    public function test_kit_paginated_returns_200_with_pagination_meta(): void
    {
        $items     = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 20, 5, 1);

        $response = $this->kit->paginated($paginator, 'Items');
        $data     = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('Items', $data['message']);
        $this->assertArrayHasKey('pagination', $data['meta']);
    }

    public function test_kit_paginated_exposes_correct_page_numbers(): void
    {
        $items     = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 20, 5, 1);

        $pagination = $this->kit->paginated($paginator)->getData(true)['meta']['pagination'];

        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(4, $pagination['last_page']);
        $this->assertSame(20, $pagination['total']);
        $this->assertSame(5, $pagination['per_page']);
    }

    public function test_kit_paginated_data_contains_only_page_items(): void
    {
        $items     = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 20, 5, 1);

        $data = $this->kit->paginated($paginator)->getData(true);

        $this->assertCount(2, $data['data']);
    }

    // -----------------------------------------------------------------------
    // Request ID helpers
    // -----------------------------------------------------------------------

    public function test_kit_per_page_returns_configured_integer(): void
    {
        $this->assertSame(10, $this->kit->perPage());
    }

    public function test_kit_get_request_id_returns_prefixed_string(): void
    {
        $id = $this->kit->getRequestId();

        $this->assertIsString($id);
        $this->assertStringStartsWith('LH-', $id);
    }

    public function test_kit_set_request_id_overrides_the_id(): void
    {
        $this->kit->setRequestId('CUSTOM-999');

        $this->assertSame('CUSTOM-999', $this->kit->getRequestId());
    }

    public function test_request_id_is_consistent_within_same_response(): void
    {
        $response1 = $this->kit->success(null, 'First');
        $response2 = $this->kit->success(null, 'Second');

        // Both calls share the same RequestIdGenerator singleton per app instance.
        $id1 = $response1->getData(true)['meta']['request_id'];
        $id2 = $response2->getData(true)['meta']['request_id'];

        $this->assertSame($id1, $id2);
    }
}
