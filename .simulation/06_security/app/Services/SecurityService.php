<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    /**
     * Log a security event.
     *
     * @param string $eventType
     * @param string $severity
     * @param string $description
     * @param array $metadata
     * @param User|null $user
     * @param Request|null $request
     * @return SecurityLog
     */
    public function logEvent(
        string $eventType,
        string $severity,
        string $description,
        array $metadata = [],
        ?User $user = null,
        ?Request $request = null
    ): SecurityLog {
        $log = SecurityLog::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'user_id' => $user?->id,
            'metadata' => $metadata,
            'status' => 'pending',
        ]);

        // If this is a high severity event, trigger alerts
        if (in_array($severity, ['high', 'critical'])) {
            $this->triggerAlert($log);
        }

        return $log;
    }

    /**
     * Get security events with optional filters.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEvents(array $filters = [])
    {
        $query = SecurityLog::query();

        if (isset($filters['event_type'])) {
            $query->ofType($filters['event_type']);
        }

        if (isset($filters['severity'])) {
            $query->ofSeverity($filters['severity']);
        }

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['ip_address'])) {
            $query->fromIp($filters['ip_address']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->inTimeRange($filters['start_date'], $filters['end_date']);
        }

        return $query->latest()->get();
    }

    /**
     * Check if an IP address is blocked.
     *
     * @param string $ipAddress
     * @return bool
     */
    public function isIpBlocked(string $ipAddress): bool
    {
        return Cache::has("blocked_ip:{$ipAddress}");
    }

    /**
     * Block an IP address.
     *
     * @param string $ipAddress
     * @param int $durationMinutes
     * @return void
     */
    public function blockIp(string $ipAddress, int $durationMinutes = 60): void
    {
        Cache::put("blocked_ip:{$ipAddress}", true, $durationMinutes * 60);
        $this->logEvent(
            'ip_block',
            'high',
            "IP address {$ipAddress} has been blocked",
            ['duration_minutes' => $durationMinutes]
        );
    }

    /**
     * Unblock an IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    public function unblockIp(string $ipAddress): void
    {
        Cache::forget("blocked_ip:{$ipAddress}");
        $this->logEvent(
            'ip_unblock',
            'medium',
            "IP address {$ipAddress} has been unblocked"
        );
    }

    /**
     * Check for suspicious activity.
     *
     * @param string $ipAddress
     * @param string $eventType
     * @return bool
     */
    public function isSuspiciousActivity(string $ipAddress, string $eventType): bool
    {
        $key = "suspicious:{$ipAddress}:{$eventType}";
        $count = Cache::get($key, 0);

        // Increment the counter
        Cache::put($key, $count + 1, 300); // 5 minutes

        // Define thresholds for different event types
        $thresholds = [
            'login_attempt' => 5,
            'api_access' => 100,
            'data_access' => 50,
        ];

        return $count >= ($thresholds[$eventType] ?? 10);
    }

    /**
     * Trigger an alert for a security event.
     *
     * @param SecurityLog $log
     * @return void
     */
    protected function triggerAlert(SecurityLog $log): void
    {
        // Log the alert
        Log::warning('Security Alert', [
            'event' => $log->event_type,
            'severity' => $log->severity,
            'description' => $log->description,
            'user_id' => $log->user_id,
            'ip_address' => $log->ip_address,
        ]);

        // Here you would typically:
        // 1. Send notifications (email, SMS, etc.)
        // 2. Create an incident in your incident management system
        // 3. Trigger automated responses
        // 4. Update the security log status
        $log->update(['status' => 'alerted']);
    }
} 