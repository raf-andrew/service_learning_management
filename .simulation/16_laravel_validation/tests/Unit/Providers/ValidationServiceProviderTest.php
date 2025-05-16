<?php

namespace Tests\Unit\Providers;

use App\Providers\ValidationServiceProvider;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->register(ValidationServiceProvider::class);
    }

    public function test_valid_url_rule_is_registered()
    {
        $this->assertTrue(Validator::hasRule('valid_url'));
    }

    public function test_valid_url_rule_validates_correctly()
    {
        $validator = Validator::make(['url' => 'https://example.com'], ['url' => 'valid_url']);
        $this->assertTrue($validator->passes());

        $validator = Validator::make(['url' => 'not-a-url'], ['url' => 'valid_url']);
        $this->assertTrue($validator->fails());
    }
} 