# MCP Utilities Initialization
$ErrorActionPreference = "Stop"

# Get script path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Import utilities
. (Join-Path -Path $scriptPath -ChildPath "init-utils.ps1")

# Define utility paths
$utilsPath = $scriptPath

# Import utilities in the correct order
$utilityScripts = @(
    "logger.ps1",
    "config.ps1",
    "environment.ps1",
    "services.ps1",
    "data.ps1",
    "logs.ps1",
    "github.ps1",
    "mcp.ps1"
)

# Import each utility script
foreach ($script in $utilityScripts) {
    $scriptPath = Join-Path -Path $utilsPath -ChildPath $script
    if (Test-Path -Path $scriptPath) {
        . $scriptPath
    }
    else {
        Write-Warning "Utility script not found: $scriptPath"
    }
}

# Export all functions
$functions = @(
    # Logger functions
    "Write-Log",
    "Get-LogContent",
    "Clear-LogFile",
    
    # Config functions
    "Get-Config",
    "Set-Config",
    "Test-Config",
    
    # Environment functions
    "Get-Environment",
    "Set-Environment",
    "Test-Environment",
    
    # Service functions
    "Get-ServiceStatus",
    "Start-Service",
    "Stop-Service",
    "Restart-Service",
    "Test-ServiceHealth",
    
    # Data functions
    "Get-DataSource",
    "Test-DataSource",
    "Backup-DataSource",
    
    # Log functions
    "Get-Logs",
    "Clear-Logs",
    "Archive-Logs",
    
    # GitHub functions
    "Get-GitHubStatus",
    "Sync-GitHub",
    
    # MCP functions
    "Initialize-MCP",
    "Test-MCPHealth",
    "Get-MCPStatus"
)

# Export functions
Export-ModuleMember -Function $functions 