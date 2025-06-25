# MCP Configuration Utility
# Provides configuration management functionality for the MCP system

function Test-ConfigurationHealth {
    param([string]$Environment = "local")
    
    try {
        $health = @{
            Healthy = $true
            Issues = @()
            Configurations = @{}
        }
        
        # Check main configuration files
        $configFiles = @(
            "config/app.php",
            "config/database.php",
            "config/modules.php",
            ".env"
        )
        
        foreach ($file in $configFiles) {
            $filePath = Join-Path (Get-Location) $file
            $configHealth = Test-ConfigFile -FilePath $filePath
            $health.Configurations[$file] = $configHealth
            
            if (-not $configHealth.Healthy) {
                $health.Healthy = $false
                $health.Issues += "Configuration file $file has issues: $($configHealth.Error)"
            }
        }
        
        # Check environment-specific configurations
        $envConfigHealth = Test-EnvironmentConfiguration -Environment $Environment
        $health.Configurations["environment"] = $envConfigHealth
        
        if (-not $envConfigHealth.Healthy) {
            $health.Healthy = $false
            $health.Issues += "Environment configuration has issues: $($envConfigHealth.Error)"
        }
        
        return $health
        
    } catch {
        return @{
            Healthy = $false
            Issues = @("Configuration health check failed: $($_.Exception.Message)")
            Configurations = @{}
        }
    }
}

function Test-ConfigFile {
    param([string]$FilePath)
    
    try {
        if (-not (Test-Path $FilePath)) {
            return @{
                Healthy = $false
                Status = "File Not Found"
                Error = "Configuration file not found"
                FilePath = $FilePath
            }
        }
        
        $fileInfo = Get-Item $FilePath
        $content = Get-Content $FilePath -Raw
        
        # Basic syntax validation for PHP files
        if ($FilePath -match "\.php$") {
            $syntaxValid = Test-PHPSyntax -Content $content
            if (-not $syntaxValid.Valid) {
                return @{
                    Healthy = $false
                    Status = "Syntax Error"
                    Error = $syntaxValid.Error
                    FilePath = $FilePath
                }
            }
        }
        
        # Check for sensitive data exposure
        $sensitivePatterns = @(
            @{ Pattern = 'password\s*=\s*["\'][^"\']+["\']'; Type = "Password" },
            @{ Pattern = 'secret\s*=\s*["\'][^"\']+["\']'; Type = "Secret" },
            @{ Pattern = 'key\s*=\s*["\'][^"\']+["\']'; Type = "Key" },
            @{ Pattern = 'token\s*=\s*["\'][^"\']+["\']'; Type = "Token" }
        )
        
        foreach ($pattern in $sensitivePatterns) {
            if ($content -match $pattern.Pattern) {
                return @{
                    Healthy = $false
                    Status = "Security Issue"
                    Error = "Potential $($pattern.Type) exposure detected"
                    FilePath = $FilePath
                }
            }
        }
        
        return @{
            Healthy = $true
            Status = "Valid"
            Error = $null
            FilePath = $FilePath
            FileSize = $fileInfo.Length
            LastModified = $fileInfo.LastWriteTime
        }
        
    } catch {
        return @{
            Healthy = $false
            Status = "Error"
            Error = $_.Exception.Message
            FilePath = $FilePath
        }
    }
}

function Test-PHPSyntax {
    param([string]$Content)
    
    try {
        # Create temporary file for syntax check
        $tempFile = [System.IO.Path]::GetTempFileName() + ".php"
        Set-Content -Path $tempFile -Value $Content
        
        # Use PHP CLI to check syntax
        $phpOutput = & php -l $tempFile 2>&1
        
        # Clean up temp file
        Remove-Item $tempFile -Force
        
        if ($LASTEXITCODE -eq 0) {
            return @{ Valid = $true; Error = $null }
        } else {
            return @{ Valid = $false; Error = $phpOutput }
        }
        
    } catch {
        return @{ Valid = $false; Error = $_.Exception.Message }
    }
}

function Test-EnvironmentConfiguration {
    param([string]$Environment)
    
    try {
        $envFile = Join-Path (Get-Location) ".env"
        
        if (-not (Test-Path $envFile)) {
            return @{
                Healthy = $false
                Status = "Missing .env file"
                Error = ".env file not found"
            }
        }
        
        $envContent = Get-Content $envFile
        $requiredVars = @(
            "APP_ENV",
            "APP_DEBUG",
            "APP_KEY",
            "DB_CONNECTION",
            "DB_HOST",
            "DB_PORT",
            "DB_DATABASE",
            "DB_USERNAME",
            "DB_PASSWORD"
        )
        
        $missingVars = @()
        foreach ($var in $requiredVars) {
            $found = $envContent | Where-Object { $_ -match "^$var=" }
            if (-not $found) {
                $missingVars += $var
            }
        }
        
        if ($missingVars.Count -gt 0) {
            return @{
                Healthy = $false
                Status = "Missing Variables"
                Error = "Missing required environment variables: $($missingVars -join ', ')"
            }
        }
        
        return @{
            Healthy = $true
            Status = "Valid"
            Error = $null
            Environment = $Environment
        }
        
    } catch {
        return @{
            Healthy = $false
            Status = "Error"
            Error = $_.Exception.Message
        }
    }
}

function Get-ConfigurationStatus {
    param([string]$Environment = "local")
    
    $health = Test-ConfigurationHealth -Environment $Environment
    
    return @{
        Healthy = $health.Healthy
        Status = if ($health.Healthy) { "Valid" } else { "Invalid" }
        Issues = $health.Issues
        Configurations = $health.Configurations
    }
}

function Deploy-Configuration {
    param([string]$Environment = "local")
    
    try {
        # Validate configuration before deployment
        $health = Test-ConfigurationHealth -Environment $Environment
        if (-not $health.Healthy) {
            return @{
                Success = $false
                Error = "Configuration validation failed: $($health.Issues -join '; ')"
            }
        }
        
        # Create backup of current configuration
        $backupPath = "modules/mcp/backups/config"
        if (-not (Test-Path $backupPath)) {
            New-Item -ItemType Directory -Path $backupPath -Force | Out-Null
        }
        
        $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
        $configBackup = Join-Path $backupPath "config_backup_$timestamp.zip"
        
        $configFiles = @(
            "config/app.php",
            "config/database.php",
            "config/modules.php",
            ".env"
        )
        
        # Create backup
        Compress-Archive -Path $configFiles -DestinationPath $configBackup -Force
        
        # Clear configuration cache
        if (Test-Path "bootstrap/cache/config.php") {
            Remove-Item "bootstrap/cache/config.php" -Force
        }
        
        # Reload configuration
        $reloadResult = Reload-Configuration -Environment $Environment
        
        if ($reloadResult.Success) {
            return @{
                Success = $true
                Message = "Configuration deployed successfully"
                BackupFile = $configBackup
                Error = $null
            }
        } else {
            return @{
                Success = $false
                Error = "Configuration reload failed: $($reloadResult.Error)"
                BackupFile = $configBackup
            }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Reload-Configuration {
    param([string]$Environment = "local")
    
    try {
        # Clear Laravel configuration cache
        if (Test-Path "artisan") {
            $output = & php artisan config:clear 2>&1
            if ($LASTEXITCODE -ne 0) {
                return @{ Success = $false; Error = "Failed to clear config cache: $output" }
            }
            
            $output = & php artisan config:cache 2>&1
            if ($LASTEXITCODE -ne 0) {
                return @{ Success = $false; Error = "Failed to cache config: $output" }
            }
        }
        
        return @{ Success = $true; Message = "Configuration reloaded successfully" }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Initialize-Configuration {
    param(
        [string]$Environment = "local",
        [switch]$Force
    )
    
    try {
        # Check if configuration files exist
        $configFiles = @(
            "config/app.php",
            "config/database.php",
            "config/modules.php"
        )
        
        $missingFiles = @()
        foreach ($file in $configFiles) {
            if (-not (Test-Path $file)) {
                $missingFiles += $file
            }
        }
        
        if ($missingFiles.Count -gt 0) {
            if ($Force) {
                # Create missing configuration files
                foreach ($file in $missingFiles) {
                    $result = Create-ConfigFile -FilePath $file -Environment $Environment
                    if (-not $result.Success) {
                        return @{ Success = $false; Error = "Failed to create $file : $($result.Error)" }
                    }
                }
            } else {
                return @{ Success = $false; Error = "Missing configuration files: $($missingFiles -join ', ')" }
            }
        }
        
        # Validate configuration
        $health = Test-ConfigurationHealth -Environment $Environment
        if (-not $health.Healthy) {
            return @{ Success = $false; Error = "Configuration validation failed: $($health.Issues -join '; ')" }
        }
        
        return @{ Success = $true; Message = "Configuration initialized successfully" }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Create-ConfigFile {
    param([string]$FilePath, [string]$Environment)
    
    try {
        $directory = Split-Path $FilePath -Parent
        if (-not (Test-Path $directory)) {
            New-Item -ItemType Directory -Path $directory -Force | Out-Null
        }
        
        # Create basic configuration content based on file type
        $content = switch -Wildcard ($FilePath) {
            "*app.php" { Get-AppConfigContent -Environment $Environment }
            "*database.php" { Get-DatabaseConfigContent -Environment $Environment }
            "*modules.php" { Get-ModulesConfigContent -Environment $Environment }
            default { "<?php`nreturn [];" }
        }
        
        Set-Content -Path $FilePath -Value $content -Encoding UTF8
        
        return @{ Success = $true; FilePath = $FilePath }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Get-AppConfigContent {
    param([string]$Environment)
    
    return @"
<?php

return [
    'name' => env('APP_NAME', 'Service Learning Management'),
    'env' => env('APP_ENV', '$Environment'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'providers' => [
        // Laravel Framework Service Providers...
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        
        // Application Service Providers...
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        
        // Module Service Providers...
        App\Modules\Shared\Providers\ModuleServiceProvider::class,
    ],
    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
    ],
];
"@
}

function Get-DatabaseConfigContent {
    param([string]$Environment)
    
    return @"
<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'service_learning_management'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        'soc2_sqlite' => [
            'driver' => 'sqlite',
            'database' => storage_path('modules/soc2/database/soc2.sqlite'),
            'prefix' => '',
        ],
        'e2ee_sqlite' => [
            'driver' => 'sqlite',
            'database' => storage_path('modules/e2ee/database/e2ee.sqlite'),
            'prefix' => '',
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'slm_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];
"@
}

function Get-ModulesConfigContent {
    param([string]$Environment)
    
    return @"
<?php

return [
    'modules' => [
        'e2ee' => [
            'enabled' => env('E2EE_ENABLED', true),
            'encryption_algorithm' => env('E2EE_ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
            'key_size' => env('E2EE_KEY_SIZE', 32),
            'iv_size' => env('E2EE_IV_SIZE', 16),
            'auth_tag_size' => env('E2EE_AUTH_TAG_SIZE', 16),
            'derivation_iterations' => env('E2EE_DERIVATION_ITERATIONS', 100000),
            'audit_enabled' => env('E2EE_AUDIT_ENABLED', true),
            'cache_ttl' => env('E2EE_CACHE_TTL', 3600),
            'cleanup_interval' => env('E2EE_CLEANUP_INTERVAL', 30),
        ],
        'soc2' => [
            'enabled' => env('SOC2_ENABLED', true),
            'type' => env('SOC2_TYPE', 'Type II'),
            'audit' => [
                'enabled' => env('SOC2_AUDIT_ENABLED', true),
                'retention_days' => env('SOC2_AUDIT_RETENTION_DAYS', 2555),
                'encrypt_logs' => env('SOC2_AUDIT_ENCRYPT_LOGS', true),
            ],
            'validation' => [
                'thresholds' => [
                    'compliance_score' => env('SOC2_COMPLIANCE_SCORE_THRESHOLD', 90),
                    'security_score' => env('SOC2_SECURITY_SCORE_THRESHOLD', 85),
                ],
            ],
        ],
        'web3' => [
            'enabled' => env('WEB3_ENABLED', true),
            'network' => env('WEB3_NETWORK', 'localhost'),
            'port' => env('WEB3_PORT', 8545),
            'contract_address' => env('WEB3_CONTRACT_ADDRESS', ''),
        ],
        'mcp' => [
            'enabled' => env('MCP_ENABLED', true),
            'environment' => env('MCP_ENVIRONMENT', '$Environment'),
            'log_level' => env('MCP_LOG_LEVEL', 'INFO'),
            'auto_heal' => env('MCP_AUTO_HEAL', false),
        ],
    ],
];
"@
}

# Export functions
Export-ModuleMember -Function @(
    "Test-ConfigurationHealth",
    "Get-ConfigurationStatus",
    "Deploy-Configuration",
    "Initialize-Configuration"
) 