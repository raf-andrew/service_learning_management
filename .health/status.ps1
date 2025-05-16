# Check Health Monitoring System Status
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [switch]$Detailed
)

# Check health monitoring status
function Get-HealthMonitoringStatus {
    Write-Log "Checking health monitoring status..." -Level "INFO" -Category "Monitor"
    
    try {
        # Get monitoring jobs
        $jobs = Get-Job | Where-Object { $_.Command -like "*Start-HealthMonitoring*" }
        
        $status = @{
            IsRunning = $jobs.Count -gt 0
            Jobs = @()
            LastCheck = $null
            Issues = @()
        }
        
        # Get job details
        foreach ($job in $jobs) {
            $jobStatus = @{
                Id = $job.Id
                State = $job.State
                StartTime = $job.PSBeginTime
                EndTime = $job.PSEndTime
                HasMoreData = $job.HasMoreData
                ChildJobs = $job.ChildJobs.Count
            }
            
            $status.Jobs += $jobStatus
        }
        
        # Get last check time
        $lastCheckFile = Join-Path $scriptPath "logs" "last-check.txt"
        if (Test-Path $lastCheckFile) {
            $status.LastCheck = Get-Content $lastCheckFile
        }
        
        # Get recent issues
        $issuesFile = Join-Path $scriptPath "logs" "issues.json"
        if (Test-Path $issuesFile) {
            $status.Issues = Get-Content $issuesFile | ConvertFrom-Json
        }
        
        Write-Log "Health monitoring status retrieved successfully" -Level "SUCCESS" -Category "Monitor"
        return $status
    }
    catch {
        Write-Log "Error checking health monitoring status: $_" -Level "ERROR" -Category "Monitor"
        return $null
    }
}

# Main execution
try {
    Write-Host "Checking Health Monitoring System Status..."
    Write-Host "========================================="
    
    # Get status
    $status = Get-HealthMonitoringStatus
    
    if ($null -eq $status) {
        Write-Host "Failed to get status" -ForegroundColor "Red"
        exit 1
    }
    
    # Display status
    Write-Host "`nStatus: $($status.IsRunning ? 'Running' : 'Stopped')" -ForegroundColor ($status.IsRunning ? "Green" : "Red")
    
    if ($status.IsRunning) {
        Write-Host "`nActive Jobs:"
        foreach ($job in $status.Jobs) {
            Write-Host "  - Job $($job.Id): $($job.State)"
            Write-Host "    Started: $($job.StartTime)"
            Write-Host "    Child Jobs: $($job.ChildJobs)"
        }
        
        if ($status.LastCheck) {
            Write-Host "`nLast Check: $($status.LastCheck)"
        }
        
        if ($status.Issues.Count -gt 0) {
            Write-Host "`nRecent Issues:"
            foreach ($issue in $status.Issues) {
                Write-Host "  - $($issue.Message)" -ForegroundColor "Yellow"
            }
        }
    }
    
    if ($Detailed) {
        # Get environment status
        Write-Host "`nEnvironment Status:"
        $envStatus = Get-EnvironmentStatus
        Write-Host "  - Current Environment: $($envStatus.CurrentEnvironment)"
        Write-Host "  - Services:"
        foreach ($service in $envStatus.Services) {
            Write-Host "    - $($service.Name): $($service.Status)" -ForegroundColor ($service.Status -eq "Running" ? "Green" : "Red")
        }
        
        # Get GitHub status
        Write-Host "`nGitHub Status:"
        $githubStatus = Get-GitHubStatus
        Write-Host "  - Authentication: $($githubStatus.Authentication)"
        Write-Host "  - Scopes: $($githubStatus.Scopes -join ', ')"
        Write-Host "  - Actions: $($githubStatus.Actions)"
        
        # Get log status
        Write-Host "`nLog Status:"
        $logDir = Join-Path $scriptPath "logs"
        if (Test-Path $logDir) {
            $logs = Get-ChildItem $logDir -File
            foreach ($log in $logs) {
                $size = [math]::Round($log.Length / 1KB, 2)
                Write-Host "  - $($log.Name): $size KB"
            }
        }
    }
}
catch {
    Write-Log "Error checking health monitoring system status: $_" -Level "ERROR" -Category "Monitor"
    exit 1
} 