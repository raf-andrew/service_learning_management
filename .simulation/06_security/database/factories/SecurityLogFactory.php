<?php

namespace Database\Factories;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SecurityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SecurityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_type' => $this->faker->randomElement(SecurityLog::EVENT_TYPES),
            'severity' => $this->faker->randomElement(SecurityLog::SEVERITY_LEVELS),
            'description' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'user_id' => User::factory(),
            'metadata' => [
                'attempt_count' => $this->faker->numberBetween(1, 10),
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari']),
                'os' => $this->faker->randomElement(['Windows', 'MacOS', 'Linux']),
            ],
            'status' => $this->faker->randomElement(['pending', 'alerted', 'resolved']),
        ];
    }

    /**
     * Indicate that the log is for a login attempt.
     */
    public function loginAttempt(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'login_attempt',
                'description' => 'User attempted to log in',
            ];
        });
    }

    /**
     * Indicate that the log is for an API access.
     */
    public function apiAccess(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'event_type' => 'api_access',
                'description' => 'API endpoint accessed',
            ];
        });
    }

    /**
     * Indicate that the log is high severity.
     */
    public function highSeverity(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'high',
            ];
        });
    }

    /**
     * Indicate that the log is critical severity.
     */
    public function criticalSeverity(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'severity' => 'critical',
            ];
        });
    }
} 