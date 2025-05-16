# Setup script for the project

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "../testing/scripts/TestLogger.ps1")

# Initialize setup results
$setupResults = @{
    StartTime = Get-Date
    EndTime = $null
    Steps = @()
    Status = "Running"
}

# Function to add setup step
function Add-SetupStep {
    param (
        [string]$Name,
        [string]$Status,
        [string]$Details
    )
    
    $setupResults.Steps += @{
        Name = $Name
        Status = $Status
        Details = $Details
        Time = Get-Date
    }
}

# Function to write setup report
function Write-SetupReport {
    $setupResults.EndTime = Get-Date
    $duration = $setupResults.EndTime - $setupResults.StartTime

    $report = @"
# Setup Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Setup Information
- Duration: $($duration.TotalSeconds) seconds
- Status: $($setupResults.Status)

## Setup Steps
"@

    foreach ($step in $setupResults.Steps) {
        $report += @"

### $($step.Name)
- Status: $($step.Status)
- Details: $($step.Details)
- Time: $($step.Time)
"@
    }

    # Save report
    $reportPath = Join-Path $scriptPath "../testing/.test/results/setup-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Setup report generated: $reportPath" -Level "INFO"

    # Save JSON data
    $jsonPath = Join-Path $scriptPath "../testing/.test/tracking/setup-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $setupResults | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath
    Write-TestLog "Setup data saved: $jsonPath" -Level "INFO"
}

# Function to check if running as administrator
function Test-Administrator {
    $currentUser = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    return $currentUser.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Function to create directory if it doesn't exist
function Ensure-Directory {
    param (
        [string]$Path
    )
    
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
        Write-TestLog "Created directory: $Path" -Level "INFO"
    }
}

# Main execution
try {
    Write-TestLog "Starting setup process..." -Level "INFO"

    # Step 1: Check administrator privileges
    Add-SetupStep -Name "Check Administrator" -Status "Running" -Details "Checking administrator privileges"
    if (Test-Administrator) {
        Add-SetupStep -Name "Check Administrator" -Status "Passed" -Details "Running as administrator"
    } else {
        Add-SetupStep -Name "Check Administrator" -Status "Failed" -Details "Script must be run as administrator"
        throw "Script must be run as administrator"
    }

    # Step 2: Create required directories
    Add-SetupStep -Name "Create Directories" -Status "Running" -Details "Creating required directories"
    $directories = @(
        ".codespaces/testing/.test/results",
        ".codespaces/testing/.test/tracking",
        ".codespaces/build",
        ".codespaces/setup",
        ".codespaces/services"
    )
    
    foreach ($dir in $directories) {
        Ensure-Directory -Path $dir
    }
    Add-SetupStep -Name "Create Directories" -Status "Passed" -Details "All directories created successfully"

    # Step 3: Check and install required tools
    Add-SetupStep -Name "Check Tools" -Status "Running" -Details "Checking required tools"
    $tools = @{
        "PowerShell" = { $PSVersionTable.PSVersion.Major -ge 7 }
        "Git" = { git --version }
        "Composer" = { composer --version }
        "Node.js" = { node --version }
        "npm" = { npm --version }
    }

    $missingTools = @()
    foreach ($tool in $tools.GetEnumerator()) {
        try {
            $result = & $tool.Value
            if (-not $result) {
                $missingTools += $tool.Key
            }
        } catch {
            $missingTools += $tool.Key
        }
    }

    if ($missingTools.Count -gt 0) {
        Add-SetupStep -Name "Check Tools" -Status "Failed" -Details "Missing tools: $($missingTools -join ', ')"
        throw "Missing required tools: $($missingTools -join ', ')"
    }
    Add-SetupStep -Name "Check Tools" -Status "Passed" -Details "All required tools are installed"

    # Step 4: Configure environment
    Add-SetupStep -Name "Configure Environment" -Status "Running" -Details "Configuring environment variables"
    $envFile = ".env"
    if (-not (Test-Path $envFile)) {
        @"
APP_NAME=ServiceLearningManagement
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service_learning
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"
"@ | Set-Content -Path $envFile
        Write-TestLog "Created .env file" -Level "INFO"
    }
    Add-SetupStep -Name "Configure Environment" -Status "Passed" -Details "Environment configured successfully"

    # Step 5: Initialize Git repository
    Add-SetupStep -Name "Initialize Git" -Status "Running" -Details "Initializing Git repository"
    if (-not (Test-Path ".git")) {
        git init
        git add .
        git commit -m "Initial commit"
        Write-TestLog "Initialized Git repository" -Level "INFO"
    }
    Add-SetupStep -Name "Initialize Git" -Status "Passed" -Details "Git repository initialized"

    # Setup completed successfully
    $setupResults.Status = "Success"
    Write-SetupReport
    Write-TestLog "Setup completed successfully" -Level "SUCCESS"
    exit 0
} catch {
    $setupResults.Status = "Failed"
    Write-SetupReport
    Write-TestError "Setup failed: $_"
    exit 1
} 