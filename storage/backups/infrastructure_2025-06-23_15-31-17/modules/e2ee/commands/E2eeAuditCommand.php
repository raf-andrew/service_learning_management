<?php

namespace App\Modules\E2ee\Commands;

use Illuminate\Console\Command;

class E2eeAuditCommand extends Command
{
    protected $signature = 'e2ee:audit';
    protected $description = 'Run E2EE audit checks.';

    public function handle()
    {
        $this->info('E2EE audit command executed.');
        return 0;
    }
} 