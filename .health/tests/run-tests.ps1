# Run Health Check Tests
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "test-framework.ps1")

# Parse command line arguments
param (
    [string[]]$Categories,
    [switch]$AutoHeal,
    [switch]$Continuous,
    [int]$Interval = 300
)

# Run tests
if ($Continuous) {
    Write-Host "Running tests continuously every $Interval seconds..."
    Write-Host "Press Ctrl+C to stop"
    
    try {
        while ($true) {
            $results = Start-Tests -Categories $Categories -AutoHeal:$AutoHeal
            
            if ($results.Failed -gt 0) {
                Write-Host "`nWaiting for next test run..." -ForegroundColor "Yellow"
            } else {
                Write-Host "`nWaiting for next test run..." -ForegroundColor "Green"
            }
            
            Start-Sleep -Seconds $Interval
        }
    }
    catch {
        Write-Host "`nStopped running tests"
    }
} else {
    $results = Start-Tests -Categories $Categories -AutoHeal:$AutoHeal
    
    # Set exit code based on test results
    if ($results.Failed -gt 0) {
        exit 1
    } else {
        exit 0
    }
} 