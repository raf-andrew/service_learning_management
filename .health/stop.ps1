# Stop Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")

# Parse command line arguments
param (
    [switch]$Force
)

# Stop health monitoring
function Stop-HealthMonitoring {
    Write-Log "Stopping health monitoring..." -Level "INFO" -Category "Monitor"
    
    try {
        # Get monitoring jobs
        $jobs = Get-Job | Where-Object { $_.Command -like "*Start-HealthMonitoring*" }
        
        if ($jobs.Count -eq 0) {
            Write-Log "No monitoring jobs found" -Level "WARNING" -Category "Monitor"
            return $true
        }
        
        # Stop jobs
        foreach ($job in $jobs) {
            Write-Log "Stopping job $($job.Id)..." -Level "INFO" -Category "Monitor"
            Stop-Job -Job $job
            Remove-Job -Job $job
        }
        
        Write-Log "Health monitoring stopped successfully" -Level "SUCCESS" -Category "Monitor"
        return $true
    }
    catch {
        Write-Log "Error stopping health monitoring: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Main execution
try {
    Write-Host "Stopping Health Monitoring System..."
    Write-Host "================================="
    
    # Stop monitoring
    if (-not (Stop-HealthMonitoring)) {
        if (-not $Force) {
            Write-Host "Failed to stop monitoring" -ForegroundColor "Red"
            exit 1
        }
    }
    
    Write-Host "`nHealth Monitoring System stopped successfully!" -ForegroundColor "Green"
}
catch {
    Write-Log "Error stopping health monitoring system: $_" -Level "ERROR" -Category "Monitor"
    exit 1
} 