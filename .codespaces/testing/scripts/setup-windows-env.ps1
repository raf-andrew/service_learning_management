# Windows Test Environment Setup Script

# Import logging module
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. "$scriptPath/check-prerequisites.ps1"

# Start logging
$logFile = Join-Path $scriptPath "setup-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"
Start-Transcript -Path $logFile -Force

Write-Log "Starting Windows test environment setup..." -Color "Cyan"

# Run prerequisite checks
Write-Log "Running prerequisite checks..." -Color "Cyan"
$prereqScript = Join-Path $scriptPath "check-prerequisites.ps1"
if (-not (Test-Path $prereqScript)) {
    Write-Error "Prerequisite checker script not found at: $prereqScript"
    exit 1
}

try {
    & $prereqScript
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Prerequisite checks failed. Please resolve the issues before proceeding."
        exit 1
    }
} catch {
    Write-Error "Error running prerequisite checks: $_"
    exit 1
}

# Function to test if a command exists
function Test-Command {
    param ($command)
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'stop'
    try { if (Get-Command $command) { return $true } }
    catch { return $false }
    finally { $ErrorActionPreference = $oldPreference }
}

# Function to install winget package with logging
function Install-WingetPackage {
    param (
        [string]$PackageId,
        [string]$PackageName
    )
    
    Write-Log "Installing $PackageName..." -Color "Cyan"
    try {
        $output = winget install --id $PackageId --silent --accept-source-agreements --accept-package-agreements 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "$PackageName installed successfully"
            return $true
        } else {
            Write-Error "Failed to install $PackageName. Exit code: $LASTEXITCODE"
            Write-Log "Installation output: $output" -Level "DEBUG"
            return $false
        }
    } catch {
        Write-Error "Error installing $PackageName: $_"
        return $false
    }
}

# Create necessary directories with logging
Write-Log "Creating required directories..." -Color "Cyan"
$directories = @(
    ".codespaces/testing",
    ".codespaces/config",
    ".codespaces/state",
    ".codespaces/scripts",
    ".codespaces/testing/results",
    ".codespaces/testing/archives"
)

foreach ($dir in $directories) {
    try {
        if (-not (Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-Success "Created directory: $dir"
        } else {
            Write-Log "Directory already exists: $dir" -Level "INFO"
        }
    } catch {
        Write-Error "Failed to create directory $dir: $_"
        exit 1
    }
}

# Install required tools with logging
Write-Log "Installing required tools..." -Color "Cyan"
$tools = @(
    @{Name = "jq"; PackageId = "stedolan.jq"; Description = "JSON processor"},
    @{Name = "gh"; PackageId = "GitHub.cli"; Description = "GitHub CLI"}
)

foreach ($tool in $tools) {
    if (-not (Test-Command $tool.Name)) {
        Write-Log "Installing $($tool.Description)..." -Color "Cyan"
        if (-not (Install-WingetPackage -PackageId $tool.PackageId -PackageName $tool.Description)) {
            Write-Error "Failed to install $($tool.Description). Setup cannot continue."
            exit 1
        }
    } else {
        Write-Success "$($tool.Description) is already installed"
    }
}

# Set environment variables with logging
Write-Log "Setting environment variables..." -Color "Cyan"
$envVars = @{
    "TEST_MACHINE" = "basicLinux32gb"
    "TEST_REGION" = "EastUs"
    "TEST_ENV" = "test"
    "TEST_BRANCH" = "test-codespaces"
    "TEST_REPO" = "service_learning_management"
}

foreach ($var in $envVars.GetEnumerator()) {
    try {
        [Environment]::SetEnvironmentVariable($var.Key, $var.Value, "Process")
        Write-Success "Set environment variable: $($var.Key)=$($var.Value)"
    } catch {
        Write-Error "Failed to set environment variable $($var.Key): $_"
        exit 1
    }
}

# Create test configuration with logging
Write-Log "Creating test configuration..." -Color "Cyan"
$configPath = ".codespaces/config/test-codespaces.json"
$config = @{
    name = "test-codespaces"
    version = "1.0.0"
    defaults = @{
        machine = $envVars["TEST_MACHINE"]
        region = $envVars["TEST_REGION"]
        branch = $envVars["TEST_BRANCH"]
    }
    environments = @{
        test = @{
            machine = $envVars["TEST_MACHINE"]
            region = $envVars["TEST_REGION"]
            branch = $envVars["TEST_BRANCH"]
            features = @{
                github = $true
                codespaces = $true
            }
        }
    }
}

try {
    $config | ConvertTo-Json -Depth 10 | Set-Content $configPath
    Write-Success "Created test configuration at: $configPath"
} catch {
    Write-Error "Failed to create test configuration: $_"
    exit 1
}

# Create test state with logging
Write-Log "Creating test state..." -Color "Cyan"
$statePath = ".codespaces/state/test-state.json"
$state = @{
    repository = $envVars["TEST_REPO"]
    branch = $envVars["TEST_BRANCH"]
    environment = $envVars["TEST_ENV"]
    github = @{
        authenticated = $false
        token = ""
    }
    codespaces = @{
        active = @()
        pending = @()
        failed = @()
    }
}

try {
    $state | ConvertTo-Json -Depth 10 | Set-Content $statePath
    Write-Success "Created test state at: $statePath"
} catch {
    Write-Error "Failed to create test state: $_"
    exit 1
}

# Verify GitHub CLI authentication with logging
Write-Log "Verifying GitHub CLI authentication..." -Color "Cyan"
if (-not (Test-Command gh)) {
    Write-Error "GitHub CLI not found. Please install it manually."
    exit 1
}

try {
    $authStatus = gh auth status 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Success "GitHub CLI is authenticated"
        $state.github.authenticated = $true
        $state | ConvertTo-Json -Depth 10 | Set-Content $statePath
    } else {
        Write-Warning "GitHub CLI is not authenticated. Please run 'gh auth login'"
    }
} catch {
    Write-Error "Error checking GitHub CLI authentication: $_"
    exit 1
}

# Log completion
Write-Success "Windows test environment setup completed!"
Write-Log "`nNext steps:" -Color "Cyan"
Write-Log "1. Run 'gh auth login' if not already authenticated"
Write-Log "2. Verify GitHub CLI is working with 'gh codespace list'"
Write-Log "3. Run the infrastructure tests with './.codespaces/testing/scripts/run-infra-tests.sh'"

# Stop logging
Stop-Transcript
Write-Log "Setup log saved to: $logFile" -Color "Cyan" 