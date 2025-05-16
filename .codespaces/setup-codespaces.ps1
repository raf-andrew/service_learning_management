# Setup Codespaces Configuration
$ErrorActionPreference = "Stop"

# Create Codespaces log directory if it doesn't exist
$logDir = ".codespaces/log"
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    Write-Host "Created Codespaces log directory: $logDir"
}

# Function to log Codespaces events
function Write-CodespacesLog {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logEntry = "[$timestamp] [$Level] $Message"
    Add-Content -Path "$logDir/codespaces.log" -Value $logEntry
}

# Update .env for Codespaces
$envContent = Get-Content ".env"
$envContent = $envContent -replace "SERVICE_ENVIRONMENT=.*", "SERVICE_ENVIRONMENT=codespaces"
$envContent = $envContent -replace "DB_CONNECTION=.*", "DB_CONNECTION=mysql"
$envContent = $envContent -replace "DB_HOST=.*", "DB_HOST=mysql"
$envContent = $envContent -replace "REDIS_HOST=.*", "REDIS_HOST=redis"
Set-Content -Path ".env" -Value $envContent

Write-CodespacesLog "Updated .env for Codespaces environment"

# Create Codespaces-specific config
$codespacesConfig = @"
<?php

return [
    'codespaces' => [
        'enabled' => true,
        'services' => [
            'database' => [
                'host' => env('CODESPACES_DB_HOST', 'mysql'),
                'port' => env('CODESPACES_DB_PORT', 3306),
                'database' => env('CODESPACES_DB_DATABASE', 'service_learning'),
                'username' => env('CODESPACES_DB_USERNAME', 'root'),
                'password' => env('CODESPACES_DB_PASSWORD', ''),
            ],
            'redis' => [
                'host' => env('CODESPACES_REDIS_HOST', 'redis'),
                'port' => env('CODESPACES_REDIS_PORT', 6379),
                'password' => env('CODESPACES_REDIS_PASSWORD', null),
            ],
        ],
        'logging' => [
            'path' => storage_path('logs/codespaces.log'),
            'level' => env('CODESPACES_LOG_LEVEL', 'debug'),
        ],
    ],
];
"@

# Write Codespaces config
$configPath = "config/codespaces.php"
Set-Content -Path $configPath -Value $codespacesConfig
Write-CodespacesLog "Created Codespaces configuration file: $configPath"

# Create Codespaces service provider
$providerContent = @"
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class CodespacesServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (Config::get('codespaces.enabled')) {
            // Override database configuration
            Config::set('database.connections.mysql.host', Config::get('codespaces.services.database.host'));
            Config::set('database.connections.mysql.port', Config::get('codespaces.services.database.port'));
            Config::set('database.connections.mysql.database', Config::get('codespaces.services.database.database'));
            Config::set('database.connections.mysql.username', Config::get('codespaces.services.database.username'));
            Config::set('database.connections.mysql.password', Config::get('codespaces.services.database.password'));

            // Override Redis configuration
            Config::set('database.redis.default.host', Config::get('codespaces.services.redis.host'));
            Config::set('database.redis.default.port', Config::get('codespaces.services.redis.port'));
            Config::set('database.redis.default.password', Config::get('codespaces.services.redis.password'));
        }
    }

    public function boot()
    {
        //
    }
}
"@

# Write service provider
$providerPath = "app/Providers/CodespacesServiceProvider.php"
Set-Content -Path $providerPath -Value $providerContent
Write-CodespacesLog "Created Codespaces service provider: $providerPath"

# Update config/app.php to include the provider
$appConfig = Get-Content "config/app.php"
$providerLine = "        App\Providers\CodespacesServiceProvider::class,"
$appConfig = $appConfig -replace "    'providers' => \[", "    'providers' => [\n$providerLine"
Set-Content -Path "config/app.php" -Value $appConfig
Write-CodespacesLog "Updated app configuration to include Codespaces service provider"

Write-Host "Codespaces configuration setup complete" 