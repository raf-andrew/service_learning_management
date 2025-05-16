<?php

namespace App\Services;

use App\Models\ServiceHealth;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    private $thresholds = [
        'cpu' => 80,
        'memory' => 85,
        'disk' => 90
    ];

    public function setThresholds(array $thresholds): void
    {
        $this->thresholds = array_merge($this->thresholds, $thresholds);
    }

    public function checkThresholds(array $metrics): array
    {
        $alerts = [];

        foreach ($metrics as $metric) {
            if ($metric['value'] > $this->thresholds[$metric['name']]) {
                $alerts[] = [
                    'type' => $metric['name'],
                    'level' => 'critical',
                    'message' => "{$metric['name']} is above threshold: {$metric['value']} {$metric['unit']}"
                ];
            } elseif ($metric['value'] > ($this->thresholds[$metric['name']] * 0.8)) {
                $alerts[] = [
                    'type' => $metric['name'],
                    'level' => 'warning',
                    'message' => "{$metric['name']} is approaching threshold: {$metric['value']} {$metric['unit']}"
                ];
            }
        }

        return $alerts;
    }

    public function createAlert(ServiceHealth $service, array $alertData): Alert
    {
        try {
            $alert = $service->alerts()->create([
                'type' => $alertData['type'],
                'level' => $alertData['level'],
                'message' => $alertData['message']
            ]);

            // Update service warning/error counts
            if ($alertData['level'] === 'critical') {
                $service->increment('error_count');
            } else {
                $service->increment('warning_count');
            }

            // Send notification for critical alerts
            if ($alertData['level'] === 'critical') {
                $this->sendAlertNotification($alert);
            }

            return $alert;
        } catch (\Exception $e) {
            Log::error("Failed to create alert for service {$service->service_name}: " . $e->getMessage());
            throw $e;
        }
    }

    public function processAlert(Alert $alert): void
    {
        try {
            // Update alert status
            $alert->update([
                'acknowledged' => true,
                'acknowledged_at' => now()
            ]);

            // Send acknowledgment notification
            $this->sendAcknowledgmentNotification($alert);
        } catch (\Exception $e) {
            Log::error("Failed to process alert {$alert->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function sendAlertNotification(Alert $alert): void
    {
        try {
            // In a real application, you would send an email, SMS, or other notification
            // For this simulation, we'll just log it
            Log::warning("ALERT: {$alert->message}", [
                'service' => $alert->serviceHealth->service_name,
                'level' => $alert->level,
                'type' => $alert->type
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send alert notification: " . $e->getMessage());
        }
    }

    private function sendAcknowledgmentNotification(Alert $alert): void
    {
        try {
            // In a real application, you would send an email, SMS, or other notification
            // For this simulation, we'll just log it
            Log::info("Alert acknowledged: {$alert->message}", [
                'service' => $alert->serviceHealth->service_name,
                'level' => $alert->level,
                'type' => $alert->type,
                'acknowledged_at' => $alert->acknowledged_at
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send acknowledgment notification: " . $e->getMessage());
        }
    }
} 