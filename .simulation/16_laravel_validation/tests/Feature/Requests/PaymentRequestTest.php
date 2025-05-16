<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRequest $request;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new PaymentRequest();
        $this->user = User::factory()->create();
    }

    public function test_authorization_is_allowed_for_authorized_user()
    {
        $this->user->givePermissionTo('make-payment');
        $this->actingAs($this->user);
        $this->assertTrue($this->request->authorize());
    }

    public function test_authorization_is_denied_for_unauthorized_user()
    {
        $this->actingAs($this->user);
        $this->assertFalse($this->request->authorize());
    }

    public function test_validation_rules_are_correct()
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('amount', $rules);
        $this->assertArrayHasKey('currency', $rules);
        $this->assertArrayHasKey('payment_method', $rules);
        $this->assertArrayHasKey('card_number', $rules);
        $this->assertArrayHasKey('card_holder_name', $rules);
        $this->assertArrayHasKey('expiry_month', $rules);
        $this->assertArrayHasKey('expiry_year', $rules);
        $this->assertArrayHasKey('cvv', $rules);
        $this->assertArrayHasKey('billing_address', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('metadata', $rules);

        $this->assertContains('required', $rules['amount']);
        $this->assertContains('numeric', $rules['amount']);
        $this->assertContains('min:0.01', $rules['amount']);
        $this->assertContains('max:999999.99', $rules['amount']);

        $this->assertContains('required', $rules['currency']);
        $this->assertContains('string', $rules['currency']);
        $this->assertContains('size:3', $rules['currency']);

        $this->assertContains('required', $rules['payment_method']);
        $this->assertContains('string', $rules['payment_method']);

        $this->assertContains('required_if:payment_method,credit_card,debit_card', $rules['card_number']);
        $this->assertContains('string', $rules['card_number']);
        $this->assertContains('size:16', $rules['card_number']);
        $this->assertContains('regex:/^[0-9]+$/', $rules['card_number']);

        $this->assertContains('required_if:payment_method,credit_card,debit_card', $rules['card_holder_name']);
        $this->assertContains('string', $rules['card_holder_name']);
        $this->assertContains('max:255', $rules['card_holder_name']);

        $this->assertContains('required_if:payment_method,credit_card,debit_card', $rules['expiry_month']);
        $this->assertContains('integer', $rules['expiry_month']);
        $this->assertContains('between:1,12', $rules['expiry_month']);

        $this->assertContains('required_if:payment_method,credit_card,debit_card', $rules['expiry_year']);
        $this->assertContains('integer', $rules['expiry_year']);

        $this->assertContains('required_if:payment_method,credit_card,debit_card', $rules['cvv']);
        $this->assertContains('string', $rules['cvv']);
        $this->assertContains('size:3', $rules['cvv']);
        $this->assertContains('regex:/^[0-9]+$/', $rules['cvv']);

        $this->assertContains('required', $rules['billing_address']);
        $this->assertContains('array', $rules['billing_address']);

        $this->assertContains('nullable', $rules['description']);
        $this->assertContains('string', $rules['description']);
        $this->assertContains('max:1000', $rules['description']);

        $this->assertContains('nullable', $rules['metadata']);
        $this->assertContains('array', $rules['metadata']);
    }

    public function test_custom_messages_are_correct()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('amount.min', $messages);
        $this->assertArrayHasKey('amount.max', $messages);
        $this->assertArrayHasKey('currency.in', $messages);
        $this->assertArrayHasKey('payment_method.in', $messages);
        $this->assertArrayHasKey('card_number.size', $messages);
        $this->assertArrayHasKey('card_number.regex', $messages);
        $this->assertArrayHasKey('expiry_month.between', $messages);
        $this->assertArrayHasKey('expiry_year.min', $messages);
        $this->assertArrayHasKey('expiry_year.max', $messages);
        $this->assertArrayHasKey('cvv.size', $messages);
        $this->assertArrayHasKey('cvv.regex', $messages);
        $this->assertArrayHasKey('billing_address.country.in', $messages);

        $this->assertEquals('The payment amount must be at least 0.01.', $messages['amount.min']);
        $this->assertEquals('The payment amount cannot exceed 999,999.99.', $messages['amount.max']);
        $this->assertEquals('The currency must be USD, EUR, or GBP.', $messages['currency.in']);
        $this->assertEquals('The payment method must be credit card, debit card, or bank transfer.', $messages['payment_method.in']);
        $this->assertEquals('The card number must be exactly 16 digits.', $messages['card_number.size']);
        $this->assertEquals('The card number must contain only digits.', $messages['card_number.regex']);
        $this->assertEquals('The expiry month must be between 1 and 12.', $messages['expiry_month.between']);
        $this->assertEquals('The expiry year cannot be in the past.', $messages['expiry_year.min']);
        $this->assertEquals('The expiry year cannot be more than 10 years in the future.', $messages['expiry_year.max']);
        $this->assertEquals('The CVV must be exactly 3 digits.', $messages['cvv.size']);
        $this->assertEquals('The CVV must contain only digits.', $messages['cvv.regex']);
        $this->assertEquals('The country must be US, GB, CA, or AU.', $messages['billing_address.country.in']);
    }

    public function test_custom_attributes_are_correct()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('card_number', $attributes);
        $this->assertArrayHasKey('card_holder_name', $attributes);
        $this->assertArrayHasKey('expiry_month', $attributes);
        $this->assertArrayHasKey('expiry_year', $attributes);
        $this->assertArrayHasKey('billing_address.street', $attributes);
        $this->assertArrayHasKey('billing_address.city', $attributes);
        $this->assertArrayHasKey('billing_address.state', $attributes);
        $this->assertArrayHasKey('billing_address.postal_code', $attributes);
        $this->assertArrayHasKey('billing_address.country', $attributes);

        $this->assertEquals('card number', $attributes['card_number']);
        $this->assertEquals('cardholder name', $attributes['card_holder_name']);
        $this->assertEquals('expiry month', $attributes['expiry_month']);
        $this->assertEquals('expiry year', $attributes['expiry_year']);
        $this->assertEquals('street address', $attributes['billing_address.street']);
        $this->assertEquals('city', $attributes['billing_address.city']);
        $this->assertEquals('state/province', $attributes['billing_address.state']);
        $this->assertEquals('postal code', $attributes['billing_address.postal_code']);
        $this->assertEquals('country', $attributes['billing_address.country']);
    }

    public function test_validates_valid_credit_card_data()
    {
        $validator = Validator::make([
            'amount' => 99.99,
            'currency' => 'USD',
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
            'card_holder_name' => 'John Doe',
            'expiry_month' => 12,
            'expiry_year' => date('Y') + 1,
            'cvv' => '123',
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'description' => 'Test payment',
            'metadata' => [
                'order_id' => '12345',
            ],
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_valid_bank_transfer_data()
    {
        $validator = Validator::make([
            'amount' => 99.99,
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'description' => 'Test payment',
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_invalid_data()
    {
        $validator = Validator::make([
            'amount' => -1,
            'currency' => 'INVALID',
            'payment_method' => 'invalid',
            'card_number' => '1234',
            'card_holder_name' => '',
            'expiry_month' => 13,
            'expiry_year' => date('Y') - 1,
            'cvv' => '12',
            'billing_address' => [
                'street' => '',
                'city' => '',
                'state' => '',
                'postal_code' => '',
                'country' => 'XX',
            ],
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_method', $validator->errors()->toArray());
        $this->assertArrayHasKey('card_number', $validator->errors()->toArray());
        $this->assertArrayHasKey('card_holder_name', $validator->errors()->toArray());
        $this->assertArrayHasKey('expiry_month', $validator->errors()->toArray());
        $this->assertArrayHasKey('expiry_year', $validator->errors()->toArray());
        $this->assertArrayHasKey('cvv', $validator->errors()->toArray());
        $this->assertArrayHasKey('billing_address.street', $validator->errors()->toArray());
        $this->assertArrayHasKey('billing_address.city', $validator->errors()->toArray());
        $this->assertArrayHasKey('billing_address.state', $validator->errors()->toArray());
        $this->assertArrayHasKey('billing_address.postal_code', $validator->errors()->toArray());
        $this->assertArrayHasKey('billing_address.country', $validator->errors()->toArray());
    }

    public function test_transforms_validated_data()
    {
        $validated = [
            'amount' => 99.999,
            'currency' => 'USD',
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
            'card_holder_name' => 'John Doe',
            'expiry_month' => 1,
            'expiry_year' => date('Y') + 1,
            'cvv' => '123',
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ];

        $transformed = $this->request->transformValidatedData($validated);

        $this->assertEquals(100.00, $transformed['amount']);
        $this->assertEquals('**** **** **** 1111', $transformed['card_number']);
        $this->assertEquals('01', $transformed['expiry_month']);
        $this->assertEquals('10001', $transformed['billing_address']['postal_code']);
    }
} 