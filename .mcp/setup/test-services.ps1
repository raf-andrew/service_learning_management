# Test script for services.ps1
$ErrorActionPreference = "Stop"
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "services.ps1")

$envs = @('Local', 'Remote')
$services = @('MySQL', 'Redis', 'Apache')

foreach ($env in $envs) {
    Write-Host "\nTesting services in $env environment..." -ForegroundColor Cyan
    foreach ($svc in $services) {
        Write-Host "Testing Get-ServiceStatus for $svc..."
        $status = Get-ServiceStatus -Service $svc -Environment $env
        if ($null -eq $status) { Write-Host 'FAILED' -ForegroundColor Red } else { Write-Host 'PASSED' -ForegroundColor Green }

        Write-Host "Testing Test-ServiceHealth for $svc..."
        $health = Test-ServiceHealth -Service $svc -Environment $env
        Write-Host "Health: $($health.IsHealthy) - $($health.Message)"

        Write-Host "Testing Start-ServiceHealing for $svc..."
        $heal = Start-ServiceHealing -Service $svc -Environment $env
        Write-Host "Healing: $($heal.Success) - $($heal.Message)"

        Write-Host "Testing Get-ServiceMetrics for $svc..."
        $metrics = Get-ServiceMetrics -Service $svc -Environment $env
        if ($null -eq $metrics) { Write-Host 'FAILED' -ForegroundColor Red } else { Write-Host 'PASSED' -ForegroundColor Green }
    }
} 