# Test script for prerequisites
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "check-prerequisites.ps1")

# Test suite name
$TestSuite = "Prerequisites"

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

# Test PowerShell version
Test-Case -Name "PowerShell Version" -Message "PowerShell version should be 7.0 or higher" -Test {
    Write-TestDebug -Message "Checking PowerShell version" -TestName "PowerShell Version" -TestSuite $TestSuite
    $version = $PSVersionTable.PSVersion
    Write-TestDebug -Message "Current PowerShell version" -TestName "PowerShell Version" -TestSuite $TestSuite -Context @{
        Version = $version.ToString()
    }
    return $version.Major -ge 7
}

# Test Windows version
Test-Case -Name "Windows Version" -Message "Windows version should be 10 or higher" -Test {
    Write-TestDebug -Message "Checking Windows version" -TestName "Windows Version" -TestSuite $TestSuite
    $os = Get-WmiObject -Class Win32_OperatingSystem
    Write-TestDebug -Message "Current Windows version" -TestName "Windows Version" -TestSuite $TestSuite -Context @{
        Version = $os.Version
        Caption = $os.Caption
    }
    return [version]$os.Version -ge [version]"10.0"
}

# Test required tools
Test-Case -Name "Required Tools" -Message "All required tools should be available" -Test {
    Write-TestDebug -Message "Checking required tools" -TestName "Required Tools" -TestSuite $TestSuite
    $tools = @("git", "node", "npm", "composer")
    $missingTools = @()
    
    foreach ($tool in $tools) {
        Write-TestDebug -Message "Checking tool" -TestName "Required Tools" -TestSuite $TestSuite -Context @{
            Tool = $tool
        }
        if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) {
            $missingTools += $tool
        }
    }
    
    if ($missingTools.Count -gt 0) {
        Write-TestDebug -Message "Missing tools" -TestName "Required Tools" -TestSuite $TestSuite -Context @{
            MissingTools = $missingTools
        }
        return $false
    }
    return $true
}

# Test directory permissions
Test-Case -Name "Directory Permissions" -Message "Should have write permissions in workspace" -Test {
    Write-TestDebug -Message "Checking directory permissions" -TestName "Directory Permissions" -TestSuite $TestSuite
    $testDir = Join-Path (Join-Path $PSScriptRoot "..") "temp"
    if (-not (Test-Path $testDir)) {
        New-Item -ItemType Directory -Path $testDir -Force | Out-Null
    }
    $testFile = Join-Path $testDir "test.tmp"
    try {
        New-Item -ItemType File -Path $testFile -Force | Out-Null
        Remove-Item $testFile -Force
        return $true
    }
    catch {
        return $false
    }
}

# Test GitHub authentication
Test-Case -Name "GitHub Authentication" -Message "Should be able to authenticate with GitHub" -Test {
    Write-TestDebug -Message "Checking GitHub authentication" -TestName "GitHub Authentication" -TestSuite $TestSuite
    try {
        $token = $env:GITHUB_TOKEN
        if (-not $token) {
            Write-TestDebug -Message "GitHub token not found" -TestName "GitHub Authentication" -TestSuite $TestSuite
            return $false
        }
        
        $headers = @{
            Authorization = "token $token"
        }
        $response = Invoke-WebRequest -Uri "https://api.github.com/user" -Headers $headers -UseBasicParsing
        return $response.StatusCode -eq 200
    }
    catch {
        Write-TestDebug -Message "GitHub authentication failed" -TestName "GitHub Authentication" -TestSuite $TestSuite -Context @{
            Error = $_.Exception.Message
        }
        return $false
    }
}

# Test internet connectivity
Test-Case -Name "Internet Connectivity" -Message "Should have internet connectivity" -Test {
    Write-TestDebug -Message "Checking internet connectivity" -TestName "Internet Connectivity" -TestSuite $TestSuite
    try {
        $response = Test-NetConnection -ComputerName github.com -Port 443 -WarningAction SilentlyContinue
        Write-TestDebug -Message "Internet connectivity check result" -TestName "Internet Connectivity" -TestSuite $TestSuite -Context @{
            Success = $response.TcpTestSucceeded
        }
        return $response.TcpTestSucceeded
    }
    catch {
        return $false
    }
}

# End test suite and print summary
End-TestSuite -SuiteName $TestSuite -Results $testResults

# Return test results
return $testResults 