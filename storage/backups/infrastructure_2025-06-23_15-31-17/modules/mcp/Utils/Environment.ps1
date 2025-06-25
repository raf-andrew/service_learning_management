# MCP Environment Utility
# Provides environment management functionality for the MCP system

function Initialize-Environment {
    param(
        [string]$Environment = "local",
        [switch]$Force
    )
    
    try {
        Write-Log "Initializing environment: $Environment" -Level "INFO" -Category "Environment"
        
        # Set environment variables
        $envVars = Get-EnvironmentVariables -Environment $Environment
        
        foreach ($var in $envVars.GetEnumerator()) {
            [Environment]::SetEnvironmentVariable($var.Key, $var.Value, "Process")
        }
        
        # Create environment-specific directories
        $directories = Get-EnvironmentDirectories -Environment $Environment
        
        foreach ($dir in $directories) {
            if (-not (Test-Path $dir)) {
                New-Item -ItemType Directory -Path $dir -Force | Out-Null
                Write-Log "Created directory: $dir" -Level "INFO" -Category "Environment"
            }
        }
        
        # Validate environment setup
        $validation = Test-EnvironmentSetup -Environment $Environment
        
        if (-not $validation.Valid) {
            if ($Force) {
                Write-Log "Environment validation failed, but continuing due to Force flag" -Level "WARN" -Category "Environment"
            } else {
                return @{ Success = $false; Error = "Environment validation failed: $($validation.Error)" }
            }
        }
        
        Write-Log "Environment initialized successfully: $Environment" -Level "SUCCESS" -Category "Environment"
        return @{ Success = $true; Environment = $Environment }
        
    } catch {
        Write-Log "Environment initialization failed: $_" -Level "ERROR" -Category "Environment"
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Get-EnvironmentVariables {
    param([string]$Environment)
    
    $baseVars = @{
        "APP_ENV" = $Environment
        "APP_DEBUG" = if ($Environment -eq "local") { "true" } else { "false" }
        "APP_KEY" = "base64:" + [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes((New-Guid).ToString()))
        "APP_URL" = if ($Environment -eq "local") { "http://localhost" } else { "https://api.example.com" }
        "LOG_CHANNEL" = "stack"
        "LOG_LEVEL" = if ($Environment -eq "local") { "debug" } else { "info" }
    }
    
    switch ($Environment.ToLower()) {
        "local" {
            $baseVars += @{
                "DB_CONNECTION" = "mysql"
                "DB_HOST" = "127.0.0.1"
                "DB_PORT" = "3306"
                "DB_DATABASE" = "service_learning_management"
                "DB_USERNAME" = "root"
                "DB_PASSWORD" = ""
                "REDIS_HOST" = "127.0.0.1"
                "REDIS_PORT" = "6379"
                "REDIS_PASSWORD" = ""
                "CACHE_DRIVER" = "file"
                "SESSION_DRIVER" = "file"
                "QUEUE_CONNECTION" = "sync"
            }
        }
        "staging" {
            $baseVars += @{
                "DB_CONNECTION" = "mysql"
                "DB_HOST" = "staging-db.example.com"
                "DB_PORT" = "3306"
                "DB_DATABASE" = "slm_staging"
                "DB_USERNAME" = "slm_staging_user"
                "DB_PASSWORD" = "staging_password"
                "REDIS_HOST" = "staging-redis.example.com"
                "REDIS_PORT" = "6379"
                "REDIS_PASSWORD" = "staging_redis_password"
                "CACHE_DRIVER" = "redis"
                "SESSION_DRIVER" = "redis"
                "QUEUE_CONNECTION" = "redis"
            }
        }
        "production" {
            $baseVars += @{
                "DB_CONNECTION" = "mysql"
                "DB_HOST" = "prod-db.example.com"
                "DB_PORT" = "3306"
                "DB_DATABASE" = "slm_production"
                "DB_USERNAME" = "slm_prod_user"
                "DB_PASSWORD" = "production_password"
                "REDIS_HOST" = "prod-redis.example.com"
                "REDIS_PORT" = "6379"
                "REDIS_PASSWORD" = "prod_redis_password"
                "CACHE_DRIVER" = "redis"
                "SESSION_DRIVER" = "redis"
                "QUEUE_CONNECTION" = "redis"
            }
        }
        default {
            throw "Unknown environment: $Environment"
        }
    }
    
    # Add module-specific variables
    $baseVars += Get-ModuleEnvironmentVariables -Environment $Environment
    
    return $baseVars
}

function Get-ModuleEnvironmentVariables {
    param([string]$Environment)
    
    $moduleVars = @{
        # E2EE Module
        "E2EE_ENABLED" = "true"
        "E2EE_ENCRYPTION_ALGORITHM" = "AES-256-GCM"
        "E2EE_KEY_SIZE" = "32"
        "E2EE_IV_SIZE" = "16"
        "E2EE_AUTH_TAG_SIZE" = "16"
        "E2EE_DERIVATION_ITERATIONS" = "100000"
        "E2EE_AUDIT_ENABLED" = "true"
        "E2EE_CACHE_TTL" = "3600"
        "E2EE_CLEANUP_INTERVAL" = "30"
        
        # SOC2 Module
        "SOC2_ENABLED" = "true"
        "SOC2_TYPE" = "Type II"
        "SOC2_AUDIT_ENABLED" = "true"
        "SOC2_AUDIT_RETENTION_DAYS" = "2555"
        "SOC2_AUDIT_ENCRYPT_LOGS" = "true"
        "SOC2_COMPLIANCE_SCORE_THRESHOLD" = "90"
        "SOC2_SECURITY_SCORE_THRESHOLD" = "85"
        
        # Web3 Module
        "WEB3_ENABLED" = "true"
        "WEB3_NETWORK" = if ($Environment -eq "local") { "localhost" } else { "mainnet" }
        "WEB3_PORT" = "8545"
        "WEB3_CONTRACT_ADDRESS" = ""
        
        # MCP Module
        "MCP_ENABLED" = "true"
        "MCP_ENVIRONMENT" = $Environment
        "MCP_LOG_LEVEL" = if ($Environment -eq "local") { "DEBUG" } else { "INFO" }
        "MCP_AUTO_HEAL" = if ($Environment -eq "production") { "true" } else { "false" }
    }
    
    return $moduleVars
}

function Get-EnvironmentDirectories {
    param([string]$Environment)
    
    $baseDirs = @(
        "storage/logs",
        "storage/framework/cache",
        "storage/framework/sessions",
        "storage/framework/views",
        "storage/app/public",
        "bootstrap/cache"
    )
    
    $moduleDirs = @(
        "modules/e2ee/storage",
        "modules/e2ee/database",
        "modules/soc2/storage",
        "modules/soc2/database",
        "modules/web3/contracts",
        "modules/mcp/logs",
        "modules/mcp/backups"
    )
    
    $envDirs = @(
        "storage/logs/$Environment",
        "storage/app/$Environment",
        "modules/mcp/logs/$Environment"
    )
    
    return $baseDirs + $moduleDirs + $envDirs
}

function Test-EnvironmentSetup {
    param([string]$Environment)
    
    try {
        $issues = @()
        
        # Check environment variables
        $envVars = Get-EnvironmentVariables -Environment $Environment
        foreach ($var in $envVars.GetEnumerator()) {
            $value = [Environment]::GetEnvironmentVariable($var.Key, "Process")
            if (-not $value) {
                $issues += "Environment variable $($var.Key) not set"
            }
        }
        
        # Check required directories
        $directories = Get-EnvironmentDirectories -Environment $Environment
        foreach ($dir in $directories) {
            if (-not (Test-Path $dir)) {
                $issues += "Directory not found: $dir"
            }
        }
        
        # Check environment-specific requirements
        switch ($Environment.ToLower()) {
            "local" {
                # Check local development tools
                $tools = @("php", "composer", "node", "npm")
                foreach ($tool in $tools) {
                    try {
                        $null = Get-Command $tool -ErrorAction Stop
                    } catch {
                        $issues += "Required tool not found: $tool"
                    }
                }
            }
            "staging" {
                # Check staging-specific requirements
                if (-not (Test-NetworkConnectivity -Host "staging-db.example.com" -Port 3306)) {
                    $issues += "Cannot connect to staging database"
                }
            }
            "production" {
                # Check production-specific requirements
                if (-not (Test-NetworkConnectivity -Host "prod-db.example.com" -Port 3306)) {
                    $issues += "Cannot connect to production database"
                }
            }
        }
        
        return @{
            Valid = $issues.Count -eq 0
            Issues = $issues
            Error = if ($issues.Count -gt 0) { $issues -join "; " } else { $null }
        }
        
    } catch {
        return @{
            Valid = $false
            Issues = @("Environment setup test failed: $($_.Exception.Message)")
            Error = $_.Exception.Message
        }
    }
}

function Test-NetworkConnectivity {
    param([string]$Host, [int]$Port)
    
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $tcpClient.ConnectAsync($Host, $Port).Wait(5000)
        $isConnected = $tcpClient.Connected
        $tcpClient.Close()
        return $isConnected
    } catch {
        return $false
    }
}

function Get-EnvironmentInfo {
    param([string]$Environment = "local")
    
    try {
        $info = @{
            Environment = $Environment
            Variables = Get-EnvironmentVariables -Environment $Environment
            Directories = Get-EnvironmentDirectories -Environment $Environment
            Validation = Test-EnvironmentSetup -Environment $Environment
            SystemInfo = Get-SystemInfo
        }
        
        return $info
        
    } catch {
        return @{ Error = $_.Exception.Message }
    }
}

function Get-SystemInfo {
    return @{
        OS = $env:OS
        ComputerName = $env:COMPUTERNAME
        UserName = $env:USERNAME
        PowerShellVersion = $PSVersionTable.PSVersion.ToString()
        .NETVersion = [System.Environment]::Version.ToString()
        WorkingDirectory = Get-Location
        AvailableMemory = [System.Math]::Round((Get-WmiObject -Class Win32_ComputerSystem).TotalPhysicalMemory / 1GB, 2)
        DiskSpace = Get-DiskSpace
    }
}

function Get-DiskSpace {
    try {
        $drive = Get-WmiObject -Class Win32_LogicalDisk -Filter "DeviceID='C:'"
        return @{
            Total = [System.Math]::Round($drive.Size / 1GB, 2)
            Free = [System.Math]::Round($drive.FreeSpace / 1GB, 2)
            Used = [System.Math]::Round(($drive.Size - $drive.FreeSpace) / 1GB, 2)
            PercentFree = [System.Math]::Round(($drive.FreeSpace / $drive.Size) * 100, 2)
        }
    } catch {
        return @{ Error = "Could not retrieve disk space information" }
    }
}

function Switch-Environment {
    param([string]$Environment)
    
    try {
        Write-Log "Switching to environment: $Environment" -Level "INFO" -Category "Environment"
        
        # Validate target environment
        $validation = Test-EnvironmentSetup -Environment $Environment
        if (-not $validation.Valid) {
            return @{ Success = $false; Error = "Target environment validation failed: $($validation.Error)" }
        }
        
        # Initialize new environment
        $result = Initialize-Environment -Environment $Environment
        if (-not $result.Success) {
            return @{ Success = $false; Error = "Failed to initialize environment: $($result.Error)" }
        }
        
        Write-Log "Successfully switched to environment: $Environment" -Level "SUCCESS" -Category "Environment"
        return @{ Success = $true; Environment = $Environment }
        
    } catch {
        Write-Log "Environment switch failed: $_" -Level "ERROR" -Category "Environment"
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Initialize-Environment",
    "Get-EnvironmentVariables",
    "Test-EnvironmentSetup",
    "Get-EnvironmentInfo",
    "Switch-Environment"
) 