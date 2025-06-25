# MCP (Management Control Protocol) PowerShell Scripts
# Comprehensive management and control system for platform services

param(
    [string]$Command = "help",
    [string]$Environment = "local",
    [string]$Service = "",
    [string]$Database = "",
    [string]$Category = "",
    [string]$Query = "",
    [string]$StartDate = "",
    [string]$EndDate = "",
    [switch]$Force,
    [switch]$Verify,
    [switch]$AutoHeal,
    [switch]$Background,
    [switch]$Follow,
    [int]$Interval = 300
)

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path $scriptPath "Utils"

# Load utility functions
. (Join-Path $utilsPath "Logger.ps1")
. (Join-Path $utilsPath "Environment.ps1")
. (Join-Path $utilsPath "Services.ps1")
. (Join-Path $utilsPath "Database.ps1")
. (Join-Path $utilsPath "Configuration.ps1")

# Initialize logging
Initialize-Logger -LogPath "modules/mcp/logs" -LogLevel "INFO"

function Show-Help {
    Write-Host @"
MCP (Management Control Protocol) - Service Learning Management System

USAGE:
    .\ManagementScripts.ps1 -Command <command> [options]

COMMANDS:
    init                    Initialize the MCP system
    check                   Check system health and status
    run                     Run the MCP system
    stop                    Stop the MCP system
    status                  Show system status
    manage                  Manage services, databases, logs, config

MANAGE SUBCOMMANDS:
    check-services          Check service health
    start-service           Start a specific service
    stop-service            Stop a specific service
    restart-service         Restart a specific service
    check-databases         Check database connections
    validate-data           Validate data integrity
    backup-database         Backup a database
    restore-database        Restore a database
    logs                    View logs
    search-logs             Search logs
    rotate-logs             Rotate logs
    check-config            Check configuration
    deploy-config           Deploy configuration

OPTIONS:
    -Environment <env>      Environment (local, remote, staging, production)
    -Service <service>      Service name for service-specific commands
    -Database <db>          Database name for database-specific commands
    -Category <category>    Log category (services, data, config, general)
    -Query <query>          Search query for logs
    -StartDate <date>       Start date for log searches (YYYY-MM-DD)
    -EndDate <date>         End date for log searches (YYYY-MM-DD)
    -Force                  Force operation without confirmation
    -Verify                 Verify operation results
    -AutoHeal               Enable auto-healing for issues
    -Background             Run in background
    -Follow                 Follow log output
    -Interval <seconds>     Interval for monitoring (default: 300)

EXAMPLES:
    .\ManagementScripts.ps1 -Command init -Environment remote
    .\ManagementScripts.ps1 -Command manage -Service mysql -Action start
    .\ManagementScripts.ps1 -Command manage -Category services -Action logs -Follow
    .\ManagementScripts.ps1 -Command manage -Database main -Action backup
"@
}

function Initialize-MCPSystem {
    param(
        [string]$Environment = "local",
        [switch]$Force,
        [switch]$Verify,
        [switch]$AutoHeal
    )
    
    Write-Log "Initializing MCP system for environment: $Environment" -Level "INFO" -Category "Init"
    
    try {
        # Check prerequisites
        $prerequisites = Test-Prerequisites
        if (-not $prerequisites.IsValid) {
            Write-Log "Prerequisites check failed" -Level "ERROR" -Category "Init"
            foreach ($issue in $prerequisites.Issues) {
                Write-Log "Issue: $($issue.Message)" -Level "ERROR" -Category "Init"
            }
            
            if ($AutoHeal) {
                Write-Log "Attempting auto-healing..." -Level "WARN" -Category "Init"
                # Auto-healing logic would go here
            }
            
            return $false
        }
        
        # Initialize environment
        $envConfig = Initialize-Environment -Environment $Environment -Force:$Force
        if (-not $envConfig.Success) {
            Write-Log "Environment initialization failed: $($envConfig.Error)" -Level "ERROR" -Category "Init"
            return $false
        }
        
        # Initialize services
        $servicesResult = Initialize-Services -Environment $Environment -Force:$Force
        if (-not $servicesResult.Success) {
            Write-Log "Services initialization failed: $($servicesResult.Error)" -Level "ERROR" -Category "Init"
            return $false
        }
        
        # Initialize databases
        $dbResult = Initialize-Databases -Environment $Environment -Force:$Force
        if (-not $dbResult.Success) {
            Write-Log "Database initialization failed: $($dbResult.Error)" -Level "ERROR" -Category "Init"
            return $false
        }
        
        # Initialize configuration
        $configResult = Initialize-Configuration -Environment $Environment -Force:$Force
        if (-not $configResult.Success) {
            Write-Log "Configuration initialization failed: $($configResult.Error)" -Level "ERROR" -Category "Init"
            return $false
        }
        
        if ($Verify) {
            $verificationResult = Test-SystemHealth -Environment $Environment
            if (-not $verificationResult.Healthy) {
                Write-Log "System verification failed" -Level "ERROR" -Category "Init"
                return $false
            }
        }
        
        Write-Log "MCP system initialized successfully" -Level "SUCCESS" -Category "Init"
        return $true
        
    } catch {
        Write-Log "Initialization failed with error: $_" -Level "ERROR" -Category "Init"
        return $false
    }
}

function Test-SystemHealth {
    param([string]$Environment = "local")
    
    Write-Log "Testing system health..." -Level "INFO" -Category "Health"
    
    $health = @{
        Healthy = $true
        Services = @{}
        Databases = @{}
        Configuration = @{}
        Issues = @()
    }
    
    try {
        # Test services
        $services = @("mysql", "redis", "apache", "php")
        foreach ($service in $services) {
            $serviceHealth = Test-ServiceHealth -Service $service
            $health.Services[$service] = $serviceHealth
            
            if (-not $serviceHealth.Healthy) {
                $health.Healthy = $false
                $health.Issues += "Service $service is unhealthy: $($serviceHealth.Error)"
            }
        }
        
        # Test databases
        $databases = @("main", "soc2", "e2ee")
        foreach ($db in $databases) {
            $dbHealth = Test-DatabaseHealth -Database $db
            $health.Databases[$db] = $dbHealth
            
            if (-not $dbHealth.Healthy) {
                $health.Healthy = $false
                $health.Issues += "Database $db is unhealthy: $($dbHealth.Error)"
            }
        }
        
        # Test configuration
        $configHealth = Test-ConfigurationHealth -Environment $Environment
        $health.Configuration = $configHealth
        
        if (-not $configHealth.Healthy) {
            $health.Healthy = $false
            $health.Issues += "Configuration is unhealthy: $($configHealth.Error)"
        }
        
        Write-Log "System health check completed" -Level "SUCCESS" -Category "Health"
        return $health
        
    } catch {
        Write-Log "Health check failed: $_" -Level "ERROR" -Category "Health"
        $health.Healthy = $false
        $health.Issues += "Health check error: $_"
        return $health
    }
}

function Start-MCPSystem {
    param(
        [string]$Environment = "local",
        [switch]$Background,
        [switch]$AutoHeal,
        [switch]$Verify,
        [int]$Interval = 300
    )
    
    Write-Log "Starting MCP system..." -Level "INFO" -Category "Start"
    
    try {
        # Check if system is already running
        if (Test-MCPSystemRunning) {
            Write-Log "MCP system is already running" -Level "WARN" -Category "Start"
            return $false
        }
        
        # Start services
        $servicesResult = Start-Services -Environment $Environment
        if (-not $servicesResult.Success) {
            Write-Log "Failed to start services: $($servicesResult.Error)" -Level "ERROR" -Category "Start"
            return $false
        }
        
        # Start monitoring
        if ($Background) {
            Start-Job -ScriptBlock {
                param($Environment, $Interval, $AutoHeal)
                while ($true) {
                    $health = Test-SystemHealth -Environment $Environment
                    if (-not $health.Healthy -and $AutoHeal) {
                        # Auto-healing logic
                    }
                    Start-Sleep -Seconds $Interval
                }
            } -ArgumentList $Environment, $Interval, $AutoHeal
        }
        
        if ($Verify) {
            $verificationResult = Test-SystemHealth -Environment $Environment
            if (-not $verificationResult.Healthy) {
                Write-Log "System verification failed after start" -Level "ERROR" -Category "Start"
                return $false
            }
        }
        
        Write-Log "MCP system started successfully" -Level "SUCCESS" -Category "Start"
        return $true
        
    } catch {
        Write-Log "Failed to start MCP system: $_" -Level "ERROR" -Category "Start"
        return $false
    }
}

function Stop-MCPSystem {
    param([string]$Environment = "local")
    
    Write-Log "Stopping MCP system..." -Level "INFO" -Category "Stop"
    
    try {
        # Stop services
        $servicesResult = Stop-Services -Environment $Environment
        if (-not $servicesResult.Success) {
            Write-Log "Failed to stop services: $($servicesResult.Error)" -Level "ERROR" -Category "Stop"
            return $false
        }
        
        # Stop monitoring jobs
        Get-Job | Where-Object { $_.Name -like "*MCP*" } | Stop-Job
        
        Write-Log "MCP system stopped successfully" -Level "SUCCESS" -Category "Stop"
        return $true
        
    } catch {
        Write-Log "Failed to stop MCP system: $_" -Level "ERROR" -Category "Stop"
        return $false
    }
}

function Get-MCPSystemStatus {
    param([string]$Environment = "local")
    
    Write-Log "Getting MCP system status..." -Level "INFO" -Category "Status"
    
    try {
        $status = @{
            Running = Test-MCPSystemRunning
            Environment = $Environment
            Services = @{}
            Databases = @{}
            Configuration = @{}
            LastCheck = Get-Date
        }
        
        # Get service status
        $services = @("mysql", "redis", "apache", "php")
        foreach ($service in $services) {
            $status.Services[$service] = Get-ServiceStatus -Service $service
        }
        
        # Get database status
        $databases = @("main", "soc2", "e2ee")
        foreach ($db in $databases) {
            $status.Databases[$db] = Get-DatabaseStatus -Database $db
        }
        
        # Get configuration status
        $status.Configuration = Get-ConfigurationStatus -Environment $Environment
        
        Write-Log "Status check completed" -Level "SUCCESS" -Category "Status"
        return $status
        
    } catch {
        Write-Log "Status check failed: $_" -Level "ERROR" -Category "Status"
        return $null
    }
}

function Manage-MCPSystem {
    param(
        [string]$Action,
        [string]$Service = "",
        [string]$Database = "",
        [string]$Category = "",
        [string]$Query = "",
        [string]$StartDate = "",
        [string]$EndDate = "",
        [switch]$Follow
    )
    
    Write-Log "Managing MCP system: $Action" -Level "INFO" -Category "Manage"
    
    try {
        switch ($Action) {
            "check-services" {
                $services = @("mysql", "redis", "apache", "php")
                foreach ($service in $services) {
                    $health = Test-ServiceHealth -Service $service
                    Write-Host "$service`: $($health.Status)"
                }
            }
            
            "start-service" {
                if (-not $Service) {
                    Write-Log "Service name required for start-service action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Start-Service -Service $Service
                return $result.Success
            }
            
            "stop-service" {
                if (-not $Service) {
                    Write-Log "Service name required for stop-service action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Stop-Service -Service $Service
                return $result.Success
            }
            
            "restart-service" {
                if (-not $Service) {
                    Write-Log "Service name required for restart-service action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Restart-Service -Service $Service
                return $result.Success
            }
            
            "check-databases" {
                $databases = @("main", "soc2", "e2ee")
                foreach ($db in $databases) {
                    $health = Test-DatabaseHealth -Database $db
                    Write-Host "$db`: $($health.Status)"
                }
            }
            
            "validate-data" {
                if (-not $Database) {
                    Write-Log "Database name required for validate-data action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Validate-DatabaseData -Database $Database
                return $result.Success
            }
            
            "backup-database" {
                if (-not $Database) {
                    Write-Log "Database name required for backup-database action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Backup-Database -Database $Database
                return $result.Success
            }
            
            "restore-database" {
                if (-not $Database) {
                    Write-Log "Database name required for restore-database action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Restore-Database -Database $Database
                return $result.Success
            }
            
            "logs" {
                if (-not $Category) {
                    Write-Log "Category required for logs action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $logs = Get-Logs -Category $Category -Follow:$Follow
                return $logs
            }
            
            "search-logs" {
                if (-not $Query) {
                    Write-Log "Query required for search-logs action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $logs = Search-Logs -Query $Query -StartDate $StartDate -EndDate $EndDate
                return $logs
            }
            
            "rotate-logs" {
                if (-not $Category) {
                    Write-Log "Category required for rotate-logs action" -Level "ERROR" -Category "Manage"
                    return $false
                }
                $result = Rotate-Logs -Category $Category
                return $result.Success
            }
            
            "check-config" {
                $result = Test-ConfigurationHealth
                return $result.Healthy
            }
            
            "deploy-config" {
                $result = Deploy-Configuration
                return $result.Success
            }
            
            default {
                Write-Log "Unknown action: $Action" -Level "ERROR" -Category "Manage"
                return $false
            }
        }
        
    } catch {
        Write-Log "Management action failed: $_" -Level "ERROR" -Category "Manage"
        return $false
    }
}

# Main execution
try {
    switch ($Command.ToLower()) {
        "help" {
            Show-Help
        }
        
        "init" {
            $result = Initialize-MCPSystem -Environment $Environment -Force:$Force -Verify:$Verify -AutoHeal:$AutoHeal
            if ($result) {
                Write-Host "MCP system initialized successfully" -ForegroundColor Green
            } else {
                Write-Host "MCP system initialization failed" -ForegroundColor Red
                exit 1
            }
        }
        
        "check" {
            $health = Test-SystemHealth -Environment $Environment
            if ($health.Healthy) {
                Write-Host "System is healthy" -ForegroundColor Green
            } else {
                Write-Host "System has issues:" -ForegroundColor Red
                foreach ($issue in $health.Issues) {
                    Write-Host "  - $issue" -ForegroundColor Red
                }
                exit 1
            }
        }
        
        "run" {
            $result = Start-MCPSystem -Environment $Environment -Background:$Background -AutoHeal:$AutoHeal -Verify:$Verify -Interval $Interval
            if ($result) {
                Write-Host "MCP system started successfully" -ForegroundColor Green
            } else {
                Write-Host "MCP system start failed" -ForegroundColor Red
                exit 1
            }
        }
        
        "stop" {
            $result = Stop-MCPSystem -Environment $Environment
            if ($result) {
                Write-Host "MCP system stopped successfully" -ForegroundColor Green
            } else {
                Write-Host "MCP system stop failed" -ForegroundColor Red
                exit 1
            }
        }
        
        "status" {
            $status = Get-MCPSystemStatus -Environment $Environment
            if ($status) {
                Write-Host "MCP System Status:" -ForegroundColor Cyan
                Write-Host "  Running: $($status.Running)" -ForegroundColor $(if ($status.Running) { "Green" } else { "Red" })
                Write-Host "  Environment: $($status.Environment)" -ForegroundColor Yellow
                Write-Host "  Last Check: $($status.LastCheck)" -ForegroundColor Yellow
            } else {
                Write-Host "Failed to get system status" -ForegroundColor Red
                exit 1
            }
        }
        
        "manage" {
            $result = Manage-MCPSystem -Action $Service -Service $Database -Database $Category -Category $Query -Query $StartDate -StartDate $EndDate -EndDate $Follow:$Follow
            if (-not $result) {
                exit 1
            }
        }
        
        default {
            Write-Host "Unknown command: $Command" -ForegroundColor Red
            Show-Help
            exit 1
        }
    }
    
} catch {
    Write-Log "Script execution failed: $_" -Level "ERROR" -Category "Main"
    Write-Host "Script execution failed: $_" -ForegroundColor Red
    exit 1
} 