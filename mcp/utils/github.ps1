# GitHub Utility Module
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "mcp.ps1")

# GitHub Management Functions
function Get-GitHubStatus {
    param (
        [string]$Repository,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting GitHub status..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{
            Detailed = $Detailed
        }
        
        if ($Repository) {
            $parameters.Repository = $Repository
        }
        
        # Execute status command
        $result = Invoke-MCPCommand -Command "status" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get GitHub status" -Level "ERROR" -Category "GitHub"
            return $null
        }
        
        Write-Log "Retrieved GitHub status successfully" -Level "SUCCESS" -Category "GitHub"
        return $result
    }
    catch {
        Write-Log "Error getting GitHub status: $_" -Level "ERROR" -Category "GitHub"
        return $null
    }
}

function Get-GitHubActions {
    param (
        [string]$Repository,
        
        [string]$Workflow,
        
        [string]$Status,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting GitHub actions..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{
            Detailed = $Detailed
        }
        
        if ($Repository) {
            $parameters.Repository = $Repository
        }
        
        if ($Workflow) {
            $parameters.Workflow = $Workflow
        }
        
        if ($Status) {
            $parameters.Status = $Status
        }
        
        # Execute actions command
        $result = Invoke-MCPCommand -Command "actions" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get GitHub actions" -Level "ERROR" -Category "GitHub"
            return $null
        }
        
        Write-Log "Retrieved GitHub actions successfully" -Level "SUCCESS" -Category "GitHub"
        return $result
    }
    catch {
        Write-Log "Error getting GitHub actions: $_" -Level "ERROR" -Category "GitHub"
        return $null
    }
}

function Start-GitHubAction {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Repository,
        
        [Parameter(Mandatory=$true)]
        [string]$Workflow,
        
        [hashtable]$Parameters = @{},
        
        [switch]$Wait
    )
    
    Write-Log "Starting GitHub action..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{
            Repository = $Repository
            Workflow = $Workflow
            Parameters = $Parameters
            Wait = $Wait
        }
        
        # Execute start command
        $result = Invoke-MCPCommand -Command "start" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to start GitHub action" -Level "ERROR" -Category "GitHub"
            return $false
        }
        
        Write-Log "Started GitHub action successfully" -Level "SUCCESS" -Category "GitHub"
        return $true
    }
    catch {
        Write-Log "Error starting GitHub action: $_" -Level "ERROR" -Category "GitHub"
        return $false
    }
}

function Stop-GitHubAction {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Repository,
        
        [Parameter(Mandatory=$true)]
        [string]$Workflow,
        
        [Parameter(Mandatory=$true)]
        [string]$RunId,
        
        [switch]$Force
    )
    
    Write-Log "Stopping GitHub action..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{
            Repository = $Repository
            Workflow = $Workflow
            RunId = $RunId
            Force = $Force
        }
        
        # Execute stop command
        $result = Invoke-MCPCommand -Command "stop" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to stop GitHub action" -Level "ERROR" -Category "GitHub"
            return $false
        }
        
        Write-Log "Stopped GitHub action successfully" -Level "SUCCESS" -Category "GitHub"
        return $true
    }
    catch {
        Write-Log "Error stopping GitHub action: $_" -Level "ERROR" -Category "GitHub"
        return $false
    }
}

function Get-GitHubActionLogs {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Repository,
        
        [Parameter(Mandatory=$true)]
        [string]$Workflow,
        
        [Parameter(Mandatory=$true)]
        [string]$RunId,
        
        [string]$Job,
        
        [string]$Step,
        
        [switch]$Follow
    )
    
    Write-Log "Getting GitHub action logs..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{
            Repository = $Repository
            Workflow = $Workflow
            RunId = $RunId
            Follow = $Follow
        }
        
        if ($Job) {
            $parameters.Job = $Job
        }
        
        if ($Step) {
            $parameters.Step = $Step
        }
        
        # Execute logs command
        $result = Invoke-MCPCommand -Command "logs" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get GitHub action logs" -Level "ERROR" -Category "GitHub"
            return $null
        }
        
        Write-Log "Retrieved GitHub action logs successfully" -Level "SUCCESS" -Category "GitHub"
        return $result
    }
    catch {
        Write-Log "Error getting GitHub action logs: $_" -Level "ERROR" -Category "GitHub"
        return $null
    }
}

function Test-GitHubConnection {
    param (
        [string]$Repository
    )
    
    Write-Log "Testing GitHub connection..." -Level "INFO" -Category "GitHub"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Repository) {
            $parameters.Repository = $Repository
        }
        
        # Execute test command
        $result = Invoke-MCPCommand -Command "test" -Service "github" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to test GitHub connection" -Level "ERROR" -Category "GitHub"
            return $false
        }
        
        Write-Log "Tested GitHub connection successfully" -Level "SUCCESS" -Category "GitHub"
        return $result
    }
    catch {
        Write-Log "Error testing GitHub connection: $_" -Level "ERROR" -Category "GitHub"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Get-GitHubStatus",
    "Get-GitHubActions",
    "Start-GitHubAction",
    "Stop-GitHubAction",
    "Get-GitHubActionLogs",
    "Test-GitHubConnection"
) 