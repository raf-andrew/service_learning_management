# Tail Health Check Logs
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")

# Parse command line arguments
param (
    [string]$Category = "General",
    [int]$Lines = 100,
    [string]$Level,
    [string]$SearchString,
    [switch]$Follow
)

# Get log entries
$entries = Get-LogEntries -Category $Category -Lines $Lines -Level $Level -SearchString $SearchString

# Display entries
foreach ($entry in $entries) {
    $color = switch -Regex ($entry) {
        "\[ERROR\]" { "Red" }
        "\[WARNING\]" { "Yellow" }
        "\[SUCCESS\]" { "Green" }
        "\[DEBUG\]" { "Gray" }
        default { "White" }
    }
    Write-Host $entry -ForegroundColor $color
}

# Follow logs if requested
if ($Follow) {
    Write-Host "`nFollowing logs... (Press Ctrl+C to stop)"
    $logFile = Get-LogFilePath -Category $Category
    
    try {
        Get-Content -Path $logFile -Wait | ForEach-Object {
            $color = switch -Regex ($_) {
                "\[ERROR\]" { "Red" }
                "\[WARNING\]" { "Yellow" }
                "\[SUCCESS\]" { "Green" }
                "\[DEBUG\]" { "Gray" }
                default { "White" }
            }
            Write-Host $_ -ForegroundColor $color
        }
    }
    catch {
        Write-Host "`nStopped following logs"
    }
} 