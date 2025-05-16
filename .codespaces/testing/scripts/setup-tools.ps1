# Setup Tools Script
# This script automatically installs all required tools for development

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

Write-Host '[DEBUG] Entering setup-tools.ps1'

function Test-IsAdmin {
    $currentUser = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
    return $currentUser.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Install-Chocolatey {
    Write-TestLog "Checking Chocolatey installation..."
    if (-not (Get-Command choco -ErrorAction SilentlyContinue)) {
        Write-TestLog "Installing Chocolatey..."
        Set-ExecutionPolicy Bypass -Scope Process -Force
        [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
        Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
    } else {
        Write-TestLog "Chocolatey is already installed"
    }
}

function Install-Laragon {
    Write-TestLog "Checking Laragon installation..."
    if (-not (Test-Path "C:\laragon")) {
        Write-TestLog "Downloading Laragon..."
        $laragonUrl = "https://github.com/leokhoa/laragon/releases/download/6.0.0/laragon-wamp.exe"
        $laragonInstaller = Join-Path $env:TEMP "laragon-installer.exe"
        
        Invoke-WebRequest -Uri $laragonUrl -OutFile $laragonInstaller
        
        Write-TestLog "Installing Laragon..."
        Start-Process -FilePath $laragonInstaller -ArgumentList "/SILENT" -Wait
        
        # Add Laragon to PATH
        $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";C:\laragon\bin"
        [Environment]::SetEnvironmentVariable("Path", $env:Path, "Machine")
    } else {
        Write-TestLog "Laragon is already installed"
    }
}

function Install-Jq {
    Write-TestLog "Checking jq installation..."
    if (-not (Get-Command jq -ErrorAction SilentlyContinue)) {
        Write-TestLog "Installing jq..."
        choco install jq -y
    } else {
        Write-TestLog "jq is already installed"
    }
}

function Install-GitHubCLI {
    Write-TestLog "Checking GitHub CLI installation..."
    if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
        Write-TestLog "Installing GitHub CLI..."
        choco install gh -y
    } else {
        Write-TestLog "GitHub CLI is already installed"
    }
}

function Install-NodeJS {
    Write-TestLog "Checking Node.js installation..."
    if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
        Write-TestLog "Installing Node.js..."
        choco install nodejs-lts -y
    } else {
        Write-TestLog "Node.js is already installed"
    }
}

function Install-Composer {
    Write-TestLog "Checking Composer installation..."
    if (-not (Get-Command composer -ErrorAction SilentlyContinue)) {
        Write-TestLog "Installing Composer..."
        choco install composer -y
    } else {
        Write-TestLog "Composer is already installed"
    }
}

function Update-Path {
    Write-TestLog "Updating PATH environment variable..."
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine")
    [Environment]::SetEnvironmentVariable("Path", $env:Path, "Process")
}

function Test-Tools {
    Write-TestLog "Testing installed tools..."
    $tools = @(
        @{ Name = "PHP"; Command = "php -v" },
        @{ Name = "Composer"; Command = "composer -V" },
        @{ Name = "Node.js"; Command = "node -v" },
        @{ Name = "npm"; Command = "npm -v" },
        @{ Name = "jq"; Command = "jq --version" },
        @{ Name = "GitHub CLI"; Command = "gh --version" }
    )

    foreach ($tool in $tools) {
        try {
            $output = Invoke-Expression $tool.Command 2>&1
            Write-TestLog "$($tool.Name) is working: $output"
        } catch {
            Write-TestError "Error testing $($tool.Name): $_"
        }
    }
}

# Main execution
try {
    Write-TestLog "Starting tools installation..."

    # Check for admin rights
    if (-not (Test-IsAdmin)) {
        Write-TestError "This script requires administrator privileges. Please run as administrator."
        exit 1
    }

    # Install tools
    Install-Chocolatey
    Install-Laragon
    Install-Jq
    Install-GitHubCLI
    Install-NodeJS
    Install-Composer

    # Update PATH
    Update-Path

    # Test installations
    Test-Tools

    Write-TestLog "Tools installation completed successfully"
} catch {
    Write-TestError "Error during tools installation: $_"
    exit 1
}

Write-Host '[DEBUG] Exiting setup-tools.ps1' 