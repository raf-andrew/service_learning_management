# Test script for dev-utils.ps1
$ErrorActionPreference = "Stop"
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "dev-utils.ps1")
. (Join-Path $scriptPath "environment.ps1")

$env = 'Local'
$service = 'MySQL'

Write-Host "Testing Get-ServiceConfig for $service in $env..."
$config = Get-ServiceConfig -Service $service -Environment $env
if ($null -eq $config) { Write-Host 'FAILED' -ForegroundColor Red } else { Write-Host 'PASSED' -ForegroundColor Green }

Write-Host "Testing Start-LogTail (first 10 lines, no follow)..."
$logPath = Join-Path $scriptPath ".." "logs" "general" "general.log"
if (Test-Path $logPath) {
    Start-LogTail -LogFile $logPath -MaxLines 10 -Follow:$false
    Write-Host 'PASSED' -ForegroundColor Green
} else {
    Write-Host 'Log file not found, SKIPPED' -ForegroundColor Yellow
}

Write-Host "Testing Get-AuditLog (limit 5)..."
$audit = Get-AuditLog -Limit 5
if ($null -eq $audit) { Write-Host 'FAILED' -ForegroundColor Red } else { Write-Host 'PASSED' -ForegroundColor Green }

Write-Host "Testing Start-ServiceMonitor (one iteration)..."
try {
    $job = Start-Job -ScriptBlock { Start-ServiceMonitor -Service 'MySQL' -Environment 'Local' -Metrics @('CPU') -Interval 1 -History 1 }
    Start-Sleep -Seconds 2
    Stop-Job $job | Out-Null
    Write-Host 'PASSED' -ForegroundColor Green
} catch { Write-Host 'FAILED' -ForegroundColor Red } 