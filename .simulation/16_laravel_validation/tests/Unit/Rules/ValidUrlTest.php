<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidUrlTest extends TestCase
{
    private ValidUrl $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ValidUrl();
    }

    public function test_validates_valid_url()
    {
        $validator = Validator::make(['url' => 'https://example.com'], ['url' => $this->rule]);
        $this->assertTrue($validator->passes());
    }

    public function test_validates_invalid_url()
    {
        $validator = Validator::make(['url' => 'not-a-url'], ['url' => $this->rule]);
        $this->assertTrue($validator->fails());
    }

    public function test_returns_correct_error_message()
    {
        $validator = Validator::make(['url' => 'not-a-url'], ['url' => $this->rule]);
        $this->assertEquals('The url must be a valid URL.', $validator->errors()->first('url'));
    }
} 