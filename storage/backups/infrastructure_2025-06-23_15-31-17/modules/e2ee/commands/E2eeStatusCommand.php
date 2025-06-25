<?php

namespace App\Modules\E2ee\Commands;

use Illuminate\Console\Command;

class E2eeStatusCommand extends Command
{
    protected $signature = 'e2ee:status';
    protected $description = 'Show E2EE system status.';

    public function handle()
    {
        $this->info('E2EE status command executed.');
        return 0;
    }
} 