# Health Monitoring System Manager
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "status", "check", "logs", "switch-env")]
    [string]$Command,
    
    [string]$Environment = "local",
    [int]$Interval = 300,
    [switch]$AutoHeal,
    [switch]$Background,
    [string]$Category,
    [int]$Lines = 100,
    [string]$Level,
    [string]$SearchString,
    [switch]$Follow
)

# Start monitoring
function Start-Monitoring {
    param (
        [string]$Environment,
        [int]$Interval,
        [switch]$AutoHeal,
        [switch]$Background
    )
    
    Write-Log "Starting monitoring..." -Level "INFO" -Category "Monitor"
    
    try {
        # Run the monitoring script
        $result = . (Join-Path $scriptPath "run.ps1") -Environment $Environment -Interval $Interval -AutoHeal:$AutoHeal -Background:$Background
        
        if ($result) {
            Write-Log "Monitoring started successfully" -Level "SUCCESS" -Category "Monitor"
            return $true
        } else {
            Write-Log "Failed to start monitoring" -Level "ERROR" -Category "Monitor"
            return $false
        }
    }
    catch {
        Write-Log "Error starting monitoring: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Stop monitoring
function Stop-Monitoring {
    Write-Log "Stopping monitoring..." -Level "INFO" -Category "Monitor"
    
    try {
        # Run the stop script
        $result = . (Join-Path $scriptPath "stop.ps1")
        
        if ($result) {
            Write-Log "Monitoring stopped successfully" -Level "SUCCESS" -Category "Monitor"
            return $true
        } else {
            Write-Log "Failed to stop monitoring" -Level "ERROR" -Category "Monitor"
            return $false
        }
    }
    catch {
        Write-Log "Error stopping monitoring: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Check status
function Get-Status {
    param (
        [switch]$Detailed
    )
    
    Write-Log "Checking status..." -Level "INFO" -Category "Monitor"
    
    try {
        # Run the status script
        $result = . (Join-Path $scriptPath "status.ps1") -Detailed:$Detailed
        
        if ($result) {
            Write-Log "Status retrieved successfully" -Level "SUCCESS" -Category "Monitor"
            return $true
        } else {
            Write-Log "Failed to get status" -Level "ERROR" -Category "Monitor"
            return $false
        }
    }
    catch {
        Write-Log "Error checking status: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Run health checks
function Start-HealthChecks {
    param (
        [switch]$AutoHeal
    )
    
    Write-Log "Running health checks..." -Level "INFO" -Category "Monitor"
    
    try {
        # Run the test script
        $result = . (Join-Path $scriptPath "tests" "run-tests.ps1") -AutoHeal:$AutoHeal
        
        if ($result) {
            Write-Log "Health checks completed successfully" -Level "SUCCESS" -Category "Monitor"
            return $true
        } else {
            Write-Log "Health checks failed" -Level "ERROR" -Category "Monitor"
            return $false
        }
    }
    catch {
        Write-Log "Error running health checks: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# View logs
function Get-Logs {
    param (
        [string]$Category,
        [int]$Lines,
        [string]$Level,
        [string]$SearchString,
        [switch]$Follow
    )
    
    Write-Log "Viewing logs..." -Level "INFO" -Category "Monitor"
    
    try {
        $logDir = Join-Path $scriptPath "logs"
        if (-not (Test-Path $logDir)) {
            Write-Log "Log directory not found" -Level "ERROR" -Category "Monitor"
            return $false
        }
        
        # Build log file path
        $logFile = if ($Category) {
            Join-Path $logDir "$Category.log"
        } else {
            Join-Path $logDir "general.log"
        }
        
        if (-not (Test-Path $logFile)) {
            Write-Log "Log file not found: $logFile" -Level "ERROR" -Category "Monitor"
            return $false
        }
        
        # Build Get-Content parameters
        $params = @{
            Path = $logFile
            Tail = $Lines
        }
        
        if ($Follow) {
            $params.Add("Wait", $true)
        }
        
        # Get logs
        $logs = Get-Content @params
        
        # Filter logs
        if ($Level) {
            $logs = $logs | Where-Object { $_ -match "\[$Level\]" }
        }
        
        if ($SearchString) {
            $logs = $logs | Where-Object { $_ -match $SearchString }
        }
        
        # Display logs
        $logs | ForEach-Object {
            Write-Host $_
        }
        
        Write-Log "Logs retrieved successfully" -Level "SUCCESS" -Category "Monitor"
        return $true
    }
    catch {
        Write-Log "Error viewing logs: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Switch environment
function Switch-Environment {
    param (
        [string]$Environment
    )
    
    Write-Log "Switching environment..." -Level "INFO" -Category "Monitor"
    
    try {
        # Run the environment script
        $result = . (Join-Path $scriptPath "utils" "manage-env.ps1") -Environment $Environment -Verify
        
        if ($result) {
            Write-Log "Environment switched successfully" -Level "SUCCESS" -Category "Monitor"
            return $true
        } else {
            Write-Log "Failed to switch environment" -Level "ERROR" -Category "Monitor"
            return $false
        }
    }
    catch {
        Write-Log "Error switching environment: $_" -Level "ERROR" -Category "Monitor"
        return $false
    }
}

# Main execution
try {
    Write-Host "Managing Health Monitoring System..."
    Write-Host "================================="
    
    # Execute command
    $result = switch ($Command) {
        "start" {
            Start-Monitoring -Environment $Environment -Interval $Interval -AutoHeal:$AutoHeal -Background:$Background
        }
        "stop" {
            Stop-Monitoring
        }
        "status" {
            Get-Status -Detailed:$Detailed
        }
        "check" {
            Start-HealthChecks -AutoHeal:$AutoHeal
        }
        "logs" {
            Get-Logs -Category $Category -Lines $Lines -Level $Level -SearchString $SearchString -Follow:$Follow
        }
        "switch-env" {
            Switch-Environment -Environment $Environment
        }
    }
    
    if (-not $result) {
        Write-Host "Command failed" -ForegroundColor "Red"
        exit 1
    }
    
    Write-Host "`nCommand completed successfully!" -ForegroundColor "Green"
}
catch {
    Write-Log "Error managing health monitoring system: $_" -Level "ERROR" -Category "Monitor"
    exit 1
} 