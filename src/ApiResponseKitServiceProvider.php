<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use LaraHub\ApiResponseKit\Contracts\ResponseFormatterInterface;
use LaraHub\ApiResponseKit\Formatters\ErrorFormatter;
use LaraHub\ApiResponseKit\Formatters\ExceptionFormatter;
use LaraHub\ApiResponseKit\Formatters\SuccessFormatter;
use LaraHub\ApiResponseKit\Formatters\ValidationFormatter;
use LaraHub\ApiResponseKit\Middleware\ApiResponseKitMiddleware;
use LaraHub\ApiResponseKit\Support\ConfigResolver;
use LaraHub\ApiResponseKit\Support\RequestIdGenerator;
use LaraHub\ApiResponseKit\Support\ResponseSchema;

/**
 * Service Provider for LaraHub API Response Kit.
 *
 * Registers package services, publishes configuration,
 * and binds the ApiResponseKit service into the Laravel container.
 */
class ApiResponseKitServiceProvider extends ServiceProvider
{
    /**
     * Resolve an overridable formatter class from config.
     *
     * For safety/backwards-compatibility, overrides must extend the built-in formatter class.
     *
     * @param string $type success|error|validation|exception
     * @param class-string $baseClass
     * @return class-string
     */
    protected function resolveFormatterClass(string $type, string $baseClass): string
    {
        /** @var ConfigResolver $config */
        $config = $this->app->make(ConfigResolver::class);
        $override = $config->getFormatterForType($type);

        if (is_string($override) && $override !== '' && class_exists($override) && is_a($override, $baseClass, true)) {
            return $override;
        }

        return $baseClass;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-response-kit.php',
            'api-response-kit'
        );

        // Register ConfigResolver as singleton
        $this->app->singleton(ConfigResolver::class, function () {
            return new ConfigResolver();
        });

        // Register RequestIdGenerator as singleton (per request)
        $this->app->singleton(RequestIdGenerator::class, function ($app) {
            $config = $app->make(ConfigResolver::class);

            return new RequestIdGenerator($config->getRequestIdPrefix());
        });

        // Register ResponseSchema
        $this->app->singleton(ResponseSchema::class, function ($app) {
            return new ResponseSchema(
                $app->make(ConfigResolver::class),
                $app->make(RequestIdGenerator::class)
            );
        });

        // Register Formatters
        $this->app->singleton(SuccessFormatter::class, function ($app) {
            $class = $this->resolveFormatterClass('success', SuccessFormatter::class);

            if ($class === SuccessFormatter::class) {
                return new SuccessFormatter($app->make(ResponseSchema::class));
            }

            return $app->make($class);
        });

        $this->app->singleton(ErrorFormatter::class, function ($app) {
            $class = $this->resolveFormatterClass('error', ErrorFormatter::class);

            if ($class === ErrorFormatter::class) {
                return new ErrorFormatter($app->make(ResponseSchema::class));
            }

            return $app->make($class);
        });

        $this->app->singleton(ValidationFormatter::class, function ($app) {
            $class = $this->resolveFormatterClass('validation', ValidationFormatter::class);

            if ($class === ValidationFormatter::class) {
                return new ValidationFormatter($app->make(ResponseSchema::class));
            }

            return $app->make($class);
        });

        $this->app->singleton(ExceptionFormatter::class, function ($app) {
            $class = $this->resolveFormatterClass('exception', ExceptionFormatter::class);

            if ($class === ExceptionFormatter::class) {
                return new ExceptionFormatter(
                    $app->make(ResponseSchema::class),
                    $app->make(ConfigResolver::class),
                    $app->make(ValidationFormatter::class)
                );
            }

            return $app->make($class);
        });

        // Register main ApiResponseKit class
        $this->app->singleton(ApiResponseKit::class, function ($app) {
            return new ApiResponseKit(
                $app->make(ConfigResolver::class),
                $app->make(RequestIdGenerator::class),
                $app->make(ResponseSchema::class),
                $app->make(SuccessFormatter::class),
                $app->make(ErrorFormatter::class),
                $app->make(ValidationFormatter::class),
                $app->make(ExceptionFormatter::class)
            );
        });

        // Register alias for the facade
        $this->app->alias(ApiResponseKit::class, 'api-response-kit');

        // Register ResponseFormatterInterface binding
        $this->app->bind(ResponseFormatterInterface::class, function ($app) {
            $config = $app->make(ConfigResolver::class);
            $formatterClass = $config->getDefaultFormatter();

            if ($formatterClass && class_exists($formatterClass)) {
                return $app->make($formatterClass);
            }

            return $app->make(SuccessFormatter::class);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/api-response-kit.php' => config_path('api-response-kit.php'),
            ], 'api-response-kit-config');
        }

        // Auto-register middleware (configurable)
        if (config('api-response-kit.auto_middleware', true)) {
            $this->registerApiMiddleware();
        }
    }

    /**
     * Register the middleware (backwards compatible wrapper).
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $this->registerApiMiddleware();
    }

    /**
     * Auto-register the package middleware.
     *
     * Registers an alias (`api-response-kit`) and optionally pushes it
     * to the global middleware stack based on config.
     *
     * @return void
     */
    protected function registerApiMiddleware(): void
    {
        /** @var ConfigResolver $config */
        $config = $this->app->make(ConfigResolver::class);

        if (!$config->isMiddlewareEnabled()) {
            return;
        }

        // Register middleware alias
        $router = $this->app['router'];
        $router->aliasMiddleware('api-response-kit', ApiResponseKitMiddleware::class);

        // Optionally push to global middleware stack
        if ($config->get('middleware.global', false)) {
            $kernel = $this->app->make(Kernel::class);
            $kernel->pushMiddleware(ApiResponseKitMiddleware::class);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ApiResponseKit::class,
            ConfigResolver::class,
            RequestIdGenerator::class,
            ResponseSchema::class,
            SuccessFormatter::class,
            ErrorFormatter::class,
            ValidationFormatter::class,
            ExceptionFormatter::class,
            ResponseFormatterInterface::class,
            'api-response-kit',
        ];
    }
}
