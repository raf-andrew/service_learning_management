<?php

namespace App\Console\Commands\.sniffing;

use Illuminate\Console\Command;
use App\Models\SniffingResult;

class ClearSniffingDataCommand extends Command
{
    protected $signature = 'sniff:clear-data
                                {--all : Clear all sniffing data}
                                {--days= : Clear data older than X days}';

    protected $description = 'Clear sniffing results data';

    public function handle()
    {
        if ($this->option('all')) {
            $count = SniffingResult::count();
            SniffingResult::truncate();
            $this->info("Cleared all {$count} sniffing results");
            return 0;
        }

        $days = $this->option('days');
        if ($days) {
            $count = SniffingResult::where('created_at', '<=', now()->subDays($days))->count();
            SniffingResult::where('created_at', '<=', now()->subDays($days))->delete();
            $this->info("Cleared {$count} sniffing results older than {$days} days");
            return 0;
        }

        $this->error('Please specify either --all or --days option');
        return 1;
    }
}
