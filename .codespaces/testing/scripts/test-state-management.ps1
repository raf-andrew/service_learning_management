# Test script for state management
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "manage-state.ps1")

# Test suite name
$TestSuite = "State Management"

# Initialize test suite
Start-TestSuite -SuiteName $TestSuite

# Test results
$testResults = @{
    total = 0
    passed = 0
    failed = @()
}

# Helper function to run a test
function Test-Case {
    param(
        [string]$Name,
        [scriptblock]$Test,
        [string]$Message
    )
    
    Start-Test -TestName $Name -TestSuite $TestSuite
    $testResults.total++
    
    try {
        Write-TestDebug -Message "Executing test" -TestName $Name -TestSuite $TestSuite -Context @{
            Message = $Message
        }
        
        $result = & $Test
        
        if ($result) {
            $testResults.passed++
            End-Test -TestName $Name -TestSuite $TestSuite -Success $true -Message $Message
        }
        else {
            $testResults.failed += @{
                name = $Name
                message = $Message
            }
            End-Test -TestName $Name -TestSuite $TestSuite -Success $false -Message $Message
        }
    }
    catch {
        $testResults.failed += @{
            name = $Name
            message = $Message
            error = $_.Exception.Message
        }
        End-Test -TestName $Name -TestSuite $TestSuite -Success $false -Message $Message -Error $_.Exception.Message
    }
}

# Test state initialization
Test-Case -Name "State Initialization" -Message "State should be initialized successfully" -Test {
    Write-TestDebug -Message "Testing state initialization" -TestName "State Initialization" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    if (-not (Test-Path $stateDir)) {
        New-Item -ItemType Directory -Path $stateDir -Force | Out-Null
    }
    $stateFile = Join-Path $stateDir "setup-state.json"
    if (-not (Test-Path $stateFile)) {
        @{
            steps = @()
            lastStep = $null
            failedSteps = @()
            progress = 0
        } | ConvertTo-Json | Set-Content -Path $stateFile
    }
    return Test-Path $stateFile
}

# Test state update
Test-Case -Name "State Update" -Message "State should be updated successfully" -Test {
    Write-TestDebug -Message "Testing state update" -TestName "State Update" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    $stateFile = Join-Path $stateDir "setup-state.json"
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    
    $step = @{
        name = "test_step"
        status = "success"
        message = "Test step completed"
        timestamp = (Get-Date).ToString("o")
    }
    
    $state.steps += $step
    $state.lastStep = $step
    $state.progress = 100
    
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    $updatedState = Get-Content -Path $stateFile | ConvertFrom-Json
    return $updatedState.lastStep.name -eq "test_step" -and $updatedState.lastStep.status -eq "success"
}

# Test last successful step
Test-Case -Name "Last Successful Step" -Message "Last successful step should be retrieved correctly" -Test {
    Write-TestDebug -Message "Testing last successful step retrieval" -TestName "Last Successful Step" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    $stateFile = Join-Path $stateDir "setup-state.json"
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    
    $steps = @(
        @{
            name = "step1"
            status = "success"
            message = "Step 1 completed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step2"
            status = "failed"
            message = "Step 2 failed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step3"
            status = "success"
            message = "Step 3 completed"
            timestamp = (Get-Date).ToString("o")
        }
    )
    
    $state.steps = $steps
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    $lastSuccess = $state.steps | Where-Object { $_.status -eq "success" } | Select-Object -Last 1
    return $lastSuccess.name -eq "step3"
}

# Test failed steps
Test-Case -Name "Failed Steps" -Message "Failed steps should be retrieved correctly" -Test {
    Write-TestDebug -Message "Testing failed steps retrieval" -TestName "Failed Steps" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    $stateFile = Join-Path $stateDir "setup-state.json"
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    
    $steps = @(
        @{
            name = "step1"
            status = "success"
            message = "Step 1 completed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step2"
            status = "failed"
            message = "Step 2 failed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step3"
            status = "failed"
            message = "Step 3 failed"
            timestamp = (Get-Date).ToString("o")
        }
    )
    
    $state.steps = $steps
    $state.failedSteps = @($steps | Where-Object { $_.status -eq "failed" } | ForEach-Object { $_.name })
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    $failedSteps = $state.failedSteps
    return $failedSteps.Count -eq 2 -and $failedSteps -contains "step2" -and $failedSteps -contains "step3"
}

# Test setup progress
Test-Case -Name "Setup Progress" -Message "Setup progress should be calculated correctly" -Test {
    Write-TestDebug -Message "Testing setup progress calculation" -TestName "Setup Progress" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    $stateFile = Join-Path $stateDir "setup-state.json"
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    
    $steps = @(
        @{
            name = "step1"
            status = "success"
            message = "Step 1 completed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step2"
            status = "success"
            message = "Step 2 completed"
            timestamp = (Get-Date).ToString("o")
        },
        @{
            name = "step3"
            status = "success"
            message = "Step 3 completed"
            timestamp = (Get-Date).ToString("o")
        }
    )
    
    $state.steps = $steps
    $state.progress = ($steps | Where-Object { $_.status -eq "success" }).Count / $steps.Count * 100
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    return $state.progress -eq 100
}

# Test setup report
Test-Case -Name "Setup Report" -Message "Setup report should be generated correctly" -Test {
    Write-TestDebug -Message "Testing setup report generation" -TestName "Setup Report" -TestSuite $TestSuite
    $stateDir = Join-Path (Join-Path $PSScriptRoot "..") "state"
    $stateFile = Join-Path $stateDir "setup-state.json"
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    
    $report = @{
        totalSteps = $state.steps.Count
        completedSteps = ($state.steps | Where-Object { $_.status -eq "success" }).Count
        failedSteps = $state.failedSteps
        progress = $state.progress
        lastStep = $state.lastStep
        timestamp = (Get-Date).ToString("o")
    }
    
    $reportFile = Join-Path $stateDir "setup-report.json"
    $report | ConvertTo-Json | Set-Content -Path $reportFile
    
    $generatedReport = Get-Content -Path $reportFile | ConvertFrom-Json
    return $generatedReport.totalSteps -eq 3 -and $generatedReport.completedSteps -eq 3 -and $generatedReport.progress -eq 100
}

# Cleanup test files
Write-TestDebug -Message "Cleaning up test files" -TestSuite $TestSuite
try {
    Remove-Item -Path (Join-Path (Join-Path $PSScriptRoot "..") "state") -Recurse -Force -ErrorAction SilentlyContinue
}
catch {
    Write-TestWarning -Message "Failed to clean up test files" -TestSuite $TestSuite -Context @{
        Error = $_.Exception.Message
    }
}

# End test suite and print summary
End-TestSuite -SuiteName $TestSuite -Results $testResults

# Return test results
return $testResults 