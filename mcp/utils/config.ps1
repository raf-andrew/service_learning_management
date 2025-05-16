# Configuration Utility Module
$ErrorActionPreference = "Stop"

# Get script path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Import required modules
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "mcp.ps1")

# Configuration Management Functions
function Get-Config {
    param (
        [string]$Section,
        
        [string]$Key,
        
        [switch]$Validate
    )
    
    Write-Log "Getting configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Section) {
            $parameters.Section = $Section
        }
        
        if ($Key) {
            $parameters.Key = $Key
        }
        
        if ($Validate) {
            $parameters.Validate = $true
        }
        
        # Execute get command
        $result = Invoke-MCPCommand -Command "get" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get configuration" -Level "ERROR" -Category "Config"
            return $null
        }
        
        Write-Log "Retrieved configuration successfully" -Level "SUCCESS" -Category "Config"
        return $result
    }
    catch {
        Write-Log "Error getting configuration: $_" -Level "ERROR" -Category "Config"
        return $null
    }
}

function Set-Config {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Section,
        
        [Parameter(Mandatory=$true)]
        [string]$Key,
        
        [Parameter(Mandatory=$true)]
        [string]$Value,
        
        [switch]$Validate,
        
        [switch]$Backup
    )
    
    Write-Log "Setting configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{
            Section = $Section
            Key = $Key
            Value = $Value
        }
        
        if ($Validate) {
            $parameters.Validate = $true
        }
        
        # Backup configuration if requested
        if ($Backup) {
            $backupResult = Backup-Config
            if (-not $backupResult) {
                Write-Log "Failed to backup configuration" -Level "ERROR" -Category "Config"
                return $false
            }
        }
        
        # Execute set command
        $result = Invoke-MCPCommand -Command "set" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to set configuration" -Level "ERROR" -Category "Config"
            return $false
        }
        
        Write-Log "Set configuration successfully" -Level "SUCCESS" -Category "Config"
        return $true
    }
    catch {
        Write-Log "Error setting configuration: $_" -Level "ERROR" -Category "Config"
        return $false
    }
}

function Remove-Config {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Section,
        
        [string]$Key,
        
        [switch]$Backup
    )
    
    Write-Log "Removing configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{
            Section = $Section
        }
        
        if ($Key) {
            $parameters.Key = $Key
        }
        
        # Backup configuration if requested
        if ($Backup) {
            $backupResult = Backup-Config
            if (-not $backupResult) {
                Write-Log "Failed to backup configuration" -Level "ERROR" -Category "Config"
                return $false
            }
        }
        
        # Execute remove command
        $result = Invoke-MCPCommand -Command "remove" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to remove configuration" -Level "ERROR" -Category "Config"
            return $false
        }
        
        Write-Log "Removed configuration successfully" -Level "SUCCESS" -Category "Config"
        return $true
    }
    catch {
        Write-Log "Error removing configuration: $_" -Level "ERROR" -Category "Config"
        return $false
    }
}

function Backup-Config {
    param (
        [string]$Section
    )
    
    Write-Log "Backing up configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Section) {
            $parameters.Section = $Section
        }
        
        # Execute backup command
        $result = Invoke-MCPCommand -Command "backup" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to backup configuration" -Level "ERROR" -Category "Config"
            return $false
        }
        
        Write-Log "Backed up configuration successfully" -Level "SUCCESS" -Category "Config"
        return $true
    }
    catch {
        Write-Log "Error backing up configuration: $_" -Level "ERROR" -Category "Config"
        return $false
    }
}

function Restore-Config {
    param (
        [Parameter(Mandatory=$true)]
        [string]$BackupId
    )
    
    Write-Log "Restoring configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Execute restore command
        $result = Invoke-MCPCommand -Command "restore" -Service "config" -Parameters @{
            BackupId = $BackupId
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to restore configuration" -Level "ERROR" -Category "Config"
            return $false
        }
        
        Write-Log "Restored configuration successfully" -Level "SUCCESS" -Category "Config"
        return $true
    }
    catch {
        Write-Log "Error restoring configuration: $_" -Level "ERROR" -Category "Config"
        return $false
    }
}

function Test-Config {
    param (
        [string]$Section
    )
    
    Write-Log "Testing configuration..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{}
        
        if ($Section) {
            $parameters.Section = $Section
        }
        
        # Execute test command
        $result = Invoke-MCPCommand -Command "test" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to test configuration" -Level "ERROR" -Category "Config"
            return $false
        }
        
        Write-Log "Tested configuration successfully" -Level "SUCCESS" -Category "Config"
        return $result
    }
    catch {
        Write-Log "Error testing configuration: $_" -Level "ERROR" -Category "Config"
        return $false
    }
}

function Get-ConfigStatus {
    param (
        [string]$Section,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting configuration status..." -Level "INFO" -Category "Config"
    
    try {
        # Build parameters
        $parameters = @{
            Detailed = $Detailed
        }
        
        if ($Section) {
            $parameters.Section = $Section
        }
        
        # Execute status command
        $result = Invoke-MCPCommand -Command "status" -Service "config" -Parameters $parameters
        
        if ($null -eq $result) {
            Write-Log "Failed to get configuration status" -Level "ERROR" -Category "Config"
            return $null
        }
        
        Write-Log "Retrieved configuration status successfully" -Level "SUCCESS" -Category "Config"
        return $result
    }
    catch {
        Write-Log "Error getting configuration status: $_" -Level "ERROR" -Category "Config"
        return $null
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Get-Config",
    "Set-Config",
    "Remove-Config",
    "Backup-Config",
    "Restore-Config",
    "Test-Config",
    "Get-ConfigStatus"
) 