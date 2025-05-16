# Run Health Checks
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "health-check.ps1")
. (Join-Path $scriptPath ".." "healers" "self-healer.ps1")

# Parse command line arguments
param (
    [switch]$AutoHeal,
    [switch]$Detailed
)

# Run health checks
$results = Start-HealthChecks

# Display results
Write-Host "`nHealth Check Results:"
Write-Host "===================="
Write-Host "Status: $($results.Status)"
Write-Host "Environment: $($results.Environment)"
Write-Host "Timestamp: $($results.Timestamp)"
Write-Host "`nChecks:"

foreach ($check in $results.Checks) {
    $color = switch ($check.Status) {
        "Healthy" { "Green" }
        "Unhealthy" { "Red" }
        "Error" { "Red" }
        default { "Yellow" }
    }

    Write-Host "`n$($check.Name) ($($check.Category)):" -ForegroundColor $color
    Write-Host "  Status: $($check.Status)"
    Write-Host "  Message: $($check.Message)"
    
    if ($Detailed -and $check.Details) {
        Write-Host "  Details:"
        $check.Details.GetEnumerator() | ForEach-Object {
            Write-Host "    $($_.Key): $($_.Value)"
        }
    }
}

# Attempt auto-healing if enabled
if ($AutoHeal -and $results.Status -ne "Healthy") {
    Write-Host "`nAttempting auto-healing..."
    foreach ($issue in $results.Issues) {
        $healed = Start-Healing -Issue $issue
        if ($healed) {
            Write-Host "Successfully healed: $($issue.Check)" -ForegroundColor "Green"
        } else {
            Write-Host "Failed to heal: $($issue.Check)" -ForegroundColor "Red"
        }
    }
}

# Set exit code
if ($results.Status -eq "Healthy") {
    exit 0
} else {
    exit 1
} 