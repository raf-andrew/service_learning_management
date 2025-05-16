# MCP Test Runner
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# Run All Tests
function Start-MCPTests {
    param (
        [string]$Environment = "local",
        [switch]$Force,
        [switch]$Detailed
    )
    
    Write-Log "Starting MCP tests..." -Level "INFO" -Category "MCP"
    
    try {
        $testResults = @{
            Status = $true
            Tests = @{}
            StartTime = Get-Date
            EndTime = $null
        }
        
        # Test MCP server
        Write-Log "Running MCP server tests..." -Level "INFO" -Category "MCP"
        $serverTests = Test-MCPServer -Environment $Environment
        $testResults.Tests["Server"] = $serverTests
        if (-not $serverTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "MCP server tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Test services
        Write-Log "Running service tests..." -Level "INFO" -Category "MCP"
        $serviceTests = & (Join-Path -Path $scriptPath -ChildPath "test-services.ps1")
        $testResults.Tests["Services"] = $serviceTests
        if (-not $serviceTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "Service tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Test data sources
        Write-Log "Running data source tests..." -Level "INFO" -Category "MCP"
        $dataTests = & (Join-Path -Path $scriptPath -ChildPath "test-data.ps1")
        $testResults.Tests["Data"] = $dataTests
        if (-not $dataTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "Data source tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Test environment
        Write-Log "Running environment tests..." -Level "INFO" -Category "MCP"
        $envTests = & (Join-Path -Path $scriptPath -ChildPath "test-environment.ps1")
        $testResults.Tests["Environment"] = $envTests
        if (-not $envTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "Environment tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Test developer utilities
        Write-Log "Running developer utility tests..." -Level "INFO" -Category "MCP"
        $utilTests = & (Join-Path -Path $scriptPath -ChildPath "test-dev-utils.ps1")
        $testResults.Tests["DeveloperUtilities"] = $utilTests
        if (-not $utilTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "Developer utility tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Test features
        Write-Log "Running feature tests..." -Level "INFO" -Category "MCP"
        $featureTests = & (Join-Path -Path $scriptPath -ChildPath "feature-tests.ps1")
        $testResults.Tests["Features"] = $featureTests
        if (-not $featureTests.Status) {
            $testResults.Status = $false
            if (-not $Force) {
                Write-Log "Feature tests failed" -Level "ERROR" -Category "MCP"
                return $testResults
            }
        }
        
        # Set end time
        $testResults.EndTime = Get-Date
        
        # Calculate duration
        $testResults.Duration = ($testResults.EndTime - $testResults.StartTime).TotalSeconds
        
        # Export test results
        $reportPath = Join-Path -Path $mcpPath -ChildPath "logs" "test-report.json"
        $reportJson = ConvertTo-Json -InputObject $testResults -Depth 10
        Set-Content -Path $reportPath -Value $reportJson
        
        if ($testResults.Status) {
            Write-Log "All MCP tests passed" -Level "SUCCESS" -Category "MCP"
        }
        else {
            Write-Log "Some MCP tests failed" -Level "ERROR" -Category "MCP"
        }
        
        return $testResults
    }
    catch {
        Write-Log "Error running MCP tests: $_" -Level "ERROR" -Category "MCP"
        return @{
            Status = $false
            Tests = @{
                "Error" = @{
                    Status = $false
                    Message = $_.Exception.Message
                }
            }
            StartTime = Get-Date
            EndTime = Get-Date
            Duration = 0
        }
    }
}

# Run tests if script is executed directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    $testResults = Start-MCPTests -Environment "local" -Detailed:$true
    if (-not $testResults.Status) {
        Write-Error "MCP tests failed"
        exit 1
    }
    exit 0
}

# Export functions
Export-ModuleMember -Function @(
    "Start-MCPTests"
) 