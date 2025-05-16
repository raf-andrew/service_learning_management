<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SecurityLogService
{
    /**
     * Log a security event.
     *
     * @param string $event
     * @param array $context
     * @param string $level
     * @return void
     */
    public function log(string $event, array $context = [], string $level = 'warning'): void
    {
        if (!Config::get('security.logging.enabled', true)) {
            return;
        }

        $channel = Config::get('security.logging.channel', 'security');
        $minLevel = Config::get('security.logging.level', 'warning');

        if ($this->shouldLog($level, $minLevel)) {
            Log::channel($channel)->log($level, $event, array_merge([
                'timestamp' => now()->toIso8601String(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
            ], $context));
        }
    }

    /**
     * Log a security warning.
     *
     * @param string $event
     * @param array $context
     * @return void
     */
    public function warning(string $event, array $context = []): void
    {
        $this->log($event, $context, 'warning');
    }

    /**
     * Log a security error.
     *
     * @param string $event
     * @param array $context
     * @return void
     */
    public function error(string $event, array $context = []): void
    {
        $this->log($event, $context, 'error');
    }

    /**
     * Log a security info message.
     *
     * @param string $event
     * @param array $context
     * @return void
     */
    public function info(string $event, array $context = []): void
    {
        $this->log($event, $context, 'info');
    }

    /**
     * Determine if the log level should be logged based on minimum level.
     *
     * @param string $level
     * @param string $minLevel
     * @return bool
     */
    private function shouldLog(string $level, string $minLevel): bool
    {
        $levels = [
            'debug' => 0,
            'info' => 1,
            'notice' => 2,
            'warning' => 3,
            'error' => 4,
            'critical' => 5,
            'alert' => 6,
            'emergency' => 7,
        ];

        return $levels[$level] >= $levels[$minLevel];
    }
} 