<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Tests;

use LaraHub\ApiResponseKit\ApiResponseKitServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * Register package service providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            ApiResponseKitServiceProvider::class,
        ];
    }

    /**
     * Register package aliases / facades.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'ApiResponseKit' => \LaraHub\ApiResponseKit\Facades\ApiResponseKit::class,
        ];
    }

    /**
     * Configure the test environment.
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.debug', true);

        // Enable debug output so exception details appear in responses by default.
        $app['config']->set('api-response-kit.debug.show_trace', true);
        $app['config']->set('api-response-kit.debug.hide_sql_errors', false);
    }
}
