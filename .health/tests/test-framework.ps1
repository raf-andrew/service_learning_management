# Health Check Test Framework
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath ".." "utils" "environment.ps1")
. (Join-Path $scriptPath ".." "utils" "github.ps1")
. (Join-Path $scriptPath ".." "healers" "self-healer.ps1")

# Test results
$testResults = @{
    Total = 0
    Passed = 0
    Failed = 0
    Skipped = 0
    StartTime = Get-Date
    EndTime = $null
    Tests = @()
}

# Run a test
function Invoke-Test {
    param (
        [string]$TestName,
        [scriptblock]$TestScript,
        [string]$Category,
        [switch]$AutoHeal
    )
    
    Write-Log "Running test: $TestName" -Level "INFO" -Category "Test"
    $testResults.Total++
    
    try {
        $result = & $TestScript
        $testStatus = @{
            Name = $TestName
            Category = $Category
            Status = $result.Status
            Message = $result.Message
            Details = $result.Details
            Timestamp = Get-Date
        }
        
        if ($result.Status -eq "Passed") {
            $testResults.Passed++
            Write-Log "Test passed: $TestName" -Level "SUCCESS" -Category "Test"
        }
        elseif ($result.Status -eq "Skipped") {
            $testResults.Skipped++
            Write-Log "Test skipped: $TestName - $($result.Message)" -Level "WARNING" -Category "Test"
        }
        else {
            $testResults.Failed++
            Write-Log "Test failed: $TestName - $($result.Message)" -Level "ERROR" -Category "Test"
            
            # Attempt auto-healing if enabled
            if ($AutoHeal) {
                $issue = @{
                    Check = $TestName
                    Category = $Category
                    Message = $result.Message
                    Details = $result.Details
                }
                
                $healed = Start-Healing -Issue $issue
                if ($healed) {
                    Write-Log "Auto-healed test: $TestName" -Level "SUCCESS" -Category "Test"
                    $testStatus.Status = "Healed"
                }
            }
        }
        
        $testResults.Tests += $testStatus
    }
    catch {
        $testResults.Failed++
        Write-Log "Error in test $TestName : $_" -Level "ERROR" -Category "Test"
        
        $testResults.Tests += @{
            Name = $TestName
            Category = $Category
            Status = "Error"
            Message = $_.Exception.Message
            Details = $_.Exception
            Timestamp = Get-Date
        }
    }
}

# Run all tests
function Start-Tests {
    param (
        [string[]]$Categories,
        [switch]$AutoHeal
    )
    
    Write-Log "Starting test suite..." -Level "INFO" -Category "Test"
    $testResults.StartTime = Get-Date
    
    # System Tests
    if (-not $Categories -or $Categories -contains "System") {
        # PowerShell Version
        Invoke-Test -TestName "PowerShell Version" -Category "System" -AutoHeal:$AutoHeal -TestScript {
            $version = $PSVersionTable.PSVersion
            $requiredVersion = [Version]"7.0.0"
            
            if ($version -ge $requiredVersion) {
                @{
                    Status = "Passed"
                    Message = "PowerShell version $version is sufficient"
                    Details = @{ Version = $version }
                }
            } else {
                @{
                    Status = "Failed"
                    Message = "PowerShell version $version is below required version $requiredVersion"
                    Details = @{ 
                        Current = $version
                        Required = $requiredVersion
                    }
                }
            }
        }
        
        # Required Tools
        Invoke-Test -TestName "Required Tools" -Category "System" -AutoHeal:$AutoHeal -TestScript {
            $tools = @(
                @{ Name = "php"; Command = "php -v" },
                @{ Name = "composer"; Command = "composer -V" },
                @{ Name = "node"; Command = "node -v" },
                @{ Name = "npm"; Command = "npm -v" },
                @{ Name = "jq"; Command = "jq --version" },
                @{ Name = "gh"; Command = "gh --version" }
            )
            
            $missingTools = @()
            foreach ($tool in $tools) {
                try {
                    $output = Invoke-Expression $tool.Command 2>&1
                } catch {
                    $missingTools += $tool.Name
                }
            }
            
            if ($missingTools.Count -eq 0) {
                @{
                    Status = "Passed"
                    Message = "All required tools are available"
                    Details = @{ Tools = $tools.Name }
                }
            } else {
                @{
                    Status = "Failed"
                    Message = "Missing required tools: $($missingTools -join ', ')"
                    Details = @{ MissingTools = $missingTools }
                }
            }
        }
    }
    
    # GitHub Tests
    if (-not $Categories -or $Categories -contains "GitHub") {
        # GitHub Authentication
        Invoke-Test -TestName "GitHub Authentication" -Category "GitHub" -AutoHeal:$AutoHeal -TestScript {
            if (Test-GitHubAuth) {
                @{
                    Status = "Passed"
                    Message = "GitHub authentication is valid"
                    Details = @{ Scopes = $githubConfig.RequiredScopes }
                }
            } else {
                @{
                    Status = "Failed"
                    Message = "GitHub authentication failed"
                    Details = @{ RequiredScopes = $githubConfig.RequiredScopes }
                }
            }
        }
        
        # GitHub Actions
        Invoke-Test -TestName "GitHub Actions" -Category "GitHub" -AutoHeal:$AutoHeal -TestScript {
            $status = Get-GitHubActionsStatus
            if ($status) {
                @{
                    Status = "Passed"
                    Message = "GitHub Actions status retrieved"
                    Details = $status
                }
            } else {
                @{
                    Status = "Failed"
                    Message = "Failed to retrieve GitHub Actions status"
                    Details = @{}
                }
            }
        }
    }
    
    # Service Tests
    if (-not $Categories -or $Categories -contains "Services") {
        # MySQL
        Invoke-Test -TestName "MySQL Service" -Category "Services" -AutoHeal:$AutoHeal -TestScript {
            $config = Get-ServiceConfig -Service "MySQL"
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect($config.Host, $config.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                $tcpClient.Close()
                
                if ($success) {
                    @{
                        Status = "Passed"
                        Message = "MySQL service is running"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                } else {
                    @{
                        Status = "Failed"
                        Message = "MySQL service is not responding"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                }
            } catch {
                @{
                    Status = "Failed"
                    Message = "MySQL service error: $_"
                    Details = @{ Host = $config.Host; Port = $config.Port; Error = $_.Exception.Message }
                }
            }
        }
        
        # Redis
        Invoke-Test -TestName "Redis Service" -Category "Services" -AutoHeal:$AutoHeal -TestScript {
            $config = Get-ServiceConfig -Service "Redis"
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect($config.Host, $config.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                $tcpClient.Close()
                
                if ($success) {
                    @{
                        Status = "Passed"
                        Message = "Redis service is running"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                } else {
                    @{
                        Status = "Failed"
                        Message = "Redis service is not responding"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                }
            } catch {
                @{
                    Status = "Failed"
                    Message = "Redis service error: $_"
                    Details = @{ Host = $config.Host; Port = $config.Port; Error = $_.Exception.Message }
                }
            }
        }
        
        # Apache
        Invoke-Test -TestName "Apache Service" -Category "Services" -AutoHeal:$AutoHeal -TestScript {
            $config = Get-ServiceConfig -Service "Apache"
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect($config.Host, $config.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                $tcpClient.Close()
                
                if ($success) {
                    @{
                        Status = "Passed"
                        Message = "Apache service is running"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                } else {
                    @{
                        Status = "Failed"
                        Message = "Apache service is not responding"
                        Details = @{ Host = $config.Host; Port = $config.Port }
                    }
                }
            } catch {
                @{
                    Status = "Failed"
                    Message = "Apache service error: $_"
                    Details = @{ Host = $config.Host; Port = $config.Port; Error = $_.Exception.Message }
                }
            }
        }
    }
    
    # Update test results
    $testResults.EndTime = Get-Date
    $duration = $testResults.EndTime - $testResults.StartTime
    
    # Save results
    $resultsPath = Join-Path (Join-Path $scriptPath ".." "logs") "test-results-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $testResults | ConvertTo-Json -Depth 10 | Set-Content -Path $resultsPath
    
    # Display summary
    Write-Host "`nTest Results Summary:"
    Write-Host "==================="
    Write-Host "Total Tests: $($testResults.Total)"
    Write-Host "Passed: $($testResults.Passed)"
    Write-Host "Failed: $($testResults.Failed)"
    Write-Host "Skipped: $($testResults.Skipped)"
    Write-Host "Duration: $($duration.TotalSeconds) seconds"
    
    if ($testResults.Failed -gt 0) {
        Write-Host "`nFailed Tests:"
        foreach ($test in $testResults.Tests | Where-Object { $_.Status -eq "Failed" }) {
            Write-Host "- $($test.Name) ($($test.Category)): $($test.Message)" -ForegroundColor "Red"
        }
    }
    
    return $testResults
}

# Export functions
Export-ModuleMember -Function Start-Tests 