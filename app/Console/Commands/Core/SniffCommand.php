<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\SniffingController;

class SniffCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sniff:run {--target=} {--rules=} {--output=json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the sniffing analysis on specified targets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $target = $this->option('target');
        $rules = $this->option('rules');
        $output = $this->option('output');

        if (!$target) {
            $this->error('Target is required. Use --target option.');
            return 1;
        }

        $this->info("Running sniffing analysis on: {$target}");

        try {
            // Create a mock request for the controller
            $request = new \Illuminate\Http\Request();
            $request->merge([
                'target' => $target,
                'rules' => $rules ? explode(',', $rules) : [],
                'output_format' => $output
            ]);

            $controller = new SniffingController();
            $response = $controller->run($request);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getContent(), true);
                $this->info('Sniffing analysis completed successfully.');
                
                if ($output === 'json') {
                    $this->line(json_encode($data, JSON_PRETTY_PRINT));
                } else {
                    $this->displayResults($data);
                }
            } else {
                $this->error('Sniffing analysis failed.');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("Error running sniffing analysis: {$e->getMessage()}");
            Log::error('SniffCommand error', [
                'error' => $e->getMessage(),
                'target' => $target
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Display results in a formatted table
     */
    protected function displayResults(array $data): void
    {
        if (isset($data['results']) && is_array($data['results'])) {
            $headers = ['Rule', 'Status', 'Message'];
            $rows = [];

            foreach ($data['results'] as $result) {
                $rows[] = [
                    $result['rule'] ?? 'Unknown',
                    $result['status'] ?? 'Unknown',
                    $result['message'] ?? 'No message'
                ];
            }

            $this->table($headers, $rows);
        }

        if (isset($data['summary'])) {
            $this->info("\nSummary:");
            $total = $data['summary']['total'] ?? 0;
            $passed = $data['summary']['passed'] ?? 0;
            $failed = $data['summary']['failed'] ?? 0;
            $this->line("Total rules checked: {$total}");
            $this->line("Passed: {$passed}");
            $this->line("Failed: {$failed}");
        }
    }
} 