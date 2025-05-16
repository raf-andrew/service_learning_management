# State management script for Codespaces setup
$ErrorActionPreference = "Stop"

# Color definitions for output
$ColorRed = [System.ConsoleColor]::Red
$ColorGreen = [System.ConsoleColor]::Green
$ColorYellow = [System.ConsoleColor]::Yellow

# Configuration
$STATE_DIR = ".codespaces/testing/state"
$STATE_FILE = Join-Path $STATE_DIR "setup-state.json"
$LOG_DIR = ".codespaces/testing/logs"
$LOG_FILE = Join-Path $LOG_DIR "setup.log"

# Logging function
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "ERROR" { $ColorRed }
        "WARN" { $ColorYellow }
        "INFO" { $ColorGreen }
        default { [System.ConsoleColor]::White }
    }
    
    $logMessage = "[$timestamp] [$Level] $Message"
    Write-Host $logMessage -ForegroundColor $color
    
    # Ensure log directory exists
    if (-not (Test-Path $LOG_DIR)) {
        New-Item -ItemType Directory -Path $LOG_DIR -Force | Out-Null
    }
    
    # Append to log file
    Add-Content -Path $LOG_FILE -Value $logMessage
}

# Initialize state
function Initialize-State {
    Write-Log "Initializing state management..."
    
    if (-not (Test-Path $STATE_DIR)) {
        try {
            New-Item -ItemType Directory -Path $STATE_DIR -Force | Out-Null
            Write-Log "Created state directory" "INFO"
        }
        catch {
            Write-Log "Failed to create state directory" "ERROR"
            return $false
        }
    }
    
    if (-not (Test-Path $STATE_FILE)) {
        try {
            $initialState = @{
                lastSuccessfulStep = $null
                completedSteps = @()
                failedSteps = @()
                startTime = (Get-Date).ToString("o")
                lastUpdateTime = (Get-Date).ToString("o")
                environment = @{
                    os = [System.Environment]::OSVersion.ToString()
                    powershell = $PSVersionTable.PSVersion.ToString()
                    tools = @{}
                }
            }
            
            $initialState | ConvertTo-Json -Depth 10 | Set-Content -Path $STATE_FILE
            Write-Log "Created initial state file" "INFO"
        }
        catch {
            Write-Log "Failed to create initial state file" "ERROR"
            return $false
        }
    }
    
    return $true
}

# Update state
function Update-State {
    param(
        [string]$Step,
        [string]$Status,
        [string]$Message,
        [hashtable]$AdditionalData = @{}
    )
    
    Write-Log "Updating state for step: $Step ($Status)"
    
    try {
        $state = Get-Content $STATE_FILE | ConvertFrom-Json -AsHashtable
        
        # Update step status
        if ($Status -eq "success") {
            $state.completedSteps += $Step
            $state.lastSuccessfulStep = $Step
        }
        elseif ($Status -eq "failed") {
            $state.failedSteps += @{
                step = $Step
                message = $Message
                timestamp = (Get-Date).ToString("o")
            }
        }
        
        # Update timestamps
        $state.lastUpdateTime = (Get-Date).ToString("o")
        
        # Add additional data if provided
        foreach ($key in $AdditionalData.Keys) {
            $state[$key] = $AdditionalData[$key]
        }
        
        # Save updated state
        $state | ConvertTo-Json -Depth 10 | Set-Content -Path $STATE_FILE
        Write-Log "State updated successfully" "INFO"
        return $true
    }
    catch {
        Write-Log "Failed to update state: $_" "ERROR"
        return $false
    }
}

# Get last successful step
function Get-LastSuccessfulStep {
    try {
        $state = Get-Content $STATE_FILE | ConvertFrom-Json -AsHashtable
        return $state.lastSuccessfulStep
    }
    catch {
        Write-Log "Failed to get last successful step: $_" "ERROR"
        return $null
    }
}

# Get failed steps
function Get-FailedSteps {
    try {
        $state = Get-Content $STATE_FILE | ConvertFrom-Json -AsHashtable
        return $state.failedSteps
    }
    catch {
        Write-Log "Failed to get failed steps: $_" "ERROR"
        return @()
    }
}

# Get setup progress
function Get-SetupProgress {
    try {
        $state = Get-Content $STATE_FILE | ConvertFrom-Json -AsHashtable
        $totalSteps = ($state.completedSteps.Count + $state.failedSteps.Count)
        $completedSteps = $state.completedSteps.Count
        
        if ($totalSteps -eq 0) {
            return 0
        }
        
        return [math]::Round(($completedSteps / $totalSteps) * 100, 2)
    }
    catch {
        Write-Log "Failed to get setup progress: $_" "ERROR"
        return 0
    }
}

# Generate setup report
function Get-SetupReport {
    try {
        $state = Get-Content $STATE_FILE | ConvertFrom-Json -AsHashtable
        $report = @{
            startTime = $state.startTime
            lastUpdateTime = $state.lastUpdateTime
            progress = Get-SetupProgress
            completedSteps = $state.completedSteps
            failedSteps = $state.failedSteps
            environment = $state.environment
        }
        
        return $report | ConvertTo-Json -Depth 10
    }
    catch {
        Write-Log "Failed to generate setup report: $_" "ERROR"
        return $null
    }
}

# Execute if run directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    Initialize-State
    Get-SetupReport
} 