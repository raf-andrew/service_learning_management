# Logs Utility Module
$ErrorActionPreference = "Stop"

# Get script path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Import required modules
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "mcp.ps1")

# Log Management Functions
function Get-Logs {
    param (
        [string]$Category,
        
        [string]$Level,
        
        [string]$SearchString,
        
        [int]$Lines = 100,
        
        [switch]$Follow,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting logs..." -Level "INFO" -Category "Logs"
    
    try {
        # Build parameters
        $parameters = @{
            Lines = $Lines
        }
        
        if ($Category) {
            $parameters.Category = $Category
        }
        
        if ($Level) {
            $parameters.Level = $Level
        }
        
        if ($SearchString) {
            $parameters.SearchString = $SearchString
        }
        
        if ($Follow) {
            $parameters.Follow = $true
        }
        
        if ($Detailed) {
            $parameters.Detailed = $true
        }
        
        # Execute logs command
        $result = Invoke-MCPCommand -Command "logs" -Service "logs" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get logs" -Level "ERROR" -Category "Logs"
            return $null
        }
        
        Write-Log "Retrieved logs successfully" -Level "SUCCESS" -Category "Logs"
        return $result
    }
    catch {
        Write-Log "Error getting logs: $_" -Level "ERROR" -Category "Logs"
        return $null
    }
}

function Clear-Logs {
    param (
        [string]$Category,
        
        [string]$Level,
        
        [string]$SearchString,
        
        [switch]$Backup
    )
    
    Write-Log "Clearing logs..." -Level "INFO" -Category "Logs"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Category) {
            $parameters.Category = $Category
        }
        
        if ($Level) {
            $parameters.Level = $Level
        }
        
        if ($SearchString) {
            $parameters.SearchString = $SearchString
        }
        
        # Backup logs if requested
        if ($Backup) {
            $backupResult = Backup-Logs -Category $Category -Level $Level -SearchString $SearchString
            if (-not $backupResult) {
                Write-Log "Failed to backup logs" -Level "ERROR" -Category "Logs"
                return $false
            }
        }
        
        # Execute clear command
        $result = Invoke-MCPCommand -Command "clear" -Service "logs" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to clear logs" -Level "ERROR" -Category "Logs"
            return $false
        }
        
        Write-Log "Cleared logs successfully" -Level "SUCCESS" -Category "Logs"
        return $true
    }
    catch {
        Write-Log "Error clearing logs: $_" -Level "ERROR" -Category "Logs"
        return $false
    }
}

function Backup-Logs {
    param (
        [string]$Category,
        
        [string]$Level,
        
        [string]$SearchString
    )
    
    Write-Log "Backing up logs..." -Level "INFO" -Category "Logs"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Category) {
            $parameters.Category = $Category
        }
        
        if ($Level) {
            $parameters.Level = $Level
        }
        
        if ($SearchString) {
            $parameters.SearchString = $SearchString
        }
        
        # Execute backup command
        $result = Invoke-MCPCommand -Command "backup" -Service "logs" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to backup logs" -Level "ERROR" -Category "Logs"
            return $false
        }
        
        Write-Log "Backed up logs successfully" -Level "SUCCESS" -Category "Logs"
        return $true
    }
    catch {
        Write-Log "Error backing up logs: $_" -Level "ERROR" -Category "Logs"
        return $false
    }
}

function Restore-Logs {
    param (
        [Parameter(Mandatory=$true)]
        [string]$BackupId
    )
    
    Write-Log "Restoring logs..." -Level "INFO" -Category "Logs"
    
    try {
        # Execute restore command
        $result = Invoke-MCPCommand -Command "restore" -Service "logs" -Parameters @{
            BackupId = $BackupId
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to restore logs" -Level "ERROR" -Category "Logs"
            return $false
        }
        
        Write-Log "Restored logs successfully" -Level "SUCCESS" -Category "Logs"
        return $true
    }
    catch {
        Write-Log "Error restoring logs: $_" -Level "ERROR" -Category "Logs"
        return $false
    }
}

function Rotate-Logs {
    param (
        [string]$Category
    )
    
    Write-Log "Rotating logs..." -Level "INFO" -Category "Logs"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Category) {
            $parameters.Category = $Category
        }
        
        # Execute rotate command
        $result = Invoke-MCPCommand -Command "rotate" -Service "logs" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to rotate logs" -Level "ERROR" -Category "Logs"
            return $false
        }
        
        Write-Log "Rotated logs successfully" -Level "SUCCESS" -Category "Logs"
        return $true
    }
    catch {
        Write-Log "Error rotating logs: $_" -Level "ERROR" -Category "Logs"
        return $false
    }
}

function Get-LogStatus {
    param (
        [string]$Category,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting log status..." -Level "INFO" -Category "Logs"
    
    try {
        # Build parameters
        $parameters = @{
            Detailed = $Detailed
        }
        
        if ($Category) {
            $parameters.Category = $Category
        }
        
        # Execute status command
        $result = Invoke-MCPCommand -Command "status" -Service "logs" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get log status" -Level "ERROR" -Category "Logs"
            return $null
        }
        
        Write-Log "Retrieved log status successfully" -Level "SUCCESS" -Category "Logs"
        return $result
    }
    catch {
        Write-Log "Error getting log status: $_" -Level "ERROR" -Category "Logs"
        return $null
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Get-Logs",
    "Clear-Logs",
    "Backup-Logs",
    "Restore-Logs",
    "Rotate-Logs",
    "Get-LogStatus"
) 