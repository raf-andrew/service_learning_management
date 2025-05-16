# Verify Deployment and Health Check Status
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "environment.ps1")
. (Join-Path $scriptPath "github.ps1")

# Parse command line arguments
param (
    [string]$Environment = "production",
    [switch]$Detailed,
    [switch]$AutoHeal
)

# Verify deployment
function Test-Deployment {
    param (
        [string]$Environment
    )
    
    Write-Log "Verifying deployment for environment: $Environment" -Level "INFO" -Category "Deployment"
    
    try {
        # Get deployment status
        $token = gh auth token
        $headers = @{
            "Authorization" = "token $token"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $repo = git config --get remote.origin.url | ForEach-Object { $_ -replace ".*[:/]([^/]+/[^/]+)\.git$", '$1' }
        $response = Invoke-RestMethod -Uri "$($githubConfig.API)/repos/$repo/deployments?environment=$Environment" -Headers $headers
        
        if ($response.Count -eq 0) {
            Write-Log "No deployments found for environment: $Environment" -Level "WARNING" -Category "Deployment"
            return $false
        }
        
        $latestDeployment = $response[0]
        $deploymentStatus = Invoke-RestMethod -Uri "$($githubConfig.API)/repos/$repo/deployments/$($latestDeployment.id)/statuses" -Headers $headers
        
        $status = @{
            State = $deploymentStatus[0].state
            Description = $deploymentStatus[0].description
            Environment = $latestDeployment.environment
            CreatedAt = $latestDeployment.created_at
            UpdatedAt = $deploymentStatus[0].updated_at
        }
        
        Write-Log "Deployment status: $($status.State)" -Level "INFO" -Category "Deployment"
        return $status
    }
    catch {
        Write-Log "Error verifying deployment: $_" -Level "ERROR" -Category "Deployment"
        return $null
    }
}

# Verify health check status
function Test-HealthCheckStatus {
    param (
        [string]$Environment
    )
    
    Write-Log "Verifying health check status for environment: $Environment" -Level "INFO" -Category "HealthCheck"
    
    try {
        # Get latest test results
        $logsPath = Join-Path $scriptPath ".." "logs"
        $latestResults = Get-ChildItem -Path $logsPath -Filter "test-results-*.json" | 
            Sort-Object LastWriteTime -Descending | 
            Select-Object -First 1
        
        if (-not $latestResults) {
            Write-Log "No test results found" -Level "WARNING" -Category "HealthCheck"
            return $null
        }
        
        $results = Get-Content $latestResults.FullName | ConvertFrom-Json
        
        $status = @{
            Total = $results.Total
            Passed = $results.Passed
            Failed = $results.Failed
            Skipped = $results.Skipped
            StartTime = $results.StartTime
            EndTime = $results.EndTime
            FailedTests = $results.Tests | Where-Object { $_.Status -eq "Failed" }
        }
        
        Write-Log "Health check status: $($status.Passed) passed, $($status.Failed) failed" -Level "INFO" -Category "HealthCheck"
        return $status
    }
    catch {
        Write-Log "Error verifying health check status: $_" -Level "ERROR" -Category "HealthCheck"
        return $null
    }
}

# Verify service health
function Test-ServiceHealth {
    param (
        [string]$Environment
    )
    
    Write-Log "Verifying service health for environment: $Environment" -Level "INFO" -Category "ServiceHealth"
    
    try {
        $config = Get-EnvironmentConfig -Environment $Environment
        $services = @("MySQL", "Redis", "Apache")
        $results = @{}
        
        foreach ($service in $services) {
            $serviceConfig = Get-ServiceConfig -Service $service
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect($serviceConfig.Host, $serviceConfig.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                $tcpClient.Close()
                
                $results[$service] = @{
                    Status = if ($success) { "Healthy" } else { "Unhealthy" }
                    Host = $serviceConfig.Host
                    Port = $serviceConfig.Port
                }
            }
            catch {
                $results[$service] = @{
                    Status = "Error"
                    Host = $serviceConfig.Host
                    Port = $serviceConfig.Port
                    Error = $_.Exception.Message
                }
            }
        }
        
        Write-Log "Service health check completed" -Level "INFO" -Category "ServiceHealth"
        return $results
    }
    catch {
        Write-Log "Error verifying service health: $_" -Level "ERROR" -Category "ServiceHealth"
        return $null
    }
}

# Main verification
try {
    # Verify deployment
    $deploymentStatus = Test-Deployment -Environment $Environment
    if ($deploymentStatus) {
        Write-Host "`nDeployment Status:"
        Write-Host "================="
        Write-Host "State: $($deploymentStatus.State)"
        Write-Host "Environment: $($deploymentStatus.Environment)"
        Write-Host "Created: $($deploymentStatus.CreatedAt)"
        Write-Host "Updated: $($deploymentStatus.UpdatedAt)"
        Write-Host "Description: $($deploymentStatus.Description)"
    }
    
    # Verify health check status
    $healthStatus = Test-HealthCheckStatus -Environment $Environment
    if ($healthStatus) {
        Write-Host "`nHealth Check Status:"
        Write-Host "==================="
        Write-Host "Total Tests: $($healthStatus.Total)"
        Write-Host "Passed: $($healthStatus.Passed)"
        Write-Host "Failed: $($healthStatus.Failed)"
        Write-Host "Skipped: $($healthStatus.Skipped)"
        Write-Host "Duration: $([math]::Round(($healthStatus.EndTime - $healthStatus.StartTime).TotalSeconds)) seconds"
        
        if ($healthStatus.Failed -gt 0) {
            Write-Host "`nFailed Tests:"
            foreach ($test in $healthStatus.FailedTests) {
                Write-Host "- $($test.Name) ($($test.Category)): $($test.Message)" -ForegroundColor "Red"
            }
        }
    }
    
    # Verify service health
    $serviceHealth = Test-ServiceHealth -Environment $Environment
    if ($serviceHealth) {
        Write-Host "`nService Health:"
        Write-Host "=============="
        foreach ($service in $serviceHealth.Keys) {
            $status = $serviceHealth[$service]
            $color = switch ($status.Status) {
                "Healthy" { "Green" }
                "Unhealthy" { "Red" }
                default { "Yellow" }
            }
            
            Write-Host "$service: $($status.Status)" -ForegroundColor $color
            if ($Detailed) {
                Write-Host "  Host: $($status.Host)"
                Write-Host "  Port: $($status.Port)"
                if ($status.Error) {
                    Write-Host "  Error: $($status.Error)" -ForegroundColor "Red"
                }
            }
        }
    }
    
    # Attempt auto-healing if enabled
    if ($AutoHeal) {
        if ($healthStatus.Failed -gt 0) {
            Write-Host "`nAttempting auto-healing..."
            foreach ($test in $healthStatus.FailedTests) {
                $issue = @{
                    Check = $test.Name
                    Category = $test.Category
                    Message = $test.Message
                    Details = $test.Details
                }
                
                $healed = Start-Healing -Issue $issue
                if ($healed) {
                    Write-Host "Successfully healed: $($test.Name)" -ForegroundColor "Green"
                } else {
                    Write-Host "Failed to heal: $($test.Name)" -ForegroundColor "Red"
                }
            }
        }
    }
}
catch {
    Write-Log "Error during verification: $_" -Level "ERROR" -Category "Verification"
    exit 1
} 