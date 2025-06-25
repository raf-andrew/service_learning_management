# MCP Logger Utility
# Provides comprehensive logging functionality for the MCP system

param(
    [string]$LogPath = "modules/mcp/logs",
    [string]$LogLevel = "INFO"
)

# Log levels
$LogLevels = @{
    "DEBUG" = 0
    "INFO" = 1
    "WARN" = 2
    "ERROR" = 3
    "FATAL" = 4
}

function Initialize-Logger {
    param(
        [string]$LogPath = "modules/mcp/logs",
        [string]$LogLevel = "INFO"
    )
    
    # Create log directory if it doesn't exist
    if (-not (Test-Path $LogPath)) {
        New-Item -ItemType Directory -Path $LogPath -Force | Out-Null
    }
    
    # Create category subdirectories
    $categories = @("general", "services", "data", "config", "audit")
    foreach ($category in $categories) {
        $categoryPath = Join-Path $LogPath $category
        if (-not (Test-Path $categoryPath)) {
            New-Item -ItemType Directory -Path $categoryPath -Force | Out-Null
        }
    }
    
    $script:LogPath = $LogPath
    $script:LogLevel = $LogLevel
    
    Write-Host "Logger initialized: $LogPath (Level: $LogLevel)" -ForegroundColor Green
}

function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO",
        [string]$Category = "general",
        [hashtable]$Context = @{}
    )
    
    # Check log level
    if ($LogLevels[$Level] -lt $LogLevels[$script:LogLevel]) {
        return
    }
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logFile = Join-Path $script:LogPath "$category\$(Get-Date -Format 'yyyy-MM-dd').log"
    
    # Format context
    $contextStr = ""
    if ($Context.Count -gt 0) {
        $contextStr = " | " + ($Context.GetEnumerator() | ForEach-Object { "$($_.Key)=$($_.Value)" }) -join ", "
    }
    
    # Create log entry
    $logEntry = "[$timestamp] [$Level] $Message$contextStr"
    
    # Write to file
    Add-Content -Path $logFile -Value $logEntry
    
    # Write to console with color
    $color = switch ($Level) {
        "DEBUG" { "Gray" }
        "INFO" { "White" }
        "WARN" { "Yellow" }
        "ERROR" { "Red" }
        "FATAL" { "DarkRed" }
        default { "White" }
    }
    
    Write-Host $logEntry -ForegroundColor $color
}

function Get-Logs {
    param(
        [string]$Category = "general",
        [string]$StartDate = "",
        [string]$EndDate = "",
        [int]$Lines = 100,
        [switch]$Follow
    )
    
    $categoryPath = Join-Path $script:LogPath $Category
    
    if (-not (Test-Path $categoryPath)) {
        Write-Log "Log category not found: $Category" -Level "ERROR" -Category "Logger"
        return $null
    }
    
    $logFiles = Get-ChildItem -Path $categoryPath -Filter "*.log" | Sort-Object Name
    
    if ($StartDate -and $EndDate) {
        $start = [DateTime]::ParseExact($StartDate, "yyyy-MM-dd", $null)
        $end = [DateTime]::ParseExact($EndDate, "yyyy-MM-dd", $null)
        $logFiles = $logFiles | Where-Object { 
            $fileDate = [DateTime]::ParseExact($_.BaseName, "yyyy-MM-dd", $null)
            $fileDate -ge $start -and $fileDate -le $end
        }
    }
    
    $logs = @()
    foreach ($file in $logFiles) {
        $fileContent = Get-Content -Path $file.FullName -Tail $Lines
        $logs += $fileContent
    }
    
    if ($Follow) {
        $latestFile = $logFiles | Select-Object -Last 1
        if ($latestFile) {
            Get-Content -Path $latestFile.FullName -Wait -Tail 10
        }
    } else {
        return $logs
    }
}

function Search-Logs {
    param(
        [string]$Query,
        [string]$Category = "general",
        [string]$StartDate = "",
        [string]$EndDate = "",
        [string]$Level = ""
    )
    
    $logs = Get-Logs -Category $Category -StartDate $StartDate -EndDate $EndDate
    
    if (-not $logs) {
        return @()
    }
    
    $results = $logs | Where-Object { $_ -match $Query }
    
    if ($Level) {
        $results = $results | Where-Object { $_ -match "\[$Level\]" }
    }
    
    return $results
}

function Rotate-Logs {
    param(
        [string]$Category = "general",
        [int]$RetentionDays = 30
    )
    
    $categoryPath = Join-Path $script:LogPath $Category
    
    if (-not (Test-Path $categoryPath)) {
        Write-Log "Log category not found: $Category" -Level "ERROR" -Category "Logger"
        return @{ Success = $false; Error = "Category not found" }
    }
    
    try {
        $cutoffDate = (Get-Date).AddDays(-$RetentionDays)
        $oldFiles = Get-ChildItem -Path $categoryPath -Filter "*.log" | Where-Object {
            $_.LastWriteTime -lt $cutoffDate
        }
        
        $deletedCount = 0
        foreach ($file in $oldFiles) {
            Remove-Item -Path $file.FullName -Force
            $deletedCount++
        }
        
        Write-Log "Rotated $deletedCount log files for category: $Category" -Level "INFO" -Category "Logger"
        return @{ Success = $true; DeletedCount = $deletedCount }
        
    } catch {
        Write-Log "Log rotation failed: $_" -Level "ERROR" -Category "Logger"
        return @{ Success = $false; Error = $_ }
    }
}

function Get-LogStatistics {
    param([string]$Category = "general")
    
    $categoryPath = Join-Path $script:LogPath $Category
    
    if (-not (Test-Path $categoryPath)) {
        return $null
    }
    
    $stats = @{
        TotalFiles = 0
        TotalLines = 0
        LevelCounts = @{
            "DEBUG" = 0
            "INFO" = 0
            "WARN" = 0
            "ERROR" = 0
            "FATAL" = 0
        }
        OldestFile = $null
        NewestFile = $null
    }
    
    $logFiles = Get-ChildItem -Path $categoryPath -Filter "*.log"
    $stats.TotalFiles = $logFiles.Count
    
    foreach ($file in $logFiles) {
        $content = Get-Content -Path $file.FullName
        $stats.TotalLines += $content.Count
        
        foreach ($line in $content) {
            if ($line -match "\[(DEBUG|INFO|WARN|ERROR|FATAL)\]") {
                $level = $matches[1]
                $stats.LevelCounts[$level]++
            }
        }
        
        if (-not $stats.OldestFile -or $file.CreationTime -lt $stats.OldestFile.CreationTime) {
            $stats.OldestFile = $file
        }
        
        if (-not $stats.NewestFile -or $file.LastWriteTime -gt $stats.NewestFile.LastWriteTime) {
            $stats.NewestFile = $file
        }
    }
    
    return $stats
}

# Export functions
Export-ModuleMember -Function @(
    "Initialize-Logger",
    "Write-Log",
    "Get-Logs",
    "Search-Logs",
    "Rotate-Logs",
    "Get-LogStatistics"
) 