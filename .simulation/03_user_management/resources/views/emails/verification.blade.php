@component('mail::message')
# Verify Your Email Address

Hello {{ $user->name }},

Thank you for registering! Please verify your email address by clicking the button below:

@component('mail::button', ['url' => $verificationUrl])
Verify Email Address
@endcomponent

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 