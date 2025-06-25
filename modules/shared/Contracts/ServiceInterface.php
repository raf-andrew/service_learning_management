<?php

namespace Modules\Shared\Contracts;

interface ServiceInterface
{
    /**
     * Get the service name
     */
    public function getServiceName(): string;

    /**
     * Get service statistics
     */
    public function getStatistics(): array;

    /**
     * Health check for the service
     */
    public function healthCheck(): array;

    /**
     * Clear service cache
     */
    public function clearCache(string $pattern = '*'): void;
} 