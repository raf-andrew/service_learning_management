# Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath ".." "utils" "environment.ps1")
. (Join-Path $scriptPath ".." "checks" "health-check.ps1")
. (Join-Path $scriptPath ".." "healers" "self-healer.ps1")

# Monitoring configuration
$monitorConfig = @{
    CheckInterval = 300  # 5 minutes
    MaxRetries = 3
    AlertThreshold = 2   # Number of consecutive failures before alerting
}

# Monitoring state
$monitorState = @{
    LastCheck = $null
    ConsecutiveFailures = 0
    Issues = @()
    IsRunning = $false
}

# Start monitoring
function Start-Monitoring {
    param (
        [int]$Interval = $monitorConfig.CheckInterval,
        [switch]$AutoHeal
    )

    Write-Log "Starting health monitoring..." -Level "INFO" -Category "Monitor"
    $monitorState.IsRunning = $true

    try {
        while ($monitorState.IsRunning) {
            $monitorState.LastCheck = Get-Date
            Write-Log "Running health check..." -Level "INFO" -Category "Monitor"

            # Run health checks
            $results = Start-HealthChecks

            if ($results.Status -eq "Healthy") {
                $monitorState.ConsecutiveFailures = 0
                Write-Log "System is healthy" -Level "SUCCESS" -Category "Monitor"
            } else {
                $monitorState.ConsecutiveFailures++
                $monitorState.Issues = $results.Issues

                Write-Log "System is unhealthy. Issues found: $($results.Issues.Count)" -Level "WARNING" -Category "Monitor"
                
                # Log each issue
                foreach ($issue in $results.Issues) {
                    Write-Log "Issue: $($issue.Check) - $($issue.Message)" -Level "WARNING" -Category "Monitor"
                }

                # Attempt auto-healing if enabled
                if ($AutoHeal) {
                    foreach ($issue in $results.Issues) {
                        $healed = Start-Healing -Issue $issue
                        if ($healed) {
                            Write-Log "Auto-healed: $($issue.Check)" -Level "SUCCESS" -Category "Monitor"
                        }
                    }
                }

                # Alert if too many consecutive failures
                if ($monitorState.ConsecutiveFailures -ge $monitorConfig.AlertThreshold) {
                    Write-Log "ALERT: System has been unhealthy for $($monitorState.ConsecutiveFailures) consecutive checks" -Level "ERROR" -Category "Monitor"
                }
            }

            # Wait for next check
            Start-Sleep -Seconds $Interval
        }
    }
    catch {
        Write-Log "Error in monitoring: $_" -Level "ERROR" -Category "Monitor"
        $monitorState.IsRunning = $false
        throw
    }
}

# Stop monitoring
function Stop-Monitoring {
    Write-Log "Stopping health monitoring..." -Level "INFO" -Category "Monitor"
    $monitorState.IsRunning = $false
}

# Get monitoring status
function Get-MonitoringStatus {
    return @{
        IsRunning = $monitorState.IsRunning
        LastCheck = $monitorState.LastCheck
        ConsecutiveFailures = $monitorState.ConsecutiveFailures
        CurrentIssues = $monitorState.Issues
    }
}

# Export functions
Export-ModuleMember -Function Start-Monitoring, Stop-Monitoring, Get-MonitoringStatus 