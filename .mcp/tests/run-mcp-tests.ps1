# Test Runner Script
$ErrorActionPreference = "Stop"

Write-Host "[DEBUG] Test runner starting..."

# Get script path and set up paths
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path (Join-Path -Path $scriptPath -ChildPath "..") -ChildPath ".." | Join-Path -ChildPath "mcp"

Write-Host "[DEBUG] Script path: $scriptPath"
Write-Host "[DEBUG] MCP path: $mcpPath"

# Import test framework
. (Join-Path -Path $scriptPath -ChildPath "test-mcp-comprehensive.ps1")

Write-Host "[DEBUG] Test framework imported successfully"

# Configure test parameters for remote services
$testParams = @{
    Environment = "codespaces"
    OutputPath = Join-Path -Path $mcpPath -ChildPath "utils\reports"
    LogLevel = "DEBUG"
    RetryCount = 3
    RetryDelay = 5
    Timeout = 30
    RemoteServices = @{
        Enabled = $true
        HostPrefix = "codespaces-"
        DefaultPorts = @{
            MySQL = 3306
            Redis = 6379
            Nginx = 80
        }
    }
}

Write-Host "[DEBUG] Test parameters configured"
Write-Host "[DEBUG] Report file will be: $(Join-Path -Path $testParams.OutputPath -ChildPath "mcp_test_report_$(Get-Date -Format 'yyyyMMdd_HHmmss').json")"

# Create reports directory if it doesn't exist
if (-not (Test-Path $testParams.OutputPath)) {
    New-Item -ItemType Directory -Path $testParams.OutputPath -Force | Out-Null
    Write-Host "[DEBUG] Created reports directory: $($testParams.OutputPath)"
}

try {
    Write-Host "[DEBUG] Starting comprehensive test suite..."
    Write-Host "[DEBUG] Environment: $($testParams.Environment)"
    Write-Host "[DEBUG] Output Path: $($testParams.OutputPath)"
    
    # Run the tests
    $results = Start-MCPComprehensiveTest @testParams
    
    # Display results
    Write-Host "`nTest Results Summary:"
    Write-Host "-------------------"
    Write-Host "Total Tests: $($results.Tests.Total)"
    Write-Host "Passed: $($results.Tests.Passed)"
    Write-Host "Failed: $($results.Tests.Failed)"
    Write-Host "Skipped: $($results.Tests.Skipped)"
    Write-Host "Warnings: $($results.Tests.Warnings)"
    
    if ($results.Issues.Count -gt 0) {
        Write-Host "`nIssues Found:"
        Write-Host "-------------"
        foreach ($issue in $results.Issues) {
            Write-Host "- $issue"
        }
    }
    
    # Export results
    $reportFile = Join-Path -Path $testParams.OutputPath -ChildPath "mcp_test_report_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"
    $results | ConvertTo-Json -Depth 10 | Out-File -FilePath $reportFile -Encoding UTF8
    Write-Host "`nDetailed report exported to: $reportFile"
    
    # Set exit code based on test results
    if ($results.Tests.Failed -gt 0) {
        exit 1
    }
    exit 0
}
catch {
    Write-Host "[ERROR] Test execution failed: $($_.Exception.Message)"
    Write-Host "[ERROR] Stack Trace: $($_.ScriptStackTrace)"
    exit 1
} 