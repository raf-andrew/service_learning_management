# Test script for environment.ps1
$ErrorActionPreference = "Stop"
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "environment.ps1")

Write-Host "Testing Get-EnvironmentConfig for 'Local'..."
$localConfig = Get-EnvironmentConfig -Environment 'Local'
if ($null -eq $localConfig) { Write-Host 'FAILED' -ForegroundColor Red; exit 1 } else { Write-Host 'PASSED' -ForegroundColor Green }

Write-Host "Testing Get-EnvironmentConfig for 'Remote'..."
$remoteConfig = Get-EnvironmentConfig -Environment 'Remote'
if ($null -eq $remoteConfig) { Write-Host 'FAILED' -ForegroundColor Red; exit 1 } else { Write-Host 'PASSED' -ForegroundColor Green }

Write-Host "Testing Test-EnvironmentConnectivity for 'Local'..."
$localConn = Test-EnvironmentConnectivity -Environment 'Local'
Write-Host "Connectivity: $localConn"

Write-Host "Testing Test-EnvironmentConnectivity for 'Remote'..."
$remoteConn = Test-EnvironmentConnectivity -Environment 'Remote'
Write-Host "Connectivity: $remoteConn" 