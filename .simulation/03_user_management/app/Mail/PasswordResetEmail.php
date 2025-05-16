<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->token = Password::createToken($user);
    }

    public function build()
    {
        return $this->markdown('emails.reset-password')
                    ->subject('Reset Your Password')
                    ->with([
                        'resetUrl' => url("/reset-password?token={$this->token}&email=" . urlencode($this->user->email)),
                        'user' => $this->user
                    ]);
    }
} 