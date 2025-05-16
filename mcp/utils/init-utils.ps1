# MCP Utilities Initialization
$ErrorActionPreference = "Stop"

# Get script path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Define the order of script imports
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

# Import each script in order
foreach ($script in $utilityScripts) {
    $scriptPath = Join-Path -Path $scriptPath -ChildPath $script
    if (Test-Path $scriptPath) {
        . $scriptPath
    } else {
        Write-Warning "Script not found: $scriptPath"
    }
}

# Export all functions
Export-ModuleMember -Function @(
    # Logger functions
    "Write-Log",
    "Get-LogContent",
    "Clear-LogFile",
    
    # Config functions
    "Get-Config",
    "Set-Config",
    "Remove-Config",
    "Backup-Config",
    "Restore-Config",
    "Test-Config",
    
    # Environment functions
    "Get-EnvironmentConfig",
    "Set-EnvironmentConfig",
    "Remove-EnvironmentConfig",
    "Backup-EnvironmentConfig",
    "Restore-EnvironmentConfig",
    "Test-Environment",
    "Get-EnvironmentStatus",
    
    # Service functions
    "Start-Service",
    "Stop-Service",
    "Restart-Service",
    "Get-ServiceStatus",
    "Get-ServiceLogs",
    "Update-Service",
    
    # Data functions
    "Get-Data",
    "Set-Data",
    "Remove-Data",
    "Backup-Data",
    "Restore-Data",
    
    # Log functions
    "Get-Logs",
    "Clear-Logs",
    "Backup-Logs",
    "Restore-Logs",
    "Rotate-Logs",
    "Get-LogStatus",
    
    # GitHub functions
    "Get-GitHubStatus",
    "Get-GitHubActions",
    "Start-GitHubAction",
    "Stop-GitHubAction",
    "Get-GitHubActionLogs",
    "Test-GitHubConnection",
    
    # MCP functions
    "Initialize-MCP",
    "Invoke-MCPCommand",
    "Test-MCPHealth",
    "Get-MCPStatus",
    "Update-MCP",
    "Backup-MCP",
    "Restore-MCP"
) 