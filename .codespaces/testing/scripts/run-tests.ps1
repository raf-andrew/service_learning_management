# Test Runner Script
# This script runs all test suites and generates a report

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger first
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

# Initialize test environment
$testResultsDir = Join-Path (Join-Path $scriptPath "..") "results"
$testLogsDir = Join-Path (Join-Path $scriptPath "..") "logs"

# Create directories if they don't exist
if (-not (Test-Path $testResultsDir)) {
    New-Item -ItemType Directory -Path $testResultsDir -Force | Out-Null
}
if (-not (Test-Path $testLogsDir)) {
    New-Item -ItemType Directory -Path $testLogsDir -Force | Out-Null
}

# Initialize test results
$testResults = @{
    Total = 0
    Passed = 0
    Failed = 0
    StartTime = Get-Date
    EndTime = $null
    Suites = @()
}

# Run test suite
function Run-TestSuite {
    param (
        [string]$SuiteName,
        [string]$ScriptPath
    )

    Write-TestLog "Running test suite: $SuiteName" -Level "INFO" -TestSuite $SuiteName
    $suiteResults = @{
        Name = $SuiteName
        Total = 0
        Passed = 0
        Failed = 0
        Tests = @()
    }

    try {
        # Run the test script
        $output = & $ScriptPath 2>&1
        $exitCode = $LASTEXITCODE

        # Parse test results from output
        $testLines = $output | Where-Object { $_ -match "^(PASS|FAIL):" }
        foreach ($line in $testLines) {
            $suiteResults.Total++
            $testResults.Total++

            if ($line -match "PASS:") {
                $suiteResults.Passed++
                $testResults.Passed++
            } else {
                $suiteResults.Failed++
                $testResults.Failed++
            }

            $suiteResults.Tests += @{
                Name = $line
                Status = if ($line -match "PASS:") { "Passed" } else { "Failed" }
            }
        }

        Write-TestLog "Test suite $SuiteName completed: $($suiteResults.Passed) passed, $($suiteResults.Failed) failed" -Level "INFO" -TestSuite $SuiteName
    } catch {
        Write-TestError "Error running test suite $SuiteName : $_" -TestSuite $SuiteName
        $suiteResults.Failed++
        $testResults.Failed++
    }

    $testResults.Suites += $suiteResults
    return $suiteResults
}

# Generate test report
function Write-TestReport {
    $testResults.EndTime = Get-Date
    $duration = $testResults.EndTime - $testResults.StartTime

    $report = @"
# Test Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Summary
- Total Tests: $($testResults.Total)
- Passed: $($testResults.Passed)
- Failed: $($testResults.Failed)
- Duration: $($duration.TotalSeconds) seconds

## Test Suites
"@

    foreach ($suite in $testResults.Suites) {
        $report += @"

### $($suite.Name)
- Total: $($suite.Total)
- Passed: $($suite.Passed)
- Failed: $($suite.Failed)

#### Test Results
"@
        foreach ($test in $suite.Tests) {
            $report += "- $($test.Name)`n"
        }
    }

    $reportPath = Join-Path $testResultsDir "test-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Test report generated: $reportPath" -Level "INFO"
}

# Main execution
try {
    Write-TestLog "Starting test execution..." -Level "INFO"

    # Install required tools first
    Write-TestLog "Installing required tools..." -Level "INFO"
    Write-TestLog "scriptPath is: $scriptPath" -Level "DEBUG"
    . (Join-Path $scriptPath "setup-tools.ps1")
    Write-TestLog "Finished installing required tools." -Level "DEBUG"
    if ($LASTEXITCODE -ne 0) {
        Write-TestError "Failed to install required tools"
        throw "Failed to install required tools"
    }

    # Run test suites
    Write-TestLog "Preparing to run test suites..." -Level "DEBUG"
    $testSuites = @(
        @{ Name = "Prerequisites"; Script = "test-prerequisites.ps1" },
        @{ Name = "Token Management"; Script = "test-token-management.ps1" },
        @{ Name = "State Management"; Script = "test-state-management.ps1" },
        @{ Name = "Setup Process"; Script = "test-setup-process.ps1" }
    )

    foreach ($suite in $testSuites) {
        $scriptPath = Join-Path $scriptPath $suite.Script
        Write-TestLog "Running suite: $($suite.Name) with script $scriptPath" -Level "DEBUG"
        Run-TestSuite -SuiteName $suite.Name -ScriptPath $scriptPath
    }

    # Generate report
    Write-TestLog "Generating test report..." -Level "DEBUG"
    Write-TestReport

    # Set exit code based on test results
    if ($testResults.Failed -gt 0) {
        Write-TestWarning "Tests completed with failures"
        exit 1
    } else {
        Write-TestLog "All tests passed successfully" -Level "SUCCESS"
        exit 0
    }
} catch {
    Write-TestError "Error during test execution: $_"
    exit 1
} 