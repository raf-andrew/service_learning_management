<?php

namespace App\Commands\Sniffing;

use Illuminate\Console\Command;
use App\Repositories\Sniffing\SniffResultRepository;

class ClearSniffingDataCommand extends Command
{
    protected $signature = 'sniff:clear
                                {--file= : Clear results for specific file}
                                {--all : Clear all sniffing results}';

    protected $description = 'Clear sniffing results from the database';

    private SniffResultRepository $repository;

    public function __construct(SniffResultRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $file = $this->option('file');
        $all = $this->option('all');

        if (!$file && !$all) {
            $this->error('Please specify either --file or --all option');
            return 1;
        }

        if ($file) {
            $this->repository->clearByFile($file);
            $this->info("Cleared sniffing results for file: {$file}");
        }

        if ($all) {
            $this->repository->clearAll();
            $this->info('Cleared all sniffing results');
        }

        return 0;
    }
}
