<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BaseFormRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the base form request authorizes by default.
     */
    public function test_base_form_request_authorizes_by_default()
    {
        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required'];
            }
        };

        $this->assertTrue($request->authorize());
    }

    /**
     * Test that the base form request provides default validation messages.
     */
    public function test_base_form_request_provides_default_validation_messages()
    {
        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required'];
            }
        };

        $messages = $request->messages();
        
        $this->assertIsArray($messages);
        $this->assertArrayHasKey('required', $messages);
        $this->assertArrayHasKey('email', $messages);
        $this->assertArrayHasKey('min', $messages);
        $this->assertArrayHasKey('max', $messages);
    }

    /**
     * Test that the base form request provides default attributes.
     */
    public function test_base_form_request_provides_default_attributes()
    {
        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required'];
            }
        };

        $attributes = $request->attributes();
        
        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('password', $attributes);
        $this->assertArrayHasKey('name', $attributes);
    }

    /**
     * Test that the base form request logs validation errors.
     */
    public function test_base_form_request_logs_validation_errors()
    {
        Log::spy();

        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required'];
            }
        };

        try {
            $request->validateResolved();
        } catch (ValidationException $e) {
            // Expected exception
        }

        Log::shouldHaveReceived('warning')
            ->with('Validation failed', \Mockery::on(function ($args) {
                return is_array($args) &&
                    isset($args['request']) &&
                    isset($args['errors']) &&
                    isset($args['user_id']) &&
                    isset($args['ip']);
            }));
    }

    /**
     * Test that the base form request trims string inputs.
     */
    public function test_base_form_request_trims_string_inputs()
    {
        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required|string'];
            }
        };

        $request->merge(['test' => '  trimmed  ']);
        $request->validateResolved();

        $this->assertEquals('trimmed', $request->input('test'));
    }

    /**
     * Test that the base form request allows data transformation.
     */
    public function test_base_form_request_allows_data_transformation()
    {
        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required|string'];
            }

            protected function transformValidatedData(array $validated)
            {
                $validated['test'] = strtoupper($validated['test']);
                return $validated;
            }
        };

        $request->merge(['test' => 'transformed']);
        $validated = $request->validated();

        $this->assertEquals('TRANSFORMED', $validated['test']);
    }

    /**
     * Test that the base form request throws validation exception on failure.
     */
    public function test_base_form_request_throws_validation_exception_on_failure()
    {
        $this->expectException(ValidationException::class);

        $request = new class extends BaseFormRequest {
            public function rules()
            {
                return ['test' => 'required'];
            }
        };

        $request->validateResolved();
    }
} 