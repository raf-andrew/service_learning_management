# Build script for the project

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "../testing/scripts/TestLogger.ps1")

# Initialize build results
$buildResults = @{
    StartTime = Get-Date
    EndTime = $null
    Steps = @()
    Status = "Running"
}

# Function to add build step
function Add-BuildStep {
    param (
        [string]$Name,
        [string]$Status,
        [string]$Details
    )
    
    $buildResults.Steps += @{
        Name = $Name
        Status = $Status
        Details = $Details
        Time = Get-Date
    }
}

# Function to write build report
function Write-BuildReport {
    $buildResults.EndTime = Get-Date
    $duration = $buildResults.EndTime - $buildResults.StartTime

    $report = @"
# Build Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Build Information
- Duration: $($duration.TotalSeconds) seconds
- Status: $($buildResults.Status)

## Build Steps
"@

    foreach ($step in $buildResults.Steps) {
        $report += @"

### $($step.Name)
- Status: $($step.Status)
- Details: $($step.Details)
- Time: $($step.Time)
"@
    }

    # Save report
    $reportPath = Join-Path $scriptPath "../testing/.test/results/build-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Build report generated: $reportPath" -Level "INFO"

    # Save JSON data
    $jsonPath = Join-Path $scriptPath "../testing/.test/tracking/build-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $buildResults | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath
    Write-TestLog "Build data saved: $jsonPath" -Level "INFO"
}

# Main execution
try {
    Write-TestLog "Starting build process..." -Level "INFO"

    # Step 1: Check prerequisites
    Add-BuildStep -Name "Check Prerequisites" -Status "Running" -Details "Checking system requirements"
    $prereqScript = Join-Path $scriptPath "../testing/scripts/check-prerequisites.ps1"
    if (Test-Path $prereqScript) {
        & $prereqScript
        if ($LASTEXITCODE -eq 0) {
            Add-BuildStep -Name "Check Prerequisites" -Status "Passed" -Details "All prerequisites met"
        } else {
            Add-BuildStep -Name "Check Prerequisites" -Status "Failed" -Details "Prerequisites check failed"
            throw "Prerequisites check failed"
        }
    } else {
        Add-BuildStep -Name "Check Prerequisites" -Status "Failed" -Details "Prerequisites script not found"
        throw "Prerequisites script not found"
    }

    # Step 2: Install dependencies
    Add-BuildStep -Name "Install Dependencies" -Status "Running" -Details "Installing project dependencies"
    if (Test-Path "composer.json") {
        composer install
        if ($LASTEXITCODE -eq 0) {
            Add-BuildStep -Name "Install Dependencies" -Status "Passed" -Details "Dependencies installed successfully"
        } else {
            Add-BuildStep -Name "Install Dependencies" -Status "Failed" -Details "Failed to install dependencies"
            throw "Failed to install dependencies"
        }
    } else {
        Add-BuildStep -Name "Install Dependencies" -Status "Failed" -Details "composer.json not found"
        throw "composer.json not found"
    }

    # Step 3: Build assets
    Add-BuildStep -Name "Build Assets" -Status "Running" -Details "Building frontend assets"
    if (Test-Path "package.json") {
        npm install
        if ($LASTEXITCODE -eq 0) {
            npm run build
            if ($LASTEXITCODE -eq 0) {
                Add-BuildStep -Name "Build Assets" -Status "Passed" -Details "Assets built successfully"
            } else {
                Add-BuildStep -Name "Build Assets" -Status "Failed" -Details "Failed to build assets"
                throw "Failed to build assets"
            }
        } else {
            Add-BuildStep -Name "Build Assets" -Status "Failed" -Details "Failed to install npm dependencies"
            throw "Failed to install npm dependencies"
        }
    } else {
        Add-BuildStep -Name "Build Assets" -Status "Skipped" -Details "package.json not found"
    }

    # Step 4: Run tests
    Add-BuildStep -Name "Run Tests" -Status "Running" -Details "Running test suite"
    $testScript = Join-Path $scriptPath "../testing/scripts/run-all-tests.ps1"
    if (Test-Path $testScript) {
        & $testScript
        if ($LASTEXITCODE -eq 0) {
            Add-BuildStep -Name "Run Tests" -Status "Passed" -Details "All tests passed"
        } else {
            Add-BuildStep -Name "Run Tests" -Status "Failed" -Details "Tests failed"
            throw "Tests failed"
        }
    } else {
        Add-BuildStep -Name "Run Tests" -Status "Failed" -Details "Test script not found"
        throw "Test script not found"
    }

    # Build completed successfully
    $buildResults.Status = "Success"
    Write-BuildReport
    Write-TestLog "Build completed successfully" -Level "SUCCESS"
    exit 0
} catch {
    $buildResults.Status = "Failed"
    Write-BuildReport
    Write-TestError "Build failed: $_"
    exit 1
} 