# Test Log Management Script
$ErrorActionPreference = "Stop"

# Directories
$logDir = ".codespaces/log"
$completeDir = ".codespaces/testing/.test/.complete"
$failuresDir = ".codespaces/testing/.test/.failures"
$checklistFile = ".codespaces/testing/.test/checklist-tracking.json"

# Create directories if they don't exist
foreach ($dir in @($completeDir, $failuresDir)) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Function to process test logs
function Process-TestLogs {
    param (
        [string]$LogFile
    )
    
    $logContent = Get-Content $LogFile
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    
    # Check for test completion
    if ($logContent -match "All tests passed successfully") {
        $testName = [System.IO.Path]::GetFileNameWithoutExtension($LogFile)
        $completeFile = Join-Path $completeDir "$testName-$timestamp.complete"
        
        # Create completion record
        $completion = @{
            test_name = $testName
            completed_at = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            log_file = $LogFile
            status = "completed"
        }
        
        # Save completion record
        $completion | ConvertTo-Json | Set-Content $completeFile
        
        # Update checklist
        Update-ChecklistItem -TestName $testName -Status "completed" -CompletionFile $completeFile
        
        # Move log to completed
        Move-Item $LogFile $completeFile -Force
    }
    # Check for failures
    elseif ($logContent -match "Failed|Error|Exception") {
        $failureFile = Join-Path $failuresDir "$([System.IO.Path]::GetFileNameWithoutExtension($LogFile))-$timestamp.failure"
        Move-Item $LogFile $failureFile -Force
        
        # Update checklist
        Update-ChecklistItem -TestName $testName -Status "failed" -FailureFile $failureFile
    }
}

# Function to update checklist
function Update-ChecklistItem {
    param (
        [string]$TestName,
        [string]$Status,
        [string]$CompletionFile,
        [string]$FailureFile
    )
    
    if (Test-Path $checklistFile) {
        $checklist = Get-Content $checklistFile | ConvertFrom-Json
        
        $item = $checklist.items | Where-Object { $_.test_name -eq $TestName }
        if ($item) {
            $item.status = $Status
            $item.last_updated = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            
            if ($CompletionFile) {
                $item.completion_file = $CompletionFile
            }
            if ($FailureFile) {
                $item.failure_file = $FailureFile
            }
            
            $checklist | ConvertTo-Json -Depth 10 | Set-Content $checklistFile
        }
    }
}

# Process all test logs
Get-ChildItem -Path $logDir -Filter "*.log" | ForEach-Object {
    Process-TestLogs $_.FullName
}

Write-Host "Test log processing complete" 