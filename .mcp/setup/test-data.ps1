# Test script for data.ps1
$ErrorActionPreference = "Stop"
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "data.ps1")
. (Join-Path $scriptPath "environment.ps1")

$envs = @('Local', 'Remote')

foreach ($env in $envs) {
    Write-Host "\nTesting data sources in $env environment..." -ForegroundColor Cyan
    $config = Get-EnvironmentConfig -Environment $env
    foreach ($source in $config.Data.Sources.Keys) {
        Write-Host "Testing Test-DataSource for $source..."
        $result = Test-DataSource -Source $source -Environment $env
        if ($result) { Write-Host 'PASSED' -ForegroundColor Green } else { Write-Host 'FAILED' -ForegroundColor Red }
    }
} 