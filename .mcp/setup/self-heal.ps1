# MCP Self-Healing and Prerequisite Checks
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# Check Prerequisites
function Test-Prerequisites {
    param (
        [string]$Environment = "local",
        [switch]$AutoHeal
    )
    
    Write-Log "Checking prerequisites..." -Level "INFO" -Category "MCP"
    
    try {
        $prerequisites = @{
            Directories = @{
                Status = $true
                Missing = @()
            }
            Services = @{
                Status = $true
                Failed = @()
            }
            DataSources = @{
                Status = $true
                Failed = @()
            }
            Config = @{
                Status = $true
                Issues = @()
            }
        }
        
        # Check required directories
        $requiredDirs = @(
            (Join-Path -Path $mcpPath -ChildPath "logs"),
            (Join-Path -Path $mcpPath -ChildPath "config"),
            (Join-Path -Path $mcpPath -ChildPath "data")
        )
        
        foreach ($dir in $requiredDirs) {
            if (-not (Test-Path -Path $dir)) {
                $prerequisites.Directories.Status = $false
                $prerequisites.Directories.Missing += $dir
                
                if ($AutoHeal) {
                    Write-Log "Creating missing directory: $dir" -Level "INFO" -Category "MCP"
                    New-Item -Path $dir -ItemType Directory -Force | Out-Null
                }
            }
        }
        
        # Check required services
        $services = @("mysql", "redis", "apache")
        foreach ($service in $services) {
            $status = Get-ServiceStatus -Service $service
            if ($status.Status -ne "Running") {
                $prerequisites.Services.Status = $false
                $prerequisites.Services.Failed += $service
                
                if ($AutoHeal) {
                    Write-Log "Attempting to start service: $service" -Level "INFO" -Category "MCP"
                    if (-not (Start-Service -Service $service -AutoHeal:$true)) {
                        Write-Log "Failed to start service: $service" -Level "ERROR" -Category "MCP"
                    }
                }
            }
        }
        
        # Check data sources
        $config = Get-EnvironmentConfig -Environment $Environment
        foreach ($source in $config.Data.Sources.Keys) {
            if (-not (Test-DataSource -Source $source -Environment $Environment)) {
                $prerequisites.DataSources.Status = $false
                $prerequisites.DataSources.Failed += $source
                
                if ($AutoHeal) {
                    Write-Log "Attempting to heal data source: $source" -Level "INFO" -Category "MCP"
                    # Add data source healing logic here
                }
            }
        }
        
        # Check configuration
        $configStatus = Get-ConfigStatus
        if (-not $configStatus.Healthy) {
            $prerequisites.Config.Status = $false
            $prerequisites.Config.Issues += $configStatus.Issues
            
            if ($AutoHeal) {
                Write-Log "Attempting to heal configuration" -Level "INFO" -Category "MCP"
                # Add config healing logic here
            }
        }
        
        # Return overall status
        $overallStatus = $prerequisites.Directories.Status -and 
                        $prerequisites.Services.Status -and 
                        $prerequisites.DataSources.Status -and 
                        $prerequisites.Config.Status
        
        if ($overallStatus) {
            Write-Log "All prerequisites met" -Level "SUCCESS" -Category "MCP"
        }
        else {
            Write-Log "Prerequisite check failed" -Level "ERROR" -Category "MCP"
        }
        
        return @{
            Status = $overallStatus
            Details = $prerequisites
        }
    }
    catch {
        Write-Log "Error checking prerequisites: $_" -Level "ERROR" -Category "MCP"
        return @{
            Status = $false
            Details = $null
            Error = $_.Exception.Message
        }
    }
}

# Self-Heal System
function Start-SelfHeal {
    param (
        [string]$Environment = "local",
        [int]$MaxRetries = 3,
        [int]$RetryDelay = 5
    )
    
    Write-Log "Starting self-healing process..." -Level "INFO" -Category "MCP"
    
    try {
        $retryCount = 0
        $healed = $false
        
        while (-not $healed -and $retryCount -lt $MaxRetries) {
            $prerequisites = Test-Prerequisites -Environment $Environment -AutoHeal:$true
            
            if ($prerequisites.Status) {
                $healed = $true
                Write-Log "Self-healing completed successfully" -Level "SUCCESS" -Category "MCP"
                break
            }
            
            $retryCount++
            if ($retryCount -lt $MaxRetries) {
                Write-Log "Retrying self-healing (Attempt $retryCount of $MaxRetries)..." -Level "INFO" -Category "MCP"
                Start-Sleep -Seconds $RetryDelay
            }
        }
        
        if (-not $healed) {
            Write-Log "Self-healing failed after $MaxRetries attempts" -Level "ERROR" -Category "MCP"
            return $false
        }
        
        return $true
    }
    catch {
        Write-Log "Error during self-healing: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Monitor System Health
function Start-HealthMonitor {
    param (
        [string]$Environment = "local",
        [int]$CheckInterval = 60,
        [switch]$AutoHeal
    )
    
    Write-Log "Starting health monitor..." -Level "INFO" -Category "MCP"
    
    try {
        $monitorJob = Start-Job -ScriptBlock {
            param($mcpPath, $Environment, $CheckInterval, $AutoHeal)
            
            $utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
            . (Join-Path -Path $utilsPath -ChildPath "init.ps1")
            
            while ($true) {
                $health = Test-MCPServerHealth -Detailed:$true
                
                if (-not $health.Overall -and $AutoHeal) {
                    Start-SelfHeal -Environment $Environment
                }
                
                Start-Sleep -Seconds $CheckInterval
            }
        } -ArgumentList $mcpPath, $Environment, $CheckInterval, $AutoHeal
        
        Write-Log "Health monitor started successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error starting health monitor: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Stop Health Monitor
function Stop-HealthMonitor {
    Write-Log "Stopping health monitor..." -Level "INFO" -Category "MCP"
    
    try {
        Get-Job | Where-Object { $_.Name -eq "HealthMonitor" } | Stop-Job
        Write-Log "Health monitor stopped successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error stopping health monitor: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Test-Prerequisites",
    "Start-SelfHeal",
    "Start-HealthMonitor",
    "Stop-HealthMonitor"
) 