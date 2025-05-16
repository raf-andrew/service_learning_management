# MCP Feature Test Suite
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "mcp.ps1")
. (Join-Path $scriptPath ".." "utils" "services.ps1")
. (Join-Path $scriptPath ".." "utils" "data.ps1")
. (Join-Path $scriptPath ".." "utils" "logs.ps1")
. (Join-Path $scriptPath ".." "utils" "config.ps1")

# Test Results
$testResults = @{
    Passed = 0
    Failed = 0
    Skipped = 0
    Details = @()
}

# Helper function to record test results
function Record-TestResult {
    param (
        [string]$TestName,
        [bool]$Passed,
        [string]$Message,
        [string]$Category
    )
    
    $result = @{
        Name = $TestName
        Passed = $Passed
        Message = $Message
        Category = $Category
        Timestamp = Get-Date
    }
    
    $testResults.Details += $result
    if ($Passed) {
        $testResults.Passed++
        Write-Host "PASSED: $TestName" -ForegroundColor Green
    } else {
        $testResults.Failed++
        Write-Host "FAILED: $TestName - $Message" -ForegroundColor Red
    }
}

# Test Service Health Checks
function Test-ServiceHealthChecks {
    param (
        [string]$Environment
    )
    
    Write-Host "`nTesting Service Health Checks for $Environment environment..." -ForegroundColor Cyan
    
    $services = @('MySQL', 'Redis', 'Apache')
    foreach ($service in $services) {
        $testName = "Health Check - $service in $Environment"
        try {
            $health = Test-ServiceHealth -Service $service -Environment $Environment
            Record-TestResult -TestName $testName -Passed $health.IsHealthy -Message $health.Message -Category "Service Health"
        } catch {
            Record-TestResult -TestName $testName -Passed $false -Message $_.Exception.Message -Category "Service Health"
        }
    }
}

# Test Data Source Operations
function Test-DataSourceOperations {
    param (
        [string]$Environment
    )
    
    Write-Host "`nTesting Data Source Operations for $Environment environment..." -ForegroundColor Cyan
    
    $config = Get-EnvironmentConfig -Environment $Environment
    foreach ($source in $config.Data.Sources.Keys) {
        $testName = "Data Source - $source in $Environment"
        try {
            $result = Test-DataSource -Source $source -Environment $Environment
            Record-TestResult -TestName $testName -Passed $result -Message "Data source test completed" -Category "Data Source"
        } catch {
            Record-TestResult -TestName $testName -Passed $false -Message $_.Exception.Message -Category "Data Source"
        }
    }
}

# Test Logging Operations
function Test-LoggingOperations {
    param (
        [string]$Environment
    )
    
    Write-Host "`nTesting Logging Operations for $Environment environment..." -ForegroundColor Cyan
    
    $logCategories = @('General', 'Services', 'Data', 'Config', 'Audit')
    foreach ($category in $logCategories) {
        $testName = "Logging - $category in $Environment"
        try {
            $logPath = Join-Path $scriptPath ".." "logs" $category.ToLower() "$category.log"
            if (-not (Test-Path (Split-Path $logPath -Parent))) {
                New-Item -ItemType Directory -Path (Split-Path $logPath -Parent) -Force | Out-Null
            }
            
            $testMessage = "Test log message for $category at $(Get-Date)"
            Write-Log -Message $testMessage -Level "INFO" -Category $category
            
            if (Test-Path $logPath) {
                $logContent = Get-Content $logPath -Tail 1
                $passed = $logContent -match [regex]::Escape($testMessage)
                Record-TestResult -TestName $testName -Passed $passed -Message "Log file verification" -Category "Logging"
            } else {
                Record-TestResult -TestName $testName -Passed $false -Message "Log file not created" -Category "Logging"
            }
        } catch {
            Record-TestResult -TestName $testName -Passed $false -Message $_.Exception.Message -Category "Logging"
        }
    }
}

# Test Configuration Management
function Test-ConfigurationManagement {
    param (
        [string]$Environment
    )
    
    Write-Host "`nTesting Configuration Management for $Environment environment..." -ForegroundColor Cyan
    
    $testName = "Configuration - Load and Validate in $Environment"
    try {
        $config = Get-EnvironmentConfig -Environment $Environment
        $passed = $null -ne $config -and 
                 $config.Services -and 
                 $config.Data -and 
                 $config.Logs
        
        Record-TestResult -TestName $testName -Passed $passed -Message "Configuration validation" -Category "Configuration"
    } catch {
        Record-TestResult -TestName $testName -Passed $false -Message $_.Exception.Message -Category "Configuration"
    }
}

# Test Service Healing
function Test-ServiceHealing {
    param (
        [string]$Environment
    )
    
    Write-Host "`nTesting Service Healing for $Environment environment..." -ForegroundColor Cyan
    
    $services = @('MySQL', 'Redis', 'Apache')
    foreach ($service in $services) {
        $testName = "Service Healing - $service in $Environment"
        try {
            $heal = Start-ServiceHealing -Service $service -Environment $Environment
            Record-TestResult -TestName $testName -Passed $heal.Success -Message $heal.Message -Category "Service Healing"
        } catch {
            Record-TestResult -TestName $testName -Passed $false -Message $_.Exception.Message -Category "Service Healing"
        }
    }
}

# Run all tests for both environments
$environments = @('Local', 'Remote')
foreach ($env in $environments) {
    Write-Host "`nRunning feature tests for $env environment..." -ForegroundColor Yellow
    
    Test-ServiceHealthChecks -Environment $env
    Test-DataSourceOperations -Environment $env
    Test-LoggingOperations -Environment $env
    Test-ConfigurationManagement -Environment $env
    Test-ServiceHealing -Environment $env
}

# Generate test report
$report = @"
MCP Feature Test Report
======================
Generated: $(Get-Date)

Summary:
--------
Total Tests: $($testResults.Passed + $testResults.Failed + $testResults.Skipped)
Passed: $($testResults.Passed)
Failed: $($testResults.Failed)
Skipped: $($testResults.Skipped)

Detailed Results:
----------------
"@

$testResults.Details | ForEach-Object {
    $status = if ($_.Passed) { "PASSED" } else { "FAILED" }
    $report += "`n$($_.Timestamp) - $($_.Category) - $($_.Name) - $status"
    if (-not $_.Passed) {
        $report += "`n  Error: $($_.Message)"
    }
}

# Save report
$reportPath = Join-Path $scriptPath "test-report.txt"
$report | Out-File -FilePath $reportPath -Encoding UTF8

Write-Host "`nTest Report saved to: $reportPath" -ForegroundColor Cyan
Write-Host "`nTest Summary:" -ForegroundColor Yellow
Write-Host "Total Tests: $($testResults.Passed + $testResults.Failed + $testResults.Skipped)"
Write-Host "Passed: $($testResults.Passed)" -ForegroundColor Green
Write-Host "Failed: $($testResults.Failed)" -ForegroundColor Red
Write-Host "Skipped: $($testResults.Skipped)" -ForegroundColor Yellow 