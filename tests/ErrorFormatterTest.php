<?php

declare(strict_types=1);

namespace LaraHub\ApiResponseKit\Tests;

use Exception;
use Illuminate\Validation\ValidationException;
use LaraHub\ApiResponseKit\ApiResponseKit;
use LaraHub\ApiResponseKit\Formatters\ErrorFormatter;
use LaraHub\ApiResponseKit\Formatters\ExceptionFormatter;
use LaraHub\ApiResponseKit\Formatters\ValidationFormatter;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorFormatterTest extends TestCase
{
    private ErrorFormatter $errorFormatter;

    private ValidationFormatter $validationFormatter;

    private ExceptionFormatter $exceptionFormatter;

    private ApiResponseKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorFormatter      = $this->app->make(ErrorFormatter::class);
        $this->validationFormatter = $this->app->make(ValidationFormatter::class);
        $this->exceptionFormatter  = $this->app->make(ExceptionFormatter::class);
        $this->kit                 = $this->app->make(ApiResponseKit::class);
    }

    // -----------------------------------------------------------------------
    // ErrorFormatter
    // -----------------------------------------------------------------------

    public function test_error_format_uses_provided_status_code(): void
    {
        $this->assertSame(404, $this->errorFormatter->format(null, 'Not found', 404)->getStatusCode());
    }

    public function test_error_format_defaults_to_500(): void
    {
        $this->assertSame(500, $this->errorFormatter->format(null, 'Oops')->getStatusCode());
    }

    public function test_error_format_sets_success_false(): void
    {
        $data = $this->errorFormatter->format(null, 'Error', 500)->getData(true);

        $this->assertFalse($data['success']);
    }

    public function test_error_format_embeds_message(): void
    {
        $data = $this->errorFormatter->format(null, 'Something went wrong', 500)->getData(true);

        $this->assertSame('Something went wrong', $data['message']);
    }

    public function test_error_format_sets_errors_null_when_no_errors_given(): void
    {
        $data = $this->errorFormatter->format(null, 'Not Found', 404)->getData(true);

        $this->assertNull($data['errors']);
    }

    public function test_error_format_with_errors_embeds_errors(): void
    {
        $errors = ['field' => 'Invalid value'];
        $data   = $this->errorFormatter->formatWithErrors('Error', $errors, 400)->getData(true);

        $this->assertSame($errors, $data['errors']);
    }

    public function test_error_format_includes_meta(): void
    {
        $data = $this->errorFormatter->format(null, 'Error', 500)->getData(true);

        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('request_id', $data['meta']);
        $this->assertArrayHasKey('timestamp', $data['meta']);
    }

    public function test_error_format_merges_additional_meta(): void
    {
        $data = $this->errorFormatter->format(null, 'Err', 500, ['trace_id' => 'abc'])->getData(true);

        $this->assertSame('abc', $data['meta']['trace_id']);
    }

    public function test_error_get_type_returns_error(): void
    {
        $this->assertSame('error', $this->errorFormatter->getType());
    }

    // -----------------------------------------------------------------------
    // ValidationFormatter
    // -----------------------------------------------------------------------

    public function test_validation_format_returns_422(): void
    {
        $response = $this->validationFormatter->format(['name' => ['Required']], 'Validation failed');

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_validation_format_sets_success_false(): void
    {
        $data = $this->validationFormatter->format([], 'Validation failed')->getData(true);

        $this->assertFalse($data['success']);
    }

    public function test_validation_format_flattens_field_errors_to_first_message(): void
    {
        $errors = [
            'email' => ['The email field is required.', 'Must be a valid email.'],
            'name'  => ['The name field is required.'],
        ];

        $data = $this->validationFormatter->format($errors, 'Validation failed')->getData(true);

        $this->assertSame('The email field is required.', $data['errors']['email']);
        $this->assertSame('The name field is required.', $data['errors']['name']);
    }

    public function test_validation_format_errors_uses_default_message(): void
    {
        $data = $this->validationFormatter->formatErrors(['name' => ['Required']])->getData(true);

        $this->assertSame('Validation failed', $data['message']);
    }

    public function test_validation_format_errors_accepts_custom_message(): void
    {
        $data = $this->validationFormatter->formatErrors(
            ['name' => ['Required']],
            'Custom validation message'
        )->getData(true);

        $this->assertSame('Custom validation message', $data['message']);
    }

    public function test_validation_format_exception_returns_422(): void
    {
        $validator = $this->app['validator']->make([], ['name' => 'required']);
        $exception = new ValidationException($validator);

        $response = $this->validationFormatter->formatException($exception);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_validation_format_exception_includes_field_errors(): void
    {
        $validator = $this->app['validator']->make(
            [],
            ['name' => 'required', 'email' => 'required|email']
        );
        $exception = new ValidationException($validator);

        $data = $this->validationFormatter->formatException($exception)->getData(true);

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('name', $data['errors']);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    public function test_validation_set_default_message_changes_fallback(): void
    {
        $this->validationFormatter->setDefaultMessage('Custom default');
        $data = $this->validationFormatter->formatErrors(['x' => ['err']])->getData(true);

        $this->assertSame('Custom default', $data['message']);
    }

    public function test_validation_get_type_returns_validation(): void
    {
        $this->assertSame('validation', $this->validationFormatter->getType());
    }

    // -----------------------------------------------------------------------
    // ExceptionFormatter
    // -----------------------------------------------------------------------

    public function test_exception_formatter_handles_generic_exception_with_500(): void
    {
        $response = $this->exceptionFormatter->formatException(new Exception('Something broke'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_exception_formatter_includes_debug_details_when_debug_is_on(): void
    {
        // Debug mode is enabled via TestCase::getEnvironmentSetUp.
        $data = $this->exceptionFormatter
            ->formatException(new RuntimeException('Debug exception'))
            ->getData(true);

        $this->assertNotNull($data['errors']);
        $this->assertArrayHasKey('exception', $data['errors']);
        $this->assertArrayHasKey('message', $data['errors']);
        $this->assertArrayHasKey('file', $data['errors']);
        $this->assertArrayHasKey('line', $data['errors']);
        $this->assertArrayHasKey('trace', $data['errors']);
    }

    public function test_exception_formatter_hides_details_when_debug_is_off(): void
    {
        $this->app['config']->set('api-response-kit.debug.show_trace', false);

        $data = $this->exceptionFormatter
            ->formatException(new RuntimeException('Secret error'))
            ->getData(true);

        $this->assertSame('An unexpected error occurred', $data['message']);
        $this->assertNull($data['errors']);
    }

    public function test_exception_formatter_uses_http_status_from_http_exception(): void
    {
        $exception = new NotFoundHttpException('Not Found');
        $response  = $this->exceptionFormatter->formatException($exception);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_exception_formatter_uses_http_exception_message(): void
    {
        $exception = new HttpException(403, 'Custom forbidden message');
        $data      = $this->exceptionFormatter->formatException($exception)->getData(true);

        $this->assertSame('Custom forbidden message', $data['message']);
    }

    public function test_exception_formatter_delegates_validation_exception_to_validation_formatter(): void
    {
        $validator = $this->app['validator']->make([], ['name' => 'required']);
        $exception = new ValidationException($validator);

        $response = $this->exceptionFormatter->formatException($exception);
        $data     = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('name', $data['errors']);
    }

    public function test_exception_formatter_respects_message_override_from_config(): void
    {
        $this->app['config']->set(
            'api-response-kit.exceptions.messages',
            [RuntimeException::class => 'Overridden runtime message']
        );

        $data = $this->exceptionFormatter
            ->formatException(new RuntimeException('Original message'))
            ->getData(true);

        $this->assertSame('Overridden runtime message', $data['message']);
    }

    public function test_exception_formatter_limits_trace_frames(): void
    {
        $this->app['config']->set('api-response-kit.exceptions.max_trace_frames', 2);

        $data = $this->exceptionFormatter
            ->formatException(new RuntimeException('Trace test'))
            ->getData(true);

        $this->assertNotNull($data['errors']);
        $this->assertLessThanOrEqual(2, count($data['errors']['trace']));
    }

    public function test_exception_format_with_throwable_data_delegates(): void
    {
        $exception = new Exception('via format()');
        $response  = $this->exceptionFormatter->format($exception, 'ignored', 500);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_exception_get_type_returns_exception(): void
    {
        $this->assertSame('exception', $this->exceptionFormatter->getType());
    }

    // -----------------------------------------------------------------------
    // ApiResponseKit convenience error methods
    // -----------------------------------------------------------------------

    public function test_kit_error_returns_correct_status_and_structure(): void
    {
        $errors   = ['detail' => 'Missing required field'];
        $response = $this->kit->error('Request failed', $errors, 400);
        $data     = $response->getData(true);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('Request failed', $data['message']);
        $this->assertSame($errors, $data['errors']);
    }

    public function test_kit_bad_request_returns_400(): void
    {
        $response = $this->kit->badRequest();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_kit_unauthorized_returns_401(): void
    {
        $this->assertSame(401, $this->kit->unauthorized()->getStatusCode());
    }

    public function test_kit_forbidden_returns_403(): void
    {
        $this->assertSame(403, $this->kit->forbidden()->getStatusCode());
    }

    public function test_kit_not_found_returns_404(): void
    {
        $response = $this->kit->notFound();
        $data     = $response->getData(true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Resource not found', $data['message']);
    }

    public function test_kit_method_not_allowed_returns_405(): void
    {
        $this->assertSame(405, $this->kit->methodNotAllowed()->getStatusCode());
    }

    public function test_kit_conflict_returns_409(): void
    {
        $this->assertSame(409, $this->kit->conflict()->getStatusCode());
    }

    public function test_kit_unprocessable_entity_returns_422(): void
    {
        $this->assertSame(422, $this->kit->unprocessableEntity()->getStatusCode());
    }

    public function test_kit_too_many_requests_returns_429(): void
    {
        $this->assertSame(429, $this->kit->tooManyRequests()->getStatusCode());
    }

    public function test_kit_server_error_returns_500(): void
    {
        $response = $this->kit->serverError();
        $data     = $response->getData(true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    public function test_kit_service_unavailable_returns_503(): void
    {
        $this->assertSame(503, $this->kit->serviceUnavailable()->getStatusCode());
    }

    public function test_kit_validation_error_returns_422_with_formatted_errors(): void
    {
        $errors   = ['email' => ['Required'], 'name' => ['Required']];
        $response = $this->kit->validationError($errors);
        $data     = $response->getData(true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertSame('Validation failed', $data['message']);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertArrayHasKey('name', $data['errors']);
    }

    public function test_kit_validation_error_accepts_custom_message(): void
    {
        $data = $this->kit->validationError(
            ['field' => ['error']],
            'Please fix the form'
        )->getData(true);

        $this->assertSame('Please fix the form', $data['message']);
    }

    public function test_kit_exception_returns_formatted_response_for_throwable(): void
    {
        $response = $this->kit->exception(new RuntimeException('Oops'));
        $data     = $response->getData(true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('meta', $data);
    }
}
