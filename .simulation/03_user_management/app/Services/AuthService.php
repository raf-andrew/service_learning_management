<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
use App\Mail\PasswordResetEmail;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => Str::random(60),
        ]);

        event(new Registered($user));

        return $user;
    }

    public function login(string $email, string $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        if (!$user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Please verify your email first'
            ];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'token' => $token,
            'user' => $user
        ];
    }

    public function logout(User $user)
    {
        $user->tokens()->delete();
    }

    public function sendPasswordResetLink(string $email)
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return true;
        }

        return false;
    }

    public function resetPassword(string $email, string $password, string $token)
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'token' => $token
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'success' => true,
                'message' => 'Password reset successful'
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid or expired reset token'
        ];
    }

    public function verifyEmail(int $id, string $hash)
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        if ($user->hasVerifiedEmail()) {
            return [
                'success' => false,
                'message' => 'Email already verified'
            ];
        }

        if ($user->email_verification_token !== $hash) {
            return [
                'success' => false,
                'message' => 'Invalid verification token'
            ];
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return [
            'success' => true,
            'message' => 'Email verified successfully'
        ];
    }

    public function resendVerificationEmail(string $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return false;
        }

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->email_verification_token = Str::random(60);
        $user->save();

        Mail::to($user->email)->send(new VerificationEmail($user));

        return true;
    }
} 