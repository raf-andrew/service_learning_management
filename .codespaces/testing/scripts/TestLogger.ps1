# Test Logger Module
$ErrorActionPreference = "Stop"

# Color codes for different log levels
$LogColors = @{
    DEBUG = "Gray"
    INFO = "Cyan"
    SUCCESS = "Green"
    WARNING = "Yellow"
    ERROR = "Red"
    CRITICAL = "Magenta"
}

# Log file paths
$LogDir = Join-Path (Join-Path $PSScriptRoot "..") "logs"
$LogFile = Join-Path $LogDir "test-execution-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"

# Ensure log directory exists
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

function Write-TestLog {
    param(
        [Parameter(Mandatory=$true)]
        [string]$Message,
        
        [Parameter(Mandatory=$false)]
        [ValidateSet("DEBUG", "INFO", "SUCCESS", "WARNING", "ERROR", "CRITICAL")]
        [string]$Level = "INFO",
        
        [Parameter(Mandatory=$false)]
        [string]$TestName,
        
        [Parameter(Mandatory=$false)]
        [string]$TestSuite,
        
        [Parameter(Mandatory=$false)]
        [hashtable]$Context
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss.fff"
    $color = $LogColors[$Level]
    
    # Build log entry
    $logEntry = @{
        Timestamp = $timestamp
        Level = $Level
        Message = $Message
        TestName = $TestName
        TestSuite = $TestSuite
        Context = $Context
    }
    
    # Format console output
    $consoleOutput = "[$timestamp] [$Level]"
    if ($TestSuite) { $consoleOutput += " [$TestSuite]" }
    if ($TestName) { $consoleOutput += " [$TestName]" }
    $consoleOutput += " $Message"
    
    if ($Context) {
        $contextStr = $Context | ConvertTo-Json -Compress
        $consoleOutput += " Context: $contextStr"
    }
    
    # Write to console with color
    Write-Host $consoleOutput -ForegroundColor $color
    
    # Ensure log directory exists before writing
    if (-not (Test-Path $LogDir)) {
        New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
    }
    
    # Write to log file with error handling
    try {
        $logEntry | ConvertTo-Json | Add-Content -Path $LogFile -ErrorAction Stop
    }
    catch {
        Write-Host "[$timestamp] [ERROR] Failed to write to log file: $($_.Exception.Message)" -ForegroundColor $LogColors["ERROR"]
    }
}

# Initialize test suite
function Start-TestSuite {
    param(
        [string]$SuiteName
    )
    
    Write-Host "`n=== Starting Test Suite: $SuiteName ===" -ForegroundColor Cyan
    Write-Host "Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')`n"
}

# End test suite and print summary
function End-TestSuite {
    param(
        [string]$SuiteName,
        [hashtable]$Results
    )
    
    Write-Host "`n=== Test Suite Summary: $SuiteName ===" -ForegroundColor Cyan
    Write-Host "Total Tests: $($Results.total)"
    Write-Host "Passed: $($Results.passed)" -ForegroundColor Green
    Write-Host "Failed: $($Results.failed.Count)" -ForegroundColor Red
    
    if ($Results.failed.Count -gt 0) {
        Write-Host "`nFailed Tests:" -ForegroundColor Red
        foreach ($failure in $Results.failed) {
            Write-Host "- $($failure.name): $($failure.message)"
            if ($failure.error) {
                Write-Host "  Error: $($failure.error)" -ForegroundColor Red
            }
        }
    }
    
    Write-Host "`nTimestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    Write-Host "=== End Test Suite: $SuiteName ===`n"
}

# Start a test
function Start-Test {
    param(
        [string]$TestName,
        [string]$TestSuite
    )
    
    Write-Host "Running test: $TestName" -ForegroundColor Yellow
}

# End a test
function End-Test {
    param(
        [string]$TestName,
        [string]$TestSuite,
        [bool]$Success,
        [string]$Message,
        [string]$Error
    )
    
    if ($Success) {
        Write-Host "`u{2713} $($TestName): $($Message)" -ForegroundColor Green
    }
    else {
        Write-Host "`u{2717} $($TestName): $($Message)" -ForegroundColor Red
        if ($Error) {
            Write-Host "  Error: $Error" -ForegroundColor Red
        }
    }
}

function Write-TestError {
    param(
        [Parameter(Mandatory=$true)]
        [string]$Message,
        
        [Parameter(Mandatory=$false)]
        [string]$TestName,
        
        [Parameter(Mandatory=$false)]
        [string]$TestSuite,
        
        [Parameter(Mandatory=$false)]
        [hashtable]$Context
    )
    
    Write-TestLog -Message $Message -Level "ERROR" -TestName $TestName -TestSuite $TestSuite -Context $Context
}

function Write-TestWarning {
    param(
        [Parameter(Mandatory=$true)]
        [string]$Message,
        
        [Parameter(Mandatory=$false)]
        [string]$TestName,
        
        [Parameter(Mandatory=$false)]
        [string]$TestSuite,
        
        [Parameter(Mandatory=$false)]
        [hashtable]$Context
    )
    
    Write-TestLog -Message $Message -Level "WARNING" -TestName $TestName -TestSuite $TestSuite -Context $Context
}

# Write debug information
function Write-TestDebug {
    param(
        [string]$Message,
        [string]$TestName,
        [string]$TestSuite,
        [hashtable]$Context
    )
    
    Write-Host "[DEBUG] $Message" -ForegroundColor Gray
    if ($Context) {
        foreach ($key in $Context.Keys) {
            Write-Host "  $key`: $($Context[$key])" -ForegroundColor Gray
        }
    }
}

# Export functions
# Export-ModuleMember -Function Start-TestSuite, End-TestSuite, Start-Test, End-Test, Write-TestDebug

# Export functions
# Export-ModuleMember -Function @(
#     'Write-TestLog',
#     'Start-TestSuite',
#     'End-TestSuite',
#     'Start-Test',
#     'End-Test',
#     'Write-TestError',
#     'Write-TestWarning',
#     'Write-TestDebug'
# ) 