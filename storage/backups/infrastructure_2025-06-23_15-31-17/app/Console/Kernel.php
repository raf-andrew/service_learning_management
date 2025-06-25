<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Core commands
        Commands\RunTests::class,
        Commands\CodespaceCommand::class,
        Commands\HealthMonitorCommand::class,
        Commands\InfrastructureManagerCommand::class,
        Commands\Web3ManagerCommand::class,
        Commands\ConvertShellScriptsCommand::class,
        Commands\ReorganizeCommandsCommand::class,
        Commands\UpdateCommandNamespacesCommand::class,
        
        // Codespaces commands
        Commands\Codespaces\RunCodespacesTestsCommand::class,
        Commands\Codespaces\HealthCheckCommand::class,
        Commands\Codespaces\ManageCodespaceCommand::class,
        
        // Web3 commands
        Commands\Web3\RunWeb3TestsCommand::class,
        Commands\Web3\RunAllWeb3TestsCommand::class,
        
        // Testing commands
        Commands\Testing\RunCommandTestsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // Load commands from the main Commands directory
        $this->load(__DIR__.'/Commands');

        // Load commands from domain-specific directories
        $domains = ['sniffing', 'codespaces', 'web3', 'infrastructure', 'environment', 'documentation', 'docker', 'testing', 'project', 'config', 'analytics', 'deployment', 'setup', 'auth', 'github'];
        foreach ($domains as $domain) {
            $this->load(__DIR__.'/Commands/'.$domain);
        }

        require base_path('routes/console.php');
    }
} 