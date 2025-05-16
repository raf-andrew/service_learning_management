# MCP Setup Script
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path $scriptPath ".." "utils"
. (Join-Path $utilsPath "logger.ps1")
. (Join-Path $utilsPath "mcp.ps1")

# Setup Configuration
$setupConfig = @{
    Prerequisites = @{
        PowerShell = @{
            Version = "5.1"
            Modules = @("PSModulePath", "PSDefaultParameterValues")
        }
        Services = @{
            MySQL = @{
                Required = $true
                Version = "8.0"
                Commands = @("mysql", "mysqldump")
            }
            Redis = @{
                Required = $true
                Version = "6.0"
                Commands = @("redis-cli")
            }
            Apache = @{
                Required = $true
                Version = "2.4"
                Commands = @("httpd", "apachectl")
            }
        }
        Tools = @{
            Git = @{
                Required = $true
                Version = "2.0"
                Commands = @("git")
            }
            Node = @{
                Required = $true
                Version = "16.0"
                Commands = @("node", "npm")
            }
        }
    }
    Directories = @{
        Logs = "logs"
        Config = "config"
        Data = "data"
        Backups = "backups"
        Tests = "tests"
    }
    Files = @{
        Config = "config/mcp.json"
        Schema = "config/schema.json"
        Rules = "config/rules.json"
    }
}

# Check prerequisites
function Test-Prerequisites {
    Write-Log "Checking prerequisites..." -Level "INFO" -Category "Setup"
    
    $success = $true
    
    # Check PowerShell version
    $psVersion = $PSVersionTable.PSVersion
    if ($psVersion.Major -lt [int]$setupConfig.Prerequisites.PowerShell.Version.Split(".")[0]) {
        Write-Log "PowerShell version $($setupConfig.Prerequisites.PowerShell.Version) or higher is required. Current version: $psVersion" -Level "ERROR" -Category "Setup"
        $success = $false
    }
    
    # Check required modules
    foreach ($module in $setupConfig.Prerequisites.PowerShell.Modules) {
        if (-not (Get-Module -ListAvailable -Name $module)) {
            Write-Log "PowerShell module '$module' is required but not installed" -Level "ERROR" -Category "Setup"
            $success = $false
        }
    }
    
    # Check services
    foreach ($service in $setupConfig.Prerequisites.Services.Keys) {
        $serviceConfig = $setupConfig.Prerequisites.Services[$service]
        if ($serviceConfig.Required) {
            foreach ($command in $serviceConfig.Commands) {
                if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
                    Write-Log "Service '$service' command '$command' is required but not found" -Level "ERROR" -Category "Setup"
                    $success = $false
                }
            }
        }
    }
    
    # Check tools
    foreach ($tool in $setupConfig.Prerequisites.Tools.Keys) {
        $toolConfig = $setupConfig.Prerequisites.Tools[$tool]
        if ($toolConfig.Required) {
            foreach ($command in $toolConfig.Commands) {
                if (-not (Get-Command $command -ErrorAction SilentlyContinue)) {
                    Write-Log "Tool '$tool' command '$command' is required but not found" -Level "ERROR" -Category "Setup"
                    $success = $false
                }
            }
        }
    }
    
    return $success
}

# Create required directories
function Initialize-Directories {
    Write-Log "Creating required directories..." -Level "INFO" -Category "Setup"
    
    $success = $true
    
    foreach ($dir in $setupConfig.Directories.Keys) {
        $path = Join-Path $scriptPath ".." $setupConfig.Directories[$dir]
        if (-not (Test-Path $path)) {
            try {
                New-Item -ItemType Directory -Path $path -Force | Out-Null
                Write-Log "Created directory: $path" -Level "INFO" -Category "Setup"
            }
            catch {
                Write-Log "Failed to create directory '$path': $_" -Level "ERROR" -Category "Setup"
                $success = $false
            }
        }
    }
    
    return $success
}

# Initialize configuration files
function Initialize-ConfigFiles {
    Write-Log "Initializing configuration files..." -Level "INFO" -Category "Setup"
    
    $success = $true
    
    # Create config directory if it doesn't exist
    $configPath = Join-Path $scriptPath ".." $setupConfig.Directories.Config
    if (-not (Test-Path $configPath)) {
        New-Item -ItemType Directory -Path $configPath -Force | Out-Null
    }
    
    # Initialize each config file
    foreach ($file in $setupConfig.Files.Keys) {
        $filePath = Join-Path $scriptPath ".." $setupConfig.Files[$file]
        if (-not (Test-Path $filePath)) {
            try {
                # Create default configuration based on file type
                switch ($file) {
                    "Config" {
                        $defaultConfig = @{
                            Version = "1.0.0"
                            Environment = "local"
                            Services = @{}
                            Data = @{}
                            Logs = @{}
                        }
                        $defaultConfig | ConvertTo-Json -Depth 10 | Set-Content $filePath
                    }
                    "Schema" {
                        $defaultSchema = @{
                            Version = "1.0.0"
                            Types = @{}
                            Properties = @{}
                        }
                        $defaultSchema | ConvertTo-Json -Depth 10 | Set-Content $filePath
                    }
                    "Rules" {
                        $defaultRules = @{
                            Version = "1.0.0"
                            Rules = @{}
                        }
                        $defaultRules | ConvertTo-Json -Depth 10 | Set-Content $filePath
                    }
                }
                Write-Log "Created configuration file: $filePath" -Level "INFO" -Category "Setup"
            }
            catch {
                Write-Log "Failed to create configuration file '$filePath': $_" -Level "ERROR" -Category "Setup"
                $success = $false
            }
        }
    }
    
    return $success
}

# Run setup
function Start-MCPSetup {
    param (
        [switch]$Force,
        [switch]$SkipTests
    )
    
    Write-Log "Starting MCP setup..." -Level "INFO" -Category "Setup"
    
    try {
        # Check prerequisites
        if (-not (Test-Prerequisites)) {
            if (-not $Force) {
                Write-Log "Prerequisites check failed. Use -Force to continue anyway." -Level "ERROR" -Category "Setup"
                return $false
            }
            Write-Log "Continuing despite failed prerequisites..." -Level "WARNING" -Category "Setup"
        }
        
        # Create directories
        if (-not (Initialize-Directories)) {
            Write-Log "Failed to create required directories" -Level "ERROR" -Category "Setup"
            return $false
        }
        
        # Initialize config files
        if (-not (Initialize-ConfigFiles)) {
            Write-Log "Failed to initialize configuration files" -Level "ERROR" -Category "Setup"
            return $false
        }
        
        # Initialize MCP
        if (-not (Initialize-MCP -Verify -AutoHeal)) {
            Write-Log "Failed to initialize MCP" -Level "ERROR" -Category "Setup"
            return $false
        }
        
        # Run tests if not skipped
        if (-not $SkipTests) {
            Write-Log "Running setup tests..." -Level "INFO" -Category "Setup"
            $testPath = Join-Path $scriptPath ".." "tests"
            $testScript = Join-Path $testPath "run-tests.ps1"
            
            if (Test-Path $testScript) {
                $testResult = & $testScript -All
                if (-not $testResult) {
                    Write-Log "Setup tests failed" -Level "ERROR" -Category "Setup"
                    return $false
                }
            }
            else {
                Write-Log "Test script not found: $testScript" -Level "WARNING" -Category "Setup"
            }
        }
        
        Write-Log "MCP setup completed successfully" -Level "SUCCESS" -Category "Setup"
        return $true
    }
    catch {
        Write-Log "Error during MCP setup: $_" -Level "ERROR" -Category "Setup"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Test-Prerequisites",
    "Initialize-Directories",
    "Initialize-ConfigFiles",
    "Start-MCPSetup"
) 