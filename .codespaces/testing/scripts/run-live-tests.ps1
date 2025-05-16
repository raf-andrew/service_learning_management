# Live feature test runner script

# Stop on error
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "setup-test-environment.ps1")

# Initialize logging
Start-TestSuite -SuiteName "Live Feature Tests"

# Test results
$testResults = @{
    total = 0
    passed = 0
    failed = @()
}

# Helper function to run a test
function Test-Case {
    param(
        [string]$Name,
        [scriptblock]$Test,
        [string]$Message
    )
    
    Start-Test -TestName $Name -TestSuite "Live Feature Tests"
    $testResults.total++
    
    try {
        Write-TestDebug -Message "Executing test" -TestName $Name -TestSuite "Live Feature Tests" -Context @{
            Message = $Message
        }
        
        $result = & $Test
        
        if ($result) {
            $testResults.passed++
            End-Test -TestName $Name -TestSuite "Live Feature Tests" -Success $true -Message $Message
        }
        else {
            $testResults.failed += @{
                name = $Name
                message = $Message
            }
            End-Test -TestName $Name -TestSuite "Live Feature Tests" -Success $false -Message $Message
        }
    }
    catch {
        $testResults.failed += @{
            name = $Name
            message = $Message
            error = $_.Exception.Message
        }
        End-Test -TestName $Name -TestSuite "Live Feature Tests" -Success $false -Message $Message -Error $_.Exception.Message
    }
}

# Verify test environment
$envSetup = & (Join-Path $PSScriptRoot "setup-test-environment.ps1")
if ($envSetup.failed.Count -gt 0) {
    Write-Error "Test environment setup failed. Please fix the issues before running tests."
    exit 1
}

# Run Codespace tests
Test-Case -Name "Codespace Creation" -Message "Should create a new codespace" -Test {
    $response = php artisan test tests/Feature/CodespaceTest.php --filter=test_can_create_codespace
    return $LASTEXITCODE -eq 0
}

Test-Case -Name "Codespace Listing" -Message "Should list all codespaces" -Test {
    $response = php artisan test tests/Feature/CodespaceTest.php --filter=test_can_list_codespaces
    return $LASTEXITCODE -eq 0
}

Test-Case -Name "Codespace Deletion" -Message "Should delete a codespace" -Test {
    $response = php artisan test tests/Feature/CodespaceTest.php --filter=test_can_delete_codespace
    return $LASTEXITCODE -eq 0
}

# Run File Upload tests
Test-Case -Name "File Upload" -Message "Should upload a file" -Test {
    $response = php artisan test tests/Feature/FileUploadTest.php
    return $LASTEXITCODE -eq 0
}

# Run API tests
Test-Case -Name "API Authentication" -Message "Should authenticate API requests" -Test {
    $response = php artisan test tests/Feature/ApiTest.php --filter=test_api_authentication
    return $LASTEXITCODE -eq 0
}

Test-Case -Name "API Rate Limiting" -Message "Should enforce rate limits" -Test {
    $response = php artisan test tests/Feature/RateLimitTest.php
    return $LASTEXITCODE -eq 0
}

# Run Mail tests
Test-Case -Name "Mail Sending" -Message "Should send emails" -Test {
    $response = php artisan test tests/Feature/MailTest.php
    return $LASTEXITCODE -eq 0
}

# Run Queue tests
Test-Case -Name "Queue Processing" -Message "Should process queued jobs" -Test {
    $response = php artisan test tests/Feature/QueueTest.php
    return $LASTEXITCODE -eq 0
}

# Run Smoke tests
Test-Case -Name "Smoke Test" -Message "Should pass basic functionality checks" -Test {
    $response = php artisan test tests/Feature/SmokeTest.php
    return $LASTEXITCODE -eq 0
}

# End test suite and print summary
End-TestSuite -SuiteName "Live Feature Tests" -Results $testResults

# Return test results
return $testResults 