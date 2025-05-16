# Logger Utility
$ErrorActionPreference = "Stop"

# Log levels and their colors
$logLevels = @{
    DEBUG = "Gray"
    INFO = "White"
    WARNING = "Yellow"
    ERROR = "Red"
    SUCCESS = "Green"
}

# Get log file path
function Get-LogFilePath {
    param (
        [string]$Category = "General"
    )

    $logDir = Join-Path (Join-Path $PSScriptRoot ".." "logs") $Category
    if (-not (Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }

    return Join-Path $logDir "health-$(Get-Date -Format 'yyyyMMdd').log"
}

# Write log entry
function Write-Log {
    param (
        [string]$Message,
        [string]$Level = "INFO",
        [string]$Category = "General"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] [$Category] $Message"
    
    # Write to console with color
    $color = $logLevels[$Level]
    if (-not $color) { $color = "White" }
    Write-Host $logMessage -ForegroundColor $color

    # Write to log file
    $logFile = Get-LogFilePath -Category $Category
    Add-Content -Path $logFile -Value $logMessage
}

# Get log entries
function Get-LogEntries {
    param (
        [string]$Category = "General",
        [int]$Lines = 100,
        [string]$Level,
        [string]$SearchString
    )

    $logFile = Get-LogFilePath -Category $Category
    if (-not (Test-Path $logFile)) {
        return @()
    }

    $entries = Get-Content -Path $logFile -Tail $Lines

    if ($Level) {
        $entries = $entries | Where-Object { $_ -match "\[$Level\]" }
    }

    if ($SearchString) {
        $entries = $entries | Where-Object { $_ -match $SearchString }
    }

    return $entries
}

# Clear old logs
function Clear-OldLogs {
    param (
        [int]$DaysToKeep = 30
    )

    $logDir = Join-Path $PSScriptRoot ".." "logs"
    if (-not (Test-Path $logDir)) {
        return
    }

    $cutoffDate = (Get-Date).AddDays(-$DaysToKeep)
    Get-ChildItem -Path $logDir -Recurse -File | Where-Object {
        $_.LastWriteTime -lt $cutoffDate
    } | Remove-Item -Force
}

# Export functions
Export-ModuleMember -Function Write-Log, Get-LogEntries, Clear-OldLogs 