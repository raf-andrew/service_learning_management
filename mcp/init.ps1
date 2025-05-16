# Core MCP Initialization
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path -Path $scriptPath -ChildPath "utils"

# Import utility modules
. (Join-Path -Path $utilsPath -ChildPath "logger.ps1")
. (Join-Path -Path $utilsPath -ChildPath "environment.ps1")
. (Join-Path -Path $utilsPath -ChildPath "services.ps1")

# Import core MCP
. (Join-Path -Path $scriptPath -ChildPath "mcp.ps1")

# Initialize logging
Initialize-Logging -LogLevel "INFO"

Write-Log "MCP initialized successfully" -Level "INFO" -Category "Initialization" 