# Test Runner Script for Codespaces
$ErrorActionPreference = "Stop"

# Import health check script
. ./.codespaces/scripts/health-check.ps1

# Create test directories
$testDir = ".codespaces/testing"
$resultsDir = "$testDir/results"
$failuresDir = "$testDir/failures"
$completeDir = "$testDir/complete"

New-Item -ItemType Directory -Force -Path $testDir | Out-Null
New-Item -ItemType Directory -Force -Path $resultsDir | Out-Null
New-Item -ItemType Directory -Force -Path $failuresDir | Out-Null
New-Item -ItemType Directory -Force -Path $completeDir | Out-Null

# Generate timestamp for test run
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$testLogFile = "$testDir/test-run-$timestamp.log"

function Write-TestLog {
    param($Message)
    $logMessage = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): $Message"
    Add-Content -Path $testLogFile -Value $logMessage
    Write-Host $logMessage
}

# Run health check first
Write-TestLog "Running health check..."
$healthCheckResult = & ./.codespaces/scripts/health-check.ps1
if ($LASTEXITCODE -ne 0) {
    Write-TestLog "❌ Health check failed - aborting test run"
    Move-Item -Path $testLogFile -Destination "$failuresDir/test-run-$timestamp.log" -Force
    exit 1
}

Write-TestLog "✅ Health check passed - proceeding with tests"

# Run Laravel tests
Write-TestLog "Running Laravel tests..."
$testResult = php artisan test --log-junit "$resultsDir/test-results-$timestamp.xml"

if ($LASTEXITCODE -ne 0) {
    Write-TestLog "❌ Tests failed"
    Move-Item -Path $testLogFile -Destination "$failuresDir/test-run-$timestamp.log" -Force
    
    # Generate failure report
    $failureReport = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        status = "failed"
        testResults = $testResult
    }
    $failureReport | ConvertTo-Json | Set-Content "$failuresDir/test-failure-$timestamp.json"
    
    exit 1
}

# Generate completion report
$completionReport = @{
    timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    status = "completed"
    testResults = $testResult
}

# Save completion report
$completionReport | ConvertTo-Json | Set-Content "$completeDir/test-completion-$timestamp.json"

# Move log file to complete directory
Move-Item -Path $testLogFile -Destination "$completeDir/test-run-$timestamp.log" -Force

Write-TestLog "✅ All tests completed successfully"
exit 0 