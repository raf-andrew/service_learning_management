# Feature Test Runner Script
# This script runs all feature tests and tracks their execution

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")
. (Join-Path $scriptPath "TestReporter.ps1")

# Initialize directories
$testTrackingDir = Join-Path (Join-Path $scriptPath "..") ".test/tracking"
$testResultsDir = Join-Path (Join-Path $scriptPath "..") ".test/results"
$testLogsDir = Join-Path (Join-Path $scriptPath "..") ".test/logs"

# Create directories if they don't exist
foreach ($dir in @($testTrackingDir, $testResultsDir, $testLogsDir)) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# Convert PSCustomObject to Hashtable
function ConvertTo-Hashtable {
    param (
        [Parameter(Mandatory = $true, ValueFromPipeline = $true)]
        [PSCustomObject]$Object
    )
    
    process {
        $hashtable = @{}
        
        foreach ($property in $Object.PSObject.Properties) {
            if ($property.Value -is [PSCustomObject]) {
                $hashtable[$property.Name] = ConvertTo-Hashtable -Object $property.Value
            } elseif ($property.Value -is [Array]) {
                $hashtable[$property.Name] = $property.Value | ForEach-Object {
                    if ($_ -is [PSCustomObject]) {
                        ConvertTo-Hashtable -Object $_
                    } else {
                        $_
                    }
                }
            } else {
                $hashtable[$property.Name] = $property.Value
            }
        }
        
        return $hashtable
    }
}

# Initialize test tracking data
$testTracking = @{
    start_time = Get-Date
    end_time = $null
    total_tests = 0
    passed_tests = 0
    failed_tests = 0
    skipped_tests = 0
    duration = $null
    status = "Running"
    test_suites = @()
}

# Load checklist data
$checklistPath = Join-Path (Join-Path $scriptPath "..") ".test/checklist-tracking.json"
$checklistData = Get-Content $checklistPath -Raw | ConvertFrom-Json | ConvertTo-Hashtable

# Save test tracking data
function Save-TestTracking {
    param (
        [hashtable]$TrackingData
    )
    
    $timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $trackingFile = Join-Path $testTrackingDir "test-tracking-$timestamp.json"
    
    $TrackingData | ConvertTo-Json -Depth 10 | Set-Content $trackingFile
    Write-TestLog "Test tracking data saved: $trackingFile" -Level "INFO"
}

# Update checklist item
function Update-ChecklistItem {
    param (
        [string]$ChecklistName,
        [string]$ItemDescription,
        [string]$Status,
        [hashtable]$CompletionProof
    )
    
    $checklist = $checklistData.checklists.$ChecklistName
    $item = $checklist.items | Where-Object { $_.description -eq $ItemDescription }
    
    if ($item) {
        $item.status = $Status
        $item.last_run = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $item.completion_proof = $CompletionProof
        
        # Update metadata
        $checklistData.metadata.completed_items = ($checklistData.checklists.PSObject.Properties | 
            ForEach-Object { $_.Value.items | Where-Object { $_.status -eq "completed" } } | 
            Measure-Object).Count
        
        $checklistData.metadata.pending_items = ($checklistData.checklists.PSObject.Properties | 
            ForEach-Object { $_.Value.items | Where-Object { $_.status -eq "pending" } } | 
            Measure-Object).Count
        
        $checklistData.metadata.failed_items = ($checklistData.checklists.PSObject.Properties | 
            ForEach-Object { $_.Value.items | Where-Object { $_.status -eq "failed" } } | 
            Measure-Object).Count
        
        # Save updated checklist
        $checklistData | ConvertTo-Json -Depth 10 | Set-Content $checklistPath
        Write-TestLog "Checklist item updated: $ChecklistName - $ItemDescription" -Level "INFO"
    }
}

# Run test suite
function Run-TestSuite {
    param (
        [string]$Name,
        [string]$TestName,
        [string]$ChecklistName,
        [string]$ItemDescription
    )
    
    Write-TestLog "Running test suite: $Name" -Level "INFO"
    Start-TestSuite -Name $Name
    
    $suite = @{
        Name = $Name
        TestName = $TestName
        StartTime = Get-Date
        EndTime = $null
        Status = "Running"
        Result = ""
        Error = $null
    }
    
    try {
        # Run the test using Docker
        $testOutput = docker exec service_learning_management-app-1 php artisan test:run "$ChecklistName" "tests/Feature/$TestName.php" 2>&1
        $suite.Result = $testOutput
        
        if ($LASTEXITCODE -eq 0) {
            $suite.Status = "Passed"
            $testTracking.passed_tests++
            Update-ChecklistItem -ChecklistName $ChecklistName -ItemDescription $ItemDescription -Status "completed" -CompletionProof @{
                result = "passed"
                test_report = $suite.Result
            }
        } else {
            $suite.Status = "Failed"
            $testTracking.failed_tests++
            $suite.Error = "Test failed with exit code $LASTEXITCODE"
            Update-ChecklistItem -ChecklistName $ChecklistName -ItemDescription $ItemDescription -Status "failed" -CompletionProof @{
                result = "failed"
                error = $suite.Error
                test_report = $suite.Result
            }
        }
    } catch {
        $suite.Status = "Failed"
        $testTracking.failed_tests++
        $suite.Error = $_.Exception.Message
        Update-ChecklistItem -ChecklistName $ChecklistName -ItemDescription $ItemDescription -Status "failed" -CompletionProof @{
            result = "failed"
            error = $suite.Error
            test_report = $suite.Result
        }
    }
    
    $suite.EndTime = Get-Date
    $testTracking.test_suites += $suite
    $testTracking.total_tests++
    
    End-TestSuite -Name $Name -Status $suite.Status
    return $suite
}

# Main execution
try {
    Write-TestLog "Starting feature test execution" -Level "INFO"
    
    # Define test suites
    $testSuites = @(
        @{
            Name = "Authentication"
            TestName = "DeveloperCredentialTest"
            ChecklistName = "Authentication Checklist"
            ItemDescription = "Developer Credential Test"
        },
        @{
            Name = "API"
            TestName = "ApiTest"
            ChecklistName = "API Functionality Checklist"
            ItemDescription = "API Test"
        },
        @{
            Name = "File Upload"
            TestName = "FileUploadTest"
            ChecklistName = "File Operations Checklist"
            ItemDescription = "File Upload Test"
        },
        @{
            Name = "Mail"
            TestName = "MailTest"
            ChecklistName = "Notifications Checklist"
            ItemDescription = "Mail Test"
        },
        @{
            Name = "Queue"
            TestName = "QueueTest"
            ChecklistName = "Notifications Checklist"
            ItemDescription = "Queue Test"
        },
        @{
            Name = "Rate Limit"
            TestName = "RateLimitTest"
            ChecklistName = "API Functionality Checklist"
            ItemDescription = "Rate Limit Test"
        },
        @{
            Name = "Concurrent Requests"
            TestName = "ConcurrentRequestTest"
            ChecklistName = "API Functionality Checklist"
            ItemDescription = "Concurrent Request Test"
        },
        @{
            Name = "Smoke Test"
            TestName = "SmokeTest"
            ChecklistName = "System Health Checklist"
            ItemDescription = "Smoke Test"
        },
        @{
            Name = "Test Reporter"
            TestName = "TestReporterTest"
            ChecklistName = "Developer Tools Checklist"
            ItemDescription = "Test Reporter Test"
        }
    )
    
    # Run test suites
    foreach ($suite in $testSuites) {
        Run-TestSuite @suite
    }
    
    # Update test tracking
    $testTracking.end_time = Get-Date
    $testTracking.duration = $testTracking.end_time - $testTracking.start_time
    $testTracking.status = if ($testTracking.failed_tests -eq 0) { "Passed" } else { "Failed" }
    
    # Save test tracking data
    Save-TestTracking -TrackingData $testTracking
    
    # Generate test report
    $reports = Write-TestReport -TestResults $testTracking -ChecklistData $checklistData
    
    Write-TestLog "Feature test execution completed" -Level "INFO"
    Write-TestLog "HTML Report: $($reports.html_report)" -Level "INFO"
    Write-TestLog "JSON Report: $($reports.json_report)" -Level "INFO"
    Write-TestLog "Markdown Report: $($reports.markdown_report)" -Level "INFO"
    
    exit 0
} catch {
    Write-TestLog "Error during feature test execution: $_" -Level "ERROR"
    exit 1
} 