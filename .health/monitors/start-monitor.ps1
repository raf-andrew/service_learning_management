# Start Health Monitor
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "health-monitor.ps1")

# Parse command line arguments
param (
    [int]$Interval = 300,  # Default: 5 minutes
    [switch]$AutoHeal,
    [switch]$Background
)

# Start monitoring
if ($Background) {
    # Start in background
    $job = Start-Job -ScriptBlock {
        param($Interval, $AutoHeal)
        . (Join-Path $using:scriptPath "health-monitor.ps1")
        Start-Monitoring -Interval $Interval -AutoHeal:$AutoHeal
    } -ArgumentList $Interval, $AutoHeal

    Write-Host "Health monitoring started in background (Job ID: $($job.Id))"
} else {
    # Start in foreground
    Start-Monitoring -Interval $Interval -AutoHeal:$AutoHeal
} 