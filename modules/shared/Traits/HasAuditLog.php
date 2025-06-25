<?php

namespace Modules\Shared\Traits;

use Modules\Shared\AuditService;

trait HasAuditLog
{
    public function logAudit(string $action, array $context = []): void
    {
        app(AuditService::class)->log($action, $context);
    }
} 