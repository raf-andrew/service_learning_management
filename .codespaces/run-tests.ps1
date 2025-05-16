# Run Tests in Codespaces
$ErrorActionPreference = "Stop"

# Ensure Codespaces is enabled
$envContent = Get-Content ".env" -Raw
if (-not ($envContent -match "CODESPACES_ENABLED=true")) {
    Write-Error "Codespaces is not enabled. Please run setup.ps1 first."
    exit 1
}

# Run the test command
Write-Host "Running tests in Codespaces environment..."
php artisan codespaces:test

# Check for failures
$failuresDir = ".codespaces/testing/.test/.failures"
if (Test-Path $failuresDir) {
    $failures = Get-ChildItem $failuresDir -Filter "*.failure"
    if ($failures.Count -gt 0) {
        Write-Host "`nTest failures found:"
        foreach ($failure in $failures) {
            Write-Host "- $($failure.Name)"
            # Delete failure logs
            Remove-Item $failure.FullName -Force
        }
    }
}

# Show completed tests
$completeDir = ".codespaces/testing/.test/.complete"
if (Test-Path $completeDir) {
    $completed = Get-ChildItem $completeDir -Filter "*.complete"
    if ($completed.Count -gt 0) {
        Write-Host "`nCompleted tests:"
        foreach ($test in $completed) {
            Write-Host "- $($test.Name)"
        }
    }
}

Write-Host "`nTest execution complete. Check .codespaces/testing/.test/results for detailed reports." 