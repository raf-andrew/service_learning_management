# Test script for token management
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "manage-tokens.ps1")

# Test suite name
$TestSuite = "Token Management"

# Initialize test suite
Start-TestSuite -SuiteName $TestSuite

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
    
    Start-Test -TestName $Name -TestSuite $TestSuite
    $testResults.total++
    
    try {
        Write-TestDebug -Message "Executing test" -TestName $Name -TestSuite $TestSuite -Context @{
            Message = $Message
        }
        
        $result = & $Test
        
        if ($result) {
            $testResults.passed++
            End-Test -TestName $Name -TestSuite $TestSuite -Success $true -Message $Message
        }
        else {
            $testResults.failed += @{
                name = $Name
                message = $Message
            }
            End-Test -TestName $Name -TestSuite $TestSuite -Success $false -Message $Message
        }
    }
    catch {
        $testResults.failed += @{
            name = $Name
            message = $Message
            error = $_.Exception.Message
        }
        End-Test -TestName $Name -TestSuite $TestSuite -Success $false -Message $Message -Error $_.Exception.Message
    }
}

# Test directory initialization
Test-Case -Name "Directory Initialization" -Message "Token directories should be created" -Test {
    Write-TestDebug -Message "Testing directory initialization" -TestName "Directory Initialization" -TestSuite $TestSuite
    $tokenDir = Join-Path (Join-Path $PSScriptRoot "..") "tokens"
    if (-not (Test-Path $tokenDir)) {
        New-Item -ItemType Directory -Path $tokenDir -Force | Out-Null
    }
    return Test-Path $tokenDir
}

# Test encryption key initialization
Test-Case -Name "Encryption Key Initialization" -Message "Encryption key should be generated" -Test {
    Write-TestDebug -Message "Testing encryption key initialization" -TestName "Encryption Key Initialization" -TestSuite $TestSuite
    $tokenDir = Join-Path (Join-Path $PSScriptRoot "..") "tokens"
    $keyFile = Join-Path $tokenDir "encryption.key"
    if (-not (Test-Path $keyFile)) {
        $key = New-Object byte[] 32
        [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($key)
        [Convert]::ToBase64String($key) | Set-Content -Path $keyFile
    }
    return Test-Path $keyFile
}

# Test token encryption
Test-Case -Name "Token Encryption" -Message "Token should be encrypted successfully" -Test {
    Write-TestDebug -Message "Testing token encryption" -TestName "Token Encryption" -TestSuite $TestSuite
    $tokenDir = Join-Path (Join-Path $PSScriptRoot "..") "tokens"
    $keyFile = Join-Path $tokenDir "encryption.key"
    $key = [Convert]::FromBase64String((Get-Content -Path $keyFile))
    
    $testToken = "test_token_123"
    $secureString = ConvertTo-SecureString -String $testToken -AsPlainText -Force
    $encrypted = ConvertFrom-SecureString -SecureString $secureString -Key $key
    
    return -not [string]::IsNullOrEmpty($encrypted)
}

# Test token decryption
Test-Case -Name "Token Decryption" -Message "Token should be decrypted successfully" -Test {
    Write-TestDebug -Message "Testing token decryption" -TestName "Token Decryption" -TestSuite $TestSuite
    $tokenDir = Join-Path (Join-Path $PSScriptRoot "..") "tokens"
    $keyFile = Join-Path $tokenDir "encryption.key"
    $key = [Convert]::FromBase64String((Get-Content -Path $keyFile))
    
    $testToken = "test_token_123"
    $secureString = ConvertTo-SecureString -String $testToken -AsPlainText -Force
    $encrypted = ConvertFrom-SecureString -SecureString $secureString -Key $key
    
    $decrypted = ConvertTo-SecureString -String $encrypted -Key $key
    $decryptedToken = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($decrypted)
    )
    
    return $decryptedToken -eq $testToken
}

# Test GitHub token validation
Test-Case -Name "GitHub Token Validation" -Message "GitHub token should be valid" -Test {
    Write-TestDebug -Message "Testing GitHub token validation" -TestName "GitHub Token Validation" -TestSuite $TestSuite
    $token = $env:GITHUB_TOKEN
    if (-not $token) {
        return $false
    }
    
    $headers = @{
        Authorization = "token $token"
    }
    try {
        $response = Invoke-WebRequest -Uri "https://api.github.com/user" -Headers $headers -UseBasicParsing
        return $response.StatusCode -eq 200
    }
    catch {
        return $false
    }
}

# Test complete token management workflow
Test-Case -Name "Token Management Workflow" -Message "Complete token management workflow should succeed" -Test {
    Write-TestDebug -Message "Testing complete token management workflow" -TestName "Token Management Workflow" -TestSuite $TestSuite
    $tokenDir = Join-Path (Join-Path $PSScriptRoot "..") "tokens"
    $keyFile = Join-Path $tokenDir "encryption.key"
    $key = [Convert]::FromBase64String((Get-Content -Path $keyFile))
    
    $testToken = "test_token_123"
    $secureString = ConvertTo-SecureString -String $testToken -AsPlainText -Force
    $encrypted = ConvertFrom-SecureString -SecureString $secureString -Key $key
    
    $decrypted = ConvertTo-SecureString -String $encrypted -Key $key
    $decryptedToken = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($decrypted)
    )
    
    $headers = @{
        Authorization = "token $decryptedToken"
    }
    try {
        $response = Invoke-WebRequest -Uri "https://api.github.com/user" -Headers $headers -UseBasicParsing
        return $response.StatusCode -eq 200
    }
    catch {
        return $false
    }
}

# Cleanup test files
Write-TestDebug -Message "Cleaning up test files" -TestSuite $TestSuite
try {
    Remove-Item -Path (Join-Path (Join-Path $PSScriptRoot "..") "tokens") -Recurse -Force -ErrorAction SilentlyContinue
}
catch {
    Write-TestWarning -Message "Failed to clean up test files" -TestSuite $TestSuite -Context @{
        Error = $_.Exception.Message
    }
}

# End test suite and print summary
End-TestSuite -SuiteName $TestSuite -Results $testResults

# Return test results
return $testResults 