# Core Logging Utility Functions
$ErrorActionPreference = "Stop"

# Initialize logging
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$logPath = Join-Path -Path (Join-Path -Path $scriptPath -ChildPath "..") -ChildPath "logs"
if (-not (Test-Path $logPath)) {
    New-Item -ItemType Directory -Path $logPath -Force | Out-Null
}

function Initialize-Logging {
    param (
        [Parameter(Mandatory=$false)]
        [string]$LogLevel = "INFO"
    )
    
    try {
        # Set log level
        $env:LOG_LEVEL = $LogLevel
        
        # Create log file
        $logFile = Join-Path -Path $logPath -ChildPath "mcp_$(Get-Date -Format 'yyyyMMdd').log"
        if (-not (Test-Path $logFile)) {
            New-Item -ItemType File -Path $logFile -Force | Out-Null
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to initialize logging: $_"
        return $false
    }
}

function Write-Log {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Message,
        
        [Parameter(Mandatory=$false)]
        [ValidateSet("DEBUG", "INFO", "WARNING", "ERROR")]
        [string]$Level = "INFO",
        
        [Parameter(Mandatory=$false)]
        [string]$Category = "General"
    )
    
    try {
        # Check log level
        $logLevels = @{
            "DEBUG" = 0
            "INFO" = 1
            "WARNING" = 2
            "ERROR" = 3
        }
        
        $currentLevel = $env:LOG_LEVEL
        if ($logLevels[$Level] -lt $logLevels[$currentLevel]) {
            return
        }
        
        # Format log message
        $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $logMessage = "[$timestamp] [$Level] [$Category] $Message"
        
        # Write to log file
        $logFile = Join-Path -Path $logPath -ChildPath "mcp_$(Get-Date -Format 'yyyyMMdd').log"
        Add-Content -Path $logFile -Value $logMessage
        
        # Write to console if error
        if ($Level -eq "ERROR") {
            Write-Host $logMessage -ForegroundColor Red
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to write log: $_"
        return $false
    }
}

function Get-Logs {
    param (
        [Parameter(Mandatory=$false)]
        [int]$Count = 100,
        
        [Parameter(Mandatory=$false)]
        [string]$Level,
        
        [Parameter(Mandatory=$false)]
        [string]$Category
    )
    
    try {
        # Get log file
        $logFile = Join-Path -Path $logPath -ChildPath "mcp_$(Get-Date -Format 'yyyyMMdd').log"
        if (-not (Test-Path $logFile)) {
            return @()
        }
        
        # Read logs
        $logs = Get-Content -Path $logFile -Tail $Count
        
        # Filter by level and category if specified
        if ($Level) {
            $logs = $logs | Where-Object { $_ -match "\[$Level\]" }
        }
        if ($Category) {
            $logs = $logs | Where-Object { $_ -match "\[$Category\]" }
        }
        
        return $logs
    }
    catch {
        Write-Error "Failed to get logs: $_"
        return @()
    }
}

function Rotate-Logs {
    param (
        [Parameter(Mandatory=$false)]
        [int]$MaxAge = 30
    )
    
    try {
        # Get all log files
        $logFiles = Get-ChildItem -Path $logPath -Filter "mcp_*.log"
        
        # Remove old logs
        $cutoffDate = (Get-Date).AddDays(-$MaxAge)
        foreach ($file in $logFiles) {
            if ($file.LastWriteTime -lt $cutoffDate) {
                Remove-Item -Path $file.FullName -Force
            }
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to rotate logs: $_"
        return $false
    }
}

# Export functions
# Export-ModuleMember -Function @(
#     "Initialize-Logging",
#     "Write-Log",
#     "Get-Logs",
#     "Rotate-Logs"
# ) 