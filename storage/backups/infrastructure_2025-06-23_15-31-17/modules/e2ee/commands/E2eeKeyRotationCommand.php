<?php

namespace App\Modules\E2ee\Commands;

use Illuminate\Console\Command;

class E2eeKeyRotationCommand extends Command
{
    protected $signature = 'e2ee:key-rotate {userId?}';
    protected $description = 'Rotate E2EE keys for a user or all users.';

    public function handle()
    {
        $userId = $this->argument('userId');
        $this->info('E2EE key rotation command executed.' . ($userId ? " For user: $userId" : ' For all users.'));
        return 0;
    }
} 