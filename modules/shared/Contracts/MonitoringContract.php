<?php

namespace Modules\Shared\Contracts;

interface MonitoringContract
{
    public function getSystemStatus(): array;
    public function getHealthChecks(): array;
    public function getPerformanceMetrics(): array;
    public function logEvent(string $event, array $context = []): void;
} 