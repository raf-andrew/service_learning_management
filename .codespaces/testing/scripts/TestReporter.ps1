# Test Reporter Script
# This script generates comprehensive test reports in multiple formats

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

# Initialize directories
$testResultsDir = Join-Path (Join-Path $scriptPath "..") ".test/results"
$testHistoryDir = Join-Path $testResultsDir "history"
$testReportsDir = Join-Path $testResultsDir "reports"

# Create directories if they don't exist
foreach ($dir in @($testResultsDir, $testHistoryDir, $testReportsDir)) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Generate HTML report
function Write-HtmlReport {
    param (
        [hashtable]$TestResults,
        [hashtable]$ChecklistData
    )
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $reportPath = Join-Path $testReportsDir "test-report-$timestamp.html"
    
    $html = @"
<!DOCTYPE html>
<html>
<head>
    <title>Test Report - $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .summary { background-color: #f5f5f5; padding: 15px; border-radius: 5px; }
        .suite { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test { margin: 10px 0; padding: 10px; background-color: #f9f9f9; }
        .passed { color: green; }
        .failed { color: red; }
        .skipped { color: orange; }
        .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .metric { background-color: #fff; padding: 10px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .checklist { margin-top: 20px; }
        .checklist-item { padding: 10px; margin: 5px 0; border-left: 4px solid #ddd; }
        .checklist-item.completed { border-left-color: green; }
        .checklist-item.failed { border-left-color: red; }
        .checklist-item.pending { border-left-color: orange; }
    </style>
</head>
<body>
    <h1>Test Report</h1>
    <p>Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")</p>
    
    <div class="summary">
        <h2>Summary</h2>
        <div class="metrics">
            <div class="metric">
                <h3>Test Results</h3>
                <p>Total Tests: $($TestResults.TotalTests)</p>
                <p>Passed: <span class="passed">$($TestResults.PassedTests)</span></p>
                <p>Failed: <span class="failed">$($TestResults.FailedTests)</span></p>
                <p>Duration: $($TestResults.Duration.TotalSeconds) seconds</p>
            </div>
            <div class="metric">
                <h3>Checklist Status</h3>
                <p>Total Items: $($ChecklistData.metadata.total_items)</p>
                <p>Completed: <span class="passed">$($ChecklistData.metadata.completed_items)</span></p>
                <p>Pending: <span class="skipped">$($ChecklistData.metadata.pending_items)</span></p>
                <p>Failed: <span class="failed">$($ChecklistData.metadata.failed_items)</span></p>
            </div>
        </div>
    </div>
    
    <h2>Test Suites</h2>
"@
    
    foreach ($suite in $TestResults.TestSuites) {
        $suiteDuration = $suite.EndTime - $suite.StartTime
        $html += @"
    <div class="suite">
        <h3>$($suite.Name) - $($suite.TestName)</h3>
        <p>Status: <span class="$($suite.Status.ToLower())">$($suite.Status)</span></p>
        <p>Duration: $($suiteDuration.TotalSeconds) seconds</p>
        <p>Start Time: $($suite.StartTime)</p>
        <p>End Time: $($suite.EndTime)</p>
        
        <div class="test">
            <h4>Test Output</h4>
            <pre>$($suite.Result)</pre>
"@
        
        if ($suite.Error) {
            $html += @"
            <h4>Error</h4>
            <pre class="failed">$($suite.Error)</pre>
"@
        }
        
        $html += @"
        </div>
    </div>
"@
    }
    
    $html += @"
    <div class="checklist">
        <h2>Checklist Status</h2>
"@
    
    foreach ($checklist in $ChecklistData.checklists.PSObject.Properties) {
        $html += @"
        <h3>$($checklist.Value.name)</h3>
"@
        
        foreach ($item in $checklist.Value.items) {
            $html += @"
        <div class="checklist-item $($item.status)">
            <h4>$($item.description)</h4>
            <p>Status: <span class="$($item.status)">$($item.status)</span></p>
            <p>Last Run: $($item.last_run)</p>
"@
            
            if ($item.completion_proof) {
                $html += @"
            <div class="completion-proof">
                <p>Result: $($item.completion_proof.result)</p>
"@
                
                if ($item.completion_proof.error) {
                    $html += @"
                <p class="failed">Error: $($item.completion_proof.error)</p>
"@
                }
                
                $html += @"
                <p>Test Report: $($item.completion_proof.test_report)</p>
            </div>
"@
            }
            
            $html += @"
        </div>
"@
        }
    }
    
    $html += @"
    </div>
</body>
</html>
"@
    
    Set-Content -Path $reportPath -Value $html
    Write-TestLog "HTML report generated: $reportPath" -Level "INFO"
    return $reportPath
}

# Generate JSON report
function Write-JsonReport {
    param (
        [hashtable]$TestResults,
        [hashtable]$ChecklistData
    )
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $reportPath = Join-Path $testReportsDir "test-report-$timestamp.json"
    
    $report = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        test_results = $TestResults
        checklist_data = $ChecklistData
        environment = @{
            os = $PSVersionTable.OS
            powershell_version = $PSVersionTable.PSVersion
            php_version = (php -v)[0]
            laravel_version = (php artisan --version)
        }
    }
    
    $report | ConvertTo-Json -Depth 10 | Set-Content $reportPath
    Write-TestLog "JSON report generated: $reportPath" -Level "INFO"
    return $reportPath
}

# Update test history
function Update-TestHistory {
    param (
        [hashtable]$TestResults,
        [hashtable]$ChecklistData
    )
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $historyFile = Join-Path $testHistoryDir "test-history.json"
    
    $history = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        test_results = $TestResults
        checklist_data = $ChecklistData
    }
    
    if (Test-Path $historyFile) {
        $existingHistory = Get-Content $historyFile -Raw | ConvertFrom-Json
        $historyArray = @($existingHistory) + @($history)
    } else {
        $historyArray = @($history)
    }
    
    $historyArray | ConvertTo-Json -Depth 10 | Set-Content $historyFile
    Write-TestLog "Test history updated: $historyFile" -Level "INFO"
}

# Generate test report
function Write-TestReport {
    param (
        [hashtable]$TestResults,
        [hashtable]$ChecklistData
    )
    
    # Generate reports in multiple formats
    $htmlReport = Write-HtmlReport -TestResults $TestResults -ChecklistData $ChecklistData
    $jsonReport = Write-JsonReport -TestResults $TestResults -ChecklistData $ChecklistData
    
    # Update test history
    Update-TestHistory -TestResults $TestResults -ChecklistData $ChecklistData
    
    # Generate markdown report
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $reportPath = Join-Path $testResultsDir "feature-test-report-$timestamp.md"
    
    $report = @"
# Feature Test Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Summary
- Total Tests: $($TestResults.TotalTests)
- Passed: $($TestResults.PassedTests)
- Failed: $($TestResults.FailedTests)
- Duration: $($TestResults.Duration.TotalSeconds) seconds

## Checklist Status
- Total Items: $($ChecklistData.metadata.total_items)
- Completed: $($ChecklistData.metadata.completed_items)
- Pending: $($ChecklistData.metadata.pending_items)
- Failed: $($ChecklistData.metadata.failed_items)

## Test Suites
"@
    
    foreach ($suite in $TestResults.TestSuites) {
        $suiteDuration = $suite.EndTime - $suite.StartTime
        $report += @"

### $($suite.Name) - $($suite.TestName)
- Status: $($suite.Status)
- Duration: $($suiteDuration.TotalSeconds) seconds
- Start Time: $($suite.StartTime)
- End Time: $($suite.EndTime)

#### Test Output
```
$($suite.Result)
```

"@
        if ($suite.Error) {
            $report += @"
#### Error
```
$($suite.Error)
```
"@
        }
    }
    
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Markdown report generated: $reportPath" -Level "INFO"
    
    return @{
        html_report = $htmlReport
        json_report = $jsonReport
        markdown_report = $reportPath
    }
} 