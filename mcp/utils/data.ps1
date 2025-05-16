# Data Utility Module
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "mcp.ps1")

# Data Source Management Functions
function Get-Data {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source,
        
        [hashtable]$Parameters = @{},
        
        [switch]$Validate
    )
    
    Write-Log "Getting data from source: $Source" -Level "INFO" -Category "Data"
    
    try {
        # Get source configuration
        $sourceConfig = $mcpConfig.Data.Sources[$Source]
        if ($null -eq $sourceConfig) {
            Write-Log "Data source '$Source' not found" -Level "ERROR" -Category "Data"
            return $null
        }
        
        # Execute get command
        $result = Invoke-MCPCommand -Command "get" -Service "data" -Parameters @{
            Source = $Source
            Parameters = $Parameters
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to get data from source: $Source" -Level "ERROR" -Category "Data"
            return $null
        }
        
        # Validate data
        if ($Validate) {
            $validationRules = $mcpConfig.Data.Validation[$Source]
            if ($null -ne $validationRules) {
                $isValid = Test-DataValidation -Data $result -Rules $validationRules
                if (-not $isValid) {
                    Write-Log "Data validation failed for source: $Source" -Level "ERROR" -Category "Data"
                    return $null
                }
            }
        }
        
        Write-Log "Retrieved data from source '$Source' successfully" -Level "SUCCESS" -Category "Data"
        return $result
    }
    catch {
        Write-Log "Error getting data from source '$Source': $_" -Level "ERROR" -Category "Data"
        return $null
    }
}

function Set-Data {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Data,
        
        [hashtable]$Parameters = @{},
        
        [switch]$Validate,
        
        [switch]$Backup
    )
    
    Write-Log "Setting data for source: $Source" -Level "INFO" -Category "Data"
    
    try {
        # Get source configuration
        $sourceConfig = $mcpConfig.Data.Sources[$Source]
        if ($null -eq $sourceConfig) {
            Write-Log "Data source '$Source' not found" -Level "ERROR" -Category "Data"
            return $false
        }
        
        # Validate data
        if ($Validate) {
            $validationRules = $mcpConfig.Data.Validation[$Source]
            if ($null -ne $validationRules) {
                $isValid = Test-DataValidation -Data $Data -Rules $validationRules
                if (-not $isValid) {
                    Write-Log "Data validation failed for source: $Source" -Level "ERROR" -Category "Data"
                    return $false
                }
            }
        }
        
        # Backup data if requested
        if ($Backup) {
            $backupResult = Backup-Data -Source $Source
            if (-not $backupResult) {
                Write-Log "Failed to backup data for source: $Source" -Level "ERROR" -Category "Data"
                return $false
            }
        }
        
        # Execute set command
        $result = Invoke-MCPCommand -Command "set" -Service "data" -Parameters @{
            Source = $Source
            Data = $Data
            Parameters = $Parameters
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to set data for source: $Source" -Level "ERROR" -Category "Data"
            return $false
        }
        
        Write-Log "Set data for source '$Source' successfully" -Level "SUCCESS" -Category "Data"
        return $true
    }
    catch {
        Write-Log "Error setting data for source '$Source': $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

function Remove-Data {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source,
        
        [hashtable]$Parameters = @{},
        
        [switch]$Backup
    )
    
    Write-Log "Removing data from source: $Source" -Level "INFO" -Category "Data"
    
    try {
        # Get source configuration
        $sourceConfig = $mcpConfig.Data.Sources[$Source]
        if ($null -eq $sourceConfig) {
            Write-Log "Data source '$Source' not found" -Level "ERROR" -Category "Data"
            return $false
        }
        
        # Backup data if requested
        if ($Backup) {
            $backupResult = Backup-Data -Source $Source
            if (-not $backupResult) {
                Write-Log "Failed to backup data for source: $Source" -Level "ERROR" -Category "Data"
                return $false
            }
        }
        
        # Execute remove command
        $result = Invoke-MCPCommand -Command "remove" -Service "data" -Parameters @{
            Source = $Source
            Parameters = $Parameters
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to remove data from source: $Source" -Level "ERROR" -Category "Data"
            return $false
        }
        
        Write-Log "Removed data from source '$Source' successfully" -Level "SUCCESS" -Category "Data"
        return $true
    }
    catch {
        Write-Log "Error removing data from source '$Source': $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

function Backup-Data {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source
    )
    
    Write-Log "Backing up data from source: $Source" -Level "INFO" -Category "Data"
    
    try {
        # Get source configuration
        $sourceConfig = $mcpConfig.Data.Sources[$Source]
        if ($null -eq $sourceConfig) {
            Write-Log "Data source '$Source' not found" -Level "ERROR" -Category "Data"
            return $false
        }
        
        # Execute backup command
        $result = Invoke-MCPCommand -Command "backup" -Service "data" -Parameters @{
            Source = $Source
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to backup data from source: $Source" -Level "ERROR" -Category "Data"
            return $false
        }
        
        Write-Log "Backed up data from source '$Source' successfully" -Level "SUCCESS" -Category "Data"
        return $true
    }
    catch {
        Write-Log "Error backing up data from source '$Source': $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

function Restore-Data {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source,
        
        [string]$BackupId
    )
    
    Write-Log "Restoring data for source: $Source" -Level "INFO" -Category "Data"
    
    try {
        # Get source configuration
        $sourceConfig = $mcpConfig.Data.Sources[$Source]
        if ($null -eq $sourceConfig) {
            Write-Log "Data source '$Source' not found" -Level "ERROR" -Category "Data"
            return $false
        }
        
        # Execute restore command
        $result = Invoke-MCPCommand -Command "restore" -Service "data" -Parameters @{
            Source = $Source
            BackupId = $BackupId
        }
        
        if ($null -eq $result) {
            Write-Log "Failed to restore data for source: $Source" -Level "ERROR" -Category "Data"
            return $false
        }
        
        Write-Log "Restored data for source '$Source' successfully" -Level "SUCCESS" -Category "Data"
        return $true
    }
    catch {
        Write-Log "Error restoring data for source '$Source': $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

function Test-DataValidation {
    param (
        [Parameter(Mandatory=$true)]
        [hashtable]$Data,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Rules
    )
    
    Write-Log "Validating data..." -Level "INFO" -Category "Data"
    
    try {
        foreach ($field in $Rules.Keys) {
            $rule = $Rules[$field]
            $value = $Data[$field]
            
            if ($null -eq $value) {
                Write-Log "Field '$field' is required" -Level "ERROR" -Category "Data"
                return $false
            }
            
            if ($rule -match "^[^/].*[^/]$") {
                # Regular expression validation
                if ($value -notmatch $rule) {
                    Write-Log "Field '$field' does not match pattern: $rule" -Level "ERROR" -Category "Data"
                    return $false
                }
            }
            else {
                # Enum validation
                $validValues = $rule -split ","
                if ($validValues -notcontains $value) {
                    Write-Log "Field '$field' must be one of: $($validValues -join ", ")" -Level "ERROR" -Category "Data"
                    return $false
                }
            }
        }
        
        Write-Log "Data validation successful" -Level "SUCCESS" -Category "Data"
        return $true
    }
    catch {
        Write-Log "Error validating data: $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Get-Data",
    "Set-Data",
    "Remove-Data",
    "Backup-Data",
    "Restore-Data",
    "Test-DataValidation"
) 