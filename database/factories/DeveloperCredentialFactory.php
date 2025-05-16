<?php

namespace Database\Factories;

use App\Models\DeveloperCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeveloperCredentialFactory extends Factory
{
    protected $model = DeveloperCredential::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'github_token' => $this->faker->uuid,
            'github_username' => $this->faker->userName,
            'is_active' => true,
            'last_used_at' => now(),
            'expires_at' => now()->addYear(),
            'permissions' => [
                'codespaces' => true,
                'repositories' => true,
                'workflows' => true
            ]
        ];
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => now()->subDay()
            ];
        });
    }

    public function withLimitedPermissions()
    {
        return $this->state(function (array $attributes) {
            return [
                'permissions' => [
                    'codespaces' => false,
                    'repositories' => true,
                    'workflows' => false
                ]
            ];
        });
    }
} 