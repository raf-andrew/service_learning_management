# Codespaces Test Script
# Tests Codespaces configuration and setup

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

# Initialize test results
$testResults = @{
    StartTime = Get-Date
    EndTime = $null
    Tests = @()
    Configuration = @{
        DevContainer = $null
        Services = @()
        BuildScripts = @()
        Dependencies = @()
    }
}

# Test devcontainer.json
function Test-DevContainerConfig {
    $devContainerPath = Join-Path (Join-Path $scriptPath "../..") "devcontainer.json"
    
    try {
        if (Test-Path $devContainerPath) {
            $config = Get-Content $devContainerPath -Raw | ConvertFrom-Json
            $testResults.Configuration.DevContainer = @{
                Exists = $true
                Config = $config
            }
            Add-TestResult -Name "DevContainer Configuration" -Status "Passed" -Details "Configuration file found and valid"
        } else {
            $testResults.Configuration.DevContainer = @{
                Exists = $false
            }
            Add-TestResult -Name "DevContainer Configuration" -Status "Failed" -Details "Configuration file not found"
        }
    } catch {
        $testResults.Configuration.DevContainer = @{
            Exists = $false
            Error = $_.Exception.Message
        }
        Add-TestResult -Name "DevContainer Configuration" -Status "Failed" -Details $_.Exception.Message
    }
}

# Test service definitions
function Test-ServiceDefinitions {
    $servicesPath = Join-Path (Join-Path $scriptPath "../..") "services"
    
    try {
        if (Test-Path $servicesPath) {
            $services = Get-ChildItem $servicesPath -File
            foreach ($service in $services) {
                $serviceConfig = Get-Content $service.FullName -Raw | ConvertFrom-Json
                $testResults.Configuration.Services += @{
                    Name = $service.BaseName
                    Config = $serviceConfig
                }
                Add-TestResult -Name "Service Definition: $($service.BaseName)" -Status "Passed" -Details "Service definition found and valid"
            }
        } else {
            Add-TestResult -Name "Service Definitions" -Status "Failed" -Details "Services directory not found"
        }
    } catch {
        Add-TestResult -Name "Service Definitions" -Status "Failed" -Details $_.Exception.Message
    }
}

# Test build scripts
function Test-BuildScripts {
    $buildPath = Join-Path (Join-Path $scriptPath "../..") "build"
    
    try {
        if (Test-Path $buildPath) {
            $scripts = Get-ChildItem $buildPath -File
            foreach ($script in $scripts) {
                $testResults.Configuration.BuildScripts += @{
                    Name = $script.BaseName
                    Path = $script.FullName
                }
                Add-TestResult -Name "Build Script: $($script.BaseName)" -Status "Passed" -Details "Build script found"
            }
        } else {
            Add-TestResult -Name "Build Scripts" -Status "Failed" -Details "Build directory not found"
        }
    } catch {
        Add-TestResult -Name "Build Scripts" -Status "Failed" -Details $_.Exception.Message
    }
}

# Test dependencies
function Test-Dependencies {
    $composerPath = Join-Path (Join-Path $scriptPath "../..") "composer.json"
    $packagePath = Join-Path (Join-Path $scriptPath "../..") "package.json"
    
    try {
        if (Test-Path $composerPath) {
            $composerConfig = Get-Content $composerPath -Raw | ConvertFrom-Json
            $testResults.Configuration.Dependencies += @{
                Type = "PHP"
                Config = $composerConfig
            }
            Add-TestResult -Name "PHP Dependencies" -Status "Passed" -Details "Composer configuration found and valid"
        } else {
            Add-TestResult -Name "PHP Dependencies" -Status "Failed" -Details "Composer configuration not found"
        }
        
        if (Test-Path $packagePath) {
            $packageConfig = Get-Content $packagePath -Raw | ConvertFrom-Json
            $testResults.Configuration.Dependencies += @{
                Type = "Node"
                Config = $packageConfig
            }
            Add-TestResult -Name "Node Dependencies" -Status "Passed" -Details "Package configuration found and valid"
        } else {
            Add-TestResult -Name "Node Dependencies" -Status "Failed" -Details "Package configuration not found"
        }
    } catch {
        Add-TestResult -Name "Dependencies" -Status "Failed" -Details $_.Exception.Message
    }
}

# Add test result
function Add-TestResult {
    param (
        [string]$Name,
        [string]$Status,
        [string]$Details
    )

    $testResults.Tests += @{
        Name = $Name
        Status = $Status
        Details = $Details
        Timestamp = Get-Date
    }
}

# Generate report
function Write-CodespacesReport {
    $testResults.EndTime = Get-Date
    $duration = $testResults.EndTime - $testResults.StartTime

    $report = @"
# Codespaces Configuration Test Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Configuration Overview
- Duration: $($duration.TotalSeconds) seconds

## Test Results
"@

    foreach ($test in $testResults.Tests) {
        $report += @"

### $($test.Name)
- Status: $($test.Status)
- Details: $($test.Details)
- Time: $($test.Timestamp)
"@
    }

    # Save report
    $reportPath = Join-Path (Join-Path $scriptPath "..") ".test/results/codespaces-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Codespaces report generated: $reportPath" -Level "INFO"

    # Save JSON data
    $jsonPath = Join-Path (Join-Path $scriptPath "..") ".test/tracking/codespaces-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $testResults | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath
    Write-TestLog "Codespaces data saved: $jsonPath" -Level "INFO"
}

# Main execution
try {
    Write-TestLog "Starting Codespaces configuration tests..." -Level "INFO"

    Test-DevContainerConfig
    Test-ServiceDefinitions
    Test-BuildScripts
    Test-Dependencies

    Write-CodespacesReport

    # Set exit code based on test results
    $failedTests = $testResults.Tests | Where-Object { $_.Status -eq "Failed" }
    if ($failedTests) {
        Write-TestWarning "Codespaces configuration tests completed with failures"
        exit 1
    } else {
        Write-TestLog "All Codespaces configuration tests passed successfully" -Level "SUCCESS"
        exit 0
    }
} catch {
    Write-TestError "Error during Codespaces configuration tests: $_"
    exit 1
} 