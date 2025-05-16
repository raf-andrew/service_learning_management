<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PaymentRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('make-payment');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3', Rule::in(['USD', 'EUR', 'GBP'])],
            'payment_method' => ['required', 'string', Rule::in(['credit_card', 'debit_card', 'bank_transfer'])],
            'card_number' => ['required_if:payment_method,credit_card,debit_card', 'string', 'size:16', 'regex:/^[0-9]+$/'],
            'card_holder_name' => ['required_if:payment_method,credit_card,debit_card', 'string', 'max:255'],
            'expiry_month' => ['required_if:payment_method,credit_card,debit_card', 'integer', 'between:1,12'],
            'expiry_year' => ['required_if:payment_method,credit_card,debit_card', 'integer', 'min:' . date('Y'), 'max:' . (date('Y') + 10)],
            'cvv' => ['required_if:payment_method,credit_card,debit_card', 'string', 'size:3', 'regex:/^[0-9]+$/'],
            'billing_address' => ['required', 'array'],
            'billing_address.street' => ['required', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:255'],
            'billing_address.state' => ['required', 'string', 'max:255'],
            'billing_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'size:2', Rule::in(['US', 'GB', 'CA', 'AU'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
            'metadata.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return array_merge(parent::messages(), [
            'amount.min' => 'The payment amount must be at least 0.01.',
            'amount.max' => 'The payment amount cannot exceed 999,999.99.',
            'currency.in' => 'The currency must be USD, EUR, or GBP.',
            'payment_method.in' => 'The payment method must be credit card, debit card, or bank transfer.',
            'card_number.size' => 'The card number must be exactly 16 digits.',
            'card_number.regex' => 'The card number must contain only digits.',
            'expiry_month.between' => 'The expiry month must be between 1 and 12.',
            'expiry_year.min' => 'The expiry year cannot be in the past.',
            'expiry_year.max' => 'The expiry year cannot be more than 10 years in the future.',
            'cvv.size' => 'The CVV must be exactly 3 digits.',
            'cvv.regex' => 'The CVV must contain only digits.',
            'billing_address.country.in' => 'The country must be US, GB, CA, or AU.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), [
            'card_number' => 'card number',
            'card_holder_name' => 'cardholder name',
            'expiry_month' => 'expiry month',
            'expiry_year' => 'expiry year',
            'billing_address.street' => 'street address',
            'billing_address.city' => 'city',
            'billing_address.state' => 'state/province',
            'billing_address.postal_code' => 'postal code',
            'billing_address.country' => 'country',
        ]);
    }

    /**
     * Transform the validated data after validation.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function transformValidatedData(array $validated)
    {
        // Format amount to 2 decimal places
        if (isset($validated['amount'])) {
            $validated['amount'] = round($validated['amount'], 2);
        }

        // Format card number to show only last 4 digits
        if (isset($validated['card_number'])) {
            $validated['card_number'] = '**** **** **** ' . substr($validated['card_number'], -4);
        }

        // Format expiry month to 2 digits
        if (isset($validated['expiry_month'])) {
            $validated['expiry_month'] = str_pad($validated['expiry_month'], 2, '0', STR_PAD_LEFT);
        }

        // Format postal code to uppercase
        if (isset($validated['billing_address']['postal_code'])) {
            $validated['billing_address']['postal_code'] = strtoupper($validated['billing_address']['postal_code']);
        }

        return $validated;
    }
} 