<?php

namespace App\Console\Commands\Auth;

class ManageAuthDocsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:docs
        {action : The action to perform (list|generate|publish)}
        {--type= : Documentation type (api|guide|reference)}
        {--format= : Output format (html|markdown|pdf)}
        {--output= : Output directory}';

    protected $description = 'Manage authentication documentation';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listDocs();
            case 'generate':
                return $this->generateDocs();
            case 'publish':
                return $this->publishDocs();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listDocs()
    {
        $type = $this->option('type');

        try {
            $docs = $this->authService->getAllDocs([
                'type' => $type
            ]);

            $this->table(
                ['Name', 'Type', 'Format', 'Last Updated'],
                $docs->map(fn($doc) => [
                    $doc->name,
                    $doc->type,
                    $doc->format,
                    $doc->updated_at->format('Y-m-d H:i:s')
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list documentation: {$e->getMessage()}");
            return 1;
        }
    }

    protected function generateDocs()
    {
        $type = $this->option('type');
        $format = $this->option('format');
        $output = $this->option('output');

        if (!$type || !$format) {
            $this->error('Documentation type and format are required');
            return 1;
        }

        try {
            $docs = $this->authService->generateDocs([
                'type' => $type,
                'format' => $format,
                'output' => $output
            ]);

            $this->info("Documentation generated successfully:");
            $this->table(
                ['Name', 'Path'],
                $docs->map(fn($doc) => [
                    $doc->name,
                    $doc->path
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate documentation: {$e->getMessage()}");
            return 1;
        }
    }

    protected function publishDocs()
    {
        $type = $this->option('type');
        $format = $this->option('format');
        $output = $this->option('output');

        if (!$type || !$format || !$output) {
            $this->error('Documentation type, format, and output directory are required');
            return 1;
        }

        try {
            $docs = $this->authService->publishDocs([
                'type' => $type,
                'format' => $format,
                'output' => $output
            ]);

            $this->info("Documentation published successfully:");
            $this->table(
                ['Name', 'URL'],
                $docs->map(fn($doc) => [
                    $doc->name,
                    $doc->url
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to publish documentation: {$e->getMessage()}");
            return 1;
        }
    }
} 