# Master Test Runner Script
# Orchestrates all test suites and generates comprehensive reports

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

# Initialize test results
$testResults = @{
    StartTime = Get-Date
    EndTime = $null
    Suites = @()
    Environment = @{}
    Codespaces = @{}
    Features = @{}
    Summary = @{
        Total = 0
        Passed = 0
        Failed = 0
        Skipped = 0
    }
}

# Run environment tests
function Test-Environment {
    Write-TestLog "Running environment tests..." -Level "INFO"
    $envScript = Join-Path $scriptPath "test-environment.ps1"
    
    try {
        $envOutput = & $envScript 2>&1
        $envResult = $LASTEXITCODE
        
        $testResults.Environment = @{
            Status = $envResult -eq 0 ? "Passed" : "Failed"
            Output = $envOutput
            ExitCode = $envResult
        }
        
        Add-SuiteResult -Name "Environment" -Status $envResult -Output $envOutput
    } catch {
        $testResults.Environment = @{
            Status = "Failed"
            Error = $_.Exception.Message
        }
        Add-SuiteResult -Name "Environment" -Status 1 -Output $_.Exception.Message
    }
}

# Run Codespaces tests
function Test-Codespaces {
    Write-TestLog "Running Codespaces configuration tests..." -Level "INFO"
    $codespacesScript = Join-Path $scriptPath "test-codespaces.ps1"
    
    try {
        $codespacesOutput = & $codespacesScript 2>&1
        $codespacesResult = $LASTEXITCODE
        
        $testResults.Codespaces = @{
            Status = $codespacesResult -eq 0 ? "Passed" : "Failed"
            Output = $codespacesOutput
            ExitCode = $codespacesResult
        }
        
        Add-SuiteResult -Name "Codespaces" -Status $codespacesResult -Output $codespacesOutput
    } catch {
        $testResults.Codespaces = @{
            Status = "Failed"
            Error = $_.Exception.Message
        }
        Add-SuiteResult -Name "Codespaces" -Status 1 -Output $_.Exception.Message
    }
}

# Run feature tests
function Test-Features {
    Write-TestLog "Running feature tests..." -Level "INFO"
    $featureScript = Join-Path $scriptPath "run-feature-tests.ps1"
    
    try {
        $featureOutput = & $featureScript 2>&1
        $featureResult = $LASTEXITCODE
        
        $testResults.Features = @{
            Status = $featureResult -eq 0 ? "Passed" : "Failed"
            Output = $featureOutput
            ExitCode = $featureResult
        }
        
        Add-SuiteResult -Name "Features" -Status $featureResult -Output $featureOutput
    } catch {
        $testResults.Features = @{
            Status = "Failed"
            Error = $_.Exception.Message
        }
        Add-SuiteResult -Name "Features" -Status 1 -Output $_.Exception.Message
    }
}

# Add suite result
function Add-SuiteResult {
    param (
        [string]$Name,
        [int]$Status,
        [string]$Output
    )

    $suiteResult = @{
        Name = $Name
        Status = $Status -eq 0 ? "Passed" : "Failed"
        Output = $Output
        Timestamp = Get-Date
    }

    $testResults.Suites += $suiteResult
    $testResults.Summary.Total++
    
    if ($Status -eq 0) {
        $testResults.Summary.Passed++
    } else {
        $testResults.Summary.Failed++
    }
}

# Generate master report
function Write-MasterReport {
    $testResults.EndTime = Get-Date
    $duration = $testResults.EndTime - $testResults.StartTime

    $report = @"
# Master Test Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Summary
- Total Suites: $($testResults.Summary.Total)
- Passed: $($testResults.Summary.Passed)
- Failed: $($testResults.Summary.Failed)
- Duration: $($duration.TotalSeconds) seconds

## Test Suites
"@

    foreach ($suite in $testResults.Suites) {
        $report += @"

### $($suite.Name)
- Status: $($suite.Status)
- Time: $($suite.Timestamp)
- Output:
```
$($suite.Output)
```
"@
    }

    # Save report
    $reportPath = Join-Path (Join-Path $scriptPath "..") ".test/results/master-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Master report generated: $reportPath" -Level "INFO"

    # Save JSON data
    $jsonPath = Join-Path (Join-Path $scriptPath "..") ".test/tracking/master-tracking-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $testResults | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath
    Write-TestLog "Master tracking data saved: $jsonPath" -Level "INFO"

    # Update checklist
    Update-Checklist
}

# Update checklist
function Update-Checklist {
    $checklistPath = Join-Path (Join-Path $scriptPath "..") ".test/checklist-tracking.json"
    
    try {
        if (Test-Path $checklistPath) {
            $checklist = Get-Content $checklistPath -Raw | ConvertFrom-Json
            
            foreach ($suite in $testResults.Suites) {
                $item = $checklist.items | Where-Object { $_.name -eq $suite.Name }
                if ($item) {
                    $item.status = $suite.Status
                    $item.last_run = $suite.Timestamp
                    $item.result = @{
                        output = $suite.Output
                        report = "master-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
                    }
                }
            }
            
            $checklist | ConvertTo-Json -Depth 10 | Set-Content -Path $checklistPath
            Write-TestLog "Checklist updated: $checklistPath" -Level "INFO"
        }
    } catch {
        Write-TestError "Error updating checklist: $_"
    }
}

# Main execution
try {
    Write-TestLog "Starting master test execution..." -Level "INFO"

    Test-Environment
    Test-Codespaces
    Test-Features

    Write-MasterReport

    # Set exit code based on test results
    if ($testResults.Summary.Failed -gt 0) {
        Write-TestWarning "Tests completed with failures"
        exit 1
    } else {
        Write-TestLog "All tests passed successfully" -Level "SUCCESS"
        exit 0
    }
} catch {
    Write-TestError "Error during master test execution: $_"
    exit 1
} 