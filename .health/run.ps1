# Run Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [string]$Environment = "local",
    [int]$Interval = 300,
    [switch]$AutoHeal,
    [switch]$Background,
    [switch]$Verify,
    [switch]$Force
)

# Run health monitoring
function Start-HealthMonitoring {
    param (
        [string]$Environment,
        [int]$Interval,
        [switch]$AutoHeal
    )
    
    Write-Log "Starting health monitoring..." -Level "INFO" -Category "Monitor"
    
    try {
        # Switch to environment
        if (-not (Switch-Environment -Environment $Environment)) {
            return $false
        }
        
        # Start monitoring
        $monitorConfig = @{
            CheckInterval = $Interval
            MaxRetries = 3
            AlertThreshold = 2
        }
        
        $monitorState = @{
            LastCheck = $null
            ConsecutiveFailures = 0
            Issues = @()
            IsRunning = $true
        }
        
        while ($monitorState.IsRunning) {
            try {
                # Run health checks
                $results = Start-HealthChecks -AutoHeal:$AutoHeal
                
                # Update state
                $monitorState.LastCheck = Get-Date
                if ($results.Status -eq "Healthy") {
                    $monitorState.ConsecutiveFailures = 0
                    $monitorState.Issues = @()
                } else {
                    $monitorState.ConsecutiveFailures++
                    $monitorState.Issues = $results.Issues
                    
                    # Alert if threshold reached
                    if ($monitorState.ConsecutiveFailures -ge $monitorConfig.AlertThreshold) {
                        Write-Log "Health check failed $($monitorState.ConsecutiveFailures) times in a row" -Level "ERROR" -Category "Monitor"
                        foreach ($issue in $monitorState.Issues) {
                            Write-Log "Issue: $($issue.Message)" -Level "ERROR" -Category "Monitor"
                        }
                    }
                }
                
                # Wait for next check
                Start-Sleep -Seconds $monitorConfig.CheckInterval
            }
            catch {
                Write-Log "Error during health check: $_" -Level "ERROR" -Category "Monitor"
                $monitorState.ConsecutiveFailures++
                
                if ($monitorState.ConsecutiveFailures -ge $monitorConfig.AlertThreshold) {
                    Write-Log "Health check failed $($monitorState.ConsecutiveFailures) times in a row" -Level "ERROR" -Category "Monitor"
                }
                
                Start-Sleep -Seconds $monitorConfig.CheckInterval
            }
        }
        
        Write-Log "Health monitoring stopped" -Level "INFO" -Category "Monitor"
        return $true
    }
    catch {
        Write-Log "Error starting health monitoring: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Main execution
try {
    Write-Host "Starting Health Monitoring System..."
    Write-Host "================================="
    
    # Initialize system if needed
    if (-not (Test-Path (Join-Path $scriptPath "logs"))) {
        Write-Host "`nInitializing system..."
        if (-not (. (Join-Path $scriptPath "init.ps1") -Environment $Environment -Verify:$Verify -AutoHeal:$AutoHeal -Force:$Force)) {
            Write-Host "Initialization failed" -ForegroundColor "Red"
            exit 1
        }
    }
    
    # Start monitoring
    if ($Background) {
        Write-Host "`nStarting monitoring in background..."
        $job = Start-Job -ScriptBlock {
            param($scriptPath, $Environment, $Interval, $AutoHeal)
            
            . (Join-Path $scriptPath "utils" "logger.ps1")
            . (Join-Path $scriptPath "utils" "environment.ps1")
            . (Join-Path $scriptPath "utils" "github.ps1")
            
            Start-HealthMonitoring -Environment $Environment -Interval $Interval -AutoHeal:$AutoHeal
        } -ArgumentList $scriptPath, $Environment, $Interval, $AutoHeal
        
        Write-Host "Monitoring started in background (Job ID: $($job.Id))" -ForegroundColor "Green"
    } else {
        Write-Host "`nStarting monitoring..."
        if (-not (Start-HealthMonitoring -Environment $Environment -Interval $Interval -AutoHeal:$AutoHeal)) {
            Write-Host "Failed to start monitoring" -ForegroundColor "Red"
            exit 1
        }
    }
}
catch {
    Write-Log "Error starting health monitoring system: $_" -Level "ERROR" -Category "Monitor"
    exit 1
} 