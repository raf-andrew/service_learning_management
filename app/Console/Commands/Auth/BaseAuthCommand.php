<?php

namespace App\Console\Commands\Auth;

use Illuminate\Console\Command;
use App\Services\Auth\AuthService;

abstract class BaseAuthCommand extends Command
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        parent::__construct();
        $this->authService = $authService;
    }

    protected function validateAuthConfig()
    {
        if (!config('auth.enabled', false)) {
            $this->error('Authentication system is not enabled');
            return false;
        }
        return true;
    }
} 