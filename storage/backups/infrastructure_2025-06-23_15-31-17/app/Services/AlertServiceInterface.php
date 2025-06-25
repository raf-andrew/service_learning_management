<?php

namespace App\Services;

/**
 * Interface for alert service functionality
 * 
 * @package App\Services
 */
interface AlertServiceInterface
{
    /**
     * Validate the input data
     * 
     * @param array $data The data to validate
     * @return bool True if valid, false otherwise
     * @throws \InvalidArgumentException If validation fails
     */
    public function validate(array $data): bool;

    /**
     * Process the input data
     * 
     * @param array $data The data to process
     * @return array The processed data
     * @throws \Exception If processing fails
     */
    public function process(array $data): array;

    /**
     * Handle the alert processing
     * 
     * @param array $data The data to handle
     * @return array The handled data
     * @throws \Exception If handling fails
     */
    public function handle(array $data): array;

    /**
     * Process metrics and generate alerts
     * 
     * @param string $serviceName The name of the service being monitored
     * @param array $metrics The metrics data to process
     * @return array Array of generated alerts
     * @throws \Exception If processing fails
     */
    public function processMetrics(string $serviceName, array $metrics): array;

    /**
     * Get all active alerts
     * 
     * @return array Array of active alerts
     */
    public function getActiveAlerts(): array;

    /**
     * Resolve an alert
     * 
     * @param int $alertId The ID of the alert to resolve
     * @return bool True if resolved successfully
     */
    public function resolveAlert(int $alertId): bool;
} 