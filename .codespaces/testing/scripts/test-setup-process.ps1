# Test script for setup process
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "check-prerequisites.ps1")
. (Join-Path $PSScriptRoot "manage-tokens.ps1")
. (Join-Path $PSScriptRoot "manage-state.ps1")

# Test suite name
$TestSuite = "Setup Process"

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

# Test environment initialization
Test-Case -Name "Environment Initialization" -Message "Environment should be initialized successfully" -Test {
    Write-TestDebug -Message "Testing environment initialization" -TestName "Environment Initialization" -TestSuite $TestSuite
    $baseDir = Join-Path $PSScriptRoot ".."
    $envDir = Join-Path $baseDir "env"
    if (-not (Test-Path $envDir)) {
        New-Item -ItemType Directory -Path $envDir -Force | Out-Null
    }
    $envFile = Join-Path $envDir ".env"
    if (-not (Test-Path $envFile)) {
        @"
# Environment variables
GITHUB_TOKEN=
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=service_learning
DB_USERNAME=root
DB_PASSWORD=
"@ | Set-Content -Path $envFile
    }
    return Test-Path $envFile
}

# Test prerequisite checking
Test-Case -Name "Prerequisite Checking" -Message "Prerequisites should be checked successfully" -Test {
    Write-TestDebug -Message "Testing prerequisite checking" -TestName "Prerequisite Checking" -TestSuite $TestSuite
    $prereqs = @{
        powershell = $PSVersionTable.PSVersion.Major -ge 7
        windows = (Get-WmiObject Win32_OperatingSystem).Version -ge "10.0"
        tools = $true
        permissions = $true
        github = $true
        internet = $true
    }
    
    # Check tools
    $requiredTools = @("git", "node", "npm", "composer")
    foreach ($tool in $requiredTools) {
        if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
            $prereqs.tools = $false
            break
        }
    }
    
    # Check permissions
    $testDir = Join-Path $env:TEMP "test_permissions"
    try {
        New-Item -ItemType Directory -Path $testDir -Force | Out-Null
        $testFile = Join-Path $testDir "test.txt"
        "test" | Set-Content -Path $testFile
        Remove-Item -Path $testDir -Recurse -Force
    }
    catch {
        $prereqs.permissions = $false
    }
    
    # Check GitHub token
    if (-not $env:GITHUB_TOKEN) {
        $prereqs.github = $false
    }
    
    # Check internet
    try {
        $response = Invoke-WebRequest -Uri "https://github.com" -UseBasicParsing
        $prereqs.internet = $response.StatusCode -eq 200
    }
    catch {
        $prereqs.internet = $false
    }
    
    return $prereqs.powershell -and $prereqs.windows -and $prereqs.tools -and $prereqs.permissions -and $prereqs.github -and $prereqs.internet
}

# Test token management
Test-Case -Name "Token Management" -Message "Token management should work successfully" -Test {
    Write-TestDebug -Message "Testing token management" -TestName "Token Management" -TestSuite $TestSuite
    $baseDir = Join-Path $PSScriptRoot ".."
    $tokenDir = Join-Path $baseDir "tokens"
    if (-not (Test-Path $tokenDir)) {
        New-Item -ItemType Directory -Path $tokenDir -Force | Out-Null
    }
    
    $keyFile = Join-Path $tokenDir "encryption.key"
    if (-not (Test-Path $keyFile)) {
        $key = New-Object byte[] 32
        [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($key)
        [Convert]::ToBase64String($key) | Set-Content -Path $keyFile
    }
    
    $testToken = "test_token_123"
    $key = [Convert]::FromBase64String((Get-Content -Path $keyFile))
    
    $secureString = ConvertTo-SecureString -String $testToken -AsPlainText -Force
    $encrypted = ConvertFrom-SecureString -SecureString $secureString -Key $key
    
    $decrypted = ConvertTo-SecureString -String $encrypted -Key $key
    $decryptedToken = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($decrypted)
    )
    
    return $decryptedToken -eq $testToken
}

# Test state management
Test-Case -Name "State Management" -Message "State management should work successfully" -Test {
    Write-TestDebug -Message "Testing state management" -TestName "State Management" -TestSuite $TestSuite
    $baseDir = Join-Path $PSScriptRoot ".."
    $stateDir = Join-Path $baseDir "state"
    if (-not (Test-Path $stateDir)) {
        New-Item -ItemType Directory -Path $stateDir -Force | Out-Null
    }
    
    $stateFile = Join-Path $stateDir "setup-state.json"
    if (-not (Test-Path $stateFile)) {
        @{
            steps = @()
            lastStep = $null
            failedSteps = @()
            progress = 0
        } | ConvertTo-Json | Set-Content -Path $stateFile
    }
    
    $step = @{
        name = "test_step"
        status = "success"
        message = "Test step completed"
        timestamp = (Get-Date).ToString("o")
    }
    
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    $state.steps += $step
    $state.lastStep = $step
    $state.progress = 100
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    $updatedState = Get-Content -Path $stateFile | ConvertFrom-Json
    return $updatedState.lastStep.name -eq "test_step" -and $updatedState.lastStep.status -eq "success"
}

# Test complete setup workflow
Test-Case -Name "Complete Setup Workflow" -Message "Complete setup workflow should succeed" -Test {
    Write-TestDebug -Message "Testing complete setup workflow" -TestName "Complete Setup Workflow" -TestSuite $TestSuite
    
    # Initialize environment
    $baseDir = Join-Path $PSScriptRoot ".."
    $envDir = Join-Path $baseDir "env"
    if (-not (Test-Path $envDir)) {
        New-Item -ItemType Directory -Path $envDir -Force | Out-Null
    }
    $envFile = Join-Path $envDir ".env"
    if (-not (Test-Path $envFile)) {
        @"
# Environment variables
GITHUB_TOKEN=
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=service_learning
DB_USERNAME=root
DB_PASSWORD=
"@ | Set-Content -Path $envFile
    }
    
    # Check prerequisites
    $prereqs = @{
        powershell = $PSVersionTable.PSVersion.Major -ge 7
        windows = (Get-WmiObject Win32_OperatingSystem).Version -ge "10.0"
        tools = $true
        permissions = $true
        github = $true
        internet = $true
    }
    
    # Check tools
    $requiredTools = @("git", "node", "npm", "composer")
    foreach ($tool in $requiredTools) {
        if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
            $prereqs.tools = $false
            break
        }
    }
    
    # Check permissions
    $testDir = Join-Path $env:TEMP "test_permissions"
    try {
        New-Item -ItemType Directory -Path $testDir -Force | Out-Null
        $testFile = Join-Path $testDir "test.txt"
        "test" | Set-Content -Path $testFile
        Remove-Item -Path $testDir -Recurse -Force
    }
    catch {
        $prereqs.permissions = $false
    }
    
    # Check GitHub token
    if (-not $env:GITHUB_TOKEN) {
        $prereqs.github = $false
    }
    
    # Check internet
    try {
        $response = Invoke-WebRequest -Uri "https://github.com" -UseBasicParsing
        $prereqs.internet = $response.StatusCode -eq 200
    }
    catch {
        $prereqs.internet = $false
    }
    
    # Initialize token management
    $tokenDir = Join-Path $baseDir "tokens"
    if (-not (Test-Path $tokenDir)) {
        New-Item -ItemType Directory -Path $tokenDir -Force | Out-Null
    }
    
    $keyFile = Join-Path $tokenDir "encryption.key"
    if (-not (Test-Path $keyFile)) {
        $key = New-Object byte[] 32
        [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($key)
        [Convert]::ToBase64String($key) | Set-Content -Path $keyFile
    }
    
    # Initialize state management
    $stateDir = Join-Path $baseDir "state"
    if (-not (Test-Path $stateDir)) {
        New-Item -ItemType Directory -Path $stateDir -Force | Out-Null
    }
    
    $stateFile = Join-Path $stateDir "setup-state.json"
    if (-not (Test-Path $stateFile)) {
        @{
            steps = @()
            lastStep = $null
            failedSteps = @()
            progress = 0
        } | ConvertTo-Json | Set-Content -Path $stateFile
    }
    
    # Update state
    $step = @{
        name = "setup_complete"
        status = "success"
        message = "Setup completed successfully"
        timestamp = (Get-Date).ToString("o")
    }
    
    $state = Get-Content -Path $stateFile | ConvertFrom-Json
    $state.steps += $step
    $state.lastStep = $step
    $state.progress = 100
    $state | ConvertTo-Json | Set-Content -Path $stateFile
    
    return $prereqs.powershell -and $prereqs.windows -and $prereqs.tools -and $prereqs.permissions -and $prereqs.github -and $prereqs.internet -and
           (Test-Path $envFile) -and (Test-Path $keyFile) -and (Test-Path $stateFile)
}

# Cleanup test files
Write-TestDebug -Message "Cleaning up test files" -TestSuite $TestSuite
try {
    $baseDir = Join-Path $PSScriptRoot ".."
    Remove-Item -Path (Join-Path $baseDir "env") -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item -Path (Join-Path $baseDir "tokens") -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item -Path (Join-Path $baseDir "state") -Recurse -Force -ErrorAction SilentlyContinue
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