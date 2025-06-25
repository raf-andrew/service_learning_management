<?php

namespace App\Contracts\Services;

/**
 * Base Service Interface
 * 
 * Defines the contract that all services must implement.
 * Ensures consistent patterns across the application.
 */
interface ServiceInterface
{
    /**
     * Get service statistics
     */
    public function getStatistics(): array;

    /**
     * Get service health status
     */
    public function getHealthStatus(): array;

    /**
     * Validate service configuration
     */
    public function validateConfiguration(): bool;

    /**
     * Get service name
     */
    public function getServiceName(): string;
} 