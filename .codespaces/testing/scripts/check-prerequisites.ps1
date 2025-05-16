# Prerequisites checker script

# Stop on error
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")

# Initialize logging
Start-TestSuite -SuiteName "Prerequisites Check"

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
    
    Start-Test -TestName $Name -TestSuite "Prerequisites Check"
    $testResults.total++
    
    try {
        Write-TestDebug -Message "Executing test" -TestName $Name -TestSuite "Prerequisites Check" -Context @{
            Message = $Message
        }
        
        $result = & $Test
        
        if ($result) {
            $testResults.passed++
            End-Test -TestName $Name -TestSuite "Prerequisites Check" -Success $true -Message $Message
        }
        else {
            $testResults.failed += @{
                name = $Name
                message = $Message
            }
            End-Test -TestName $Name -TestSuite "Prerequisites Check" -Success $false -Message $Message
        }
    }
    catch {
        $testResults.failed += @{
            name = $Name
            message = $Message
            error = $_.Exception.Message
        }
        End-Test -TestName $Name -TestSuite "Prerequisites Check" -Success $false -Message $Message -Error $_.Exception.Message
    }
}

# Check PowerShell version
Test-Case -Name "PowerShell Version" -Message "PowerShell 7.0 or higher required" -Test {
    $version = $PSVersionTable.PSVersion
    return $version.Major -ge 7
}

# Check administrator rights
Test-Case -Name "Administrator Rights" -Message "Script should run with administrator rights" -Test {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Check Docker service
Test-Case -Name "Docker Service" -Message "Docker service should be running" -Test {
    $service = Get-Service -Name "docker" -ErrorAction SilentlyContinue
    return $service -and $service.Status -eq "Running"
}

# Check required ports
Test-Case -Name "Port 8000" -Message "Port 8000 should be available" -Test {
    $port = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
    return -not $port
}

Test-Case -Name "Port 3306" -Message "Port 3306 should be available" -Test {
    $port = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
    return -not $port
}

Test-Case -Name "Port 6379" -Message "Port 6379 should be available" -Test {
    $port = Get-NetTCPConnection -LocalPort 6379 -ErrorAction SilentlyContinue
    return -not $port
}

# Check required environment variables
Test-Case -Name "Environment Variables" -Message "Required environment variables should be set" -Test {
    $requiredVars = @(
        "DB_HOST",
        "DB_PORT",
        "DB_DATABASE",
        "DB_USERNAME",
        "DB_PASSWORD",
        "REDIS_HOST",
        "REDIS_PORT",
        "API_TOKEN"
    )
    
    $missingVars = $requiredVars | Where-Object { -not (Get-Item "env:$_" -ErrorAction SilentlyContinue) }
    return $missingVars.Count -eq 0
}

# Check required directories
Test-Case -Name "Required Directories" -Message "Required directories should exist" -Test {
    $requiredDirs = @(
        "tests",
        "tests/Feature",
        "tests/Unit",
        "tests/Integration",
        "storage/logs",
        "storage/framework/testing"
    )
    
    $missingDirs = $requiredDirs | Where-Object { -not (Test-Path $_) }
    return $missingDirs.Count -eq 0
}

# Check required files
Test-Case -Name "Required Files" -Message "Required files should exist" -Test {
    $requiredFiles = @(
        "composer.json",
        "phpunit.xml",
        ".env.testing",
        "docker-compose.test.yml"
    )
    
    $missingFiles = $requiredFiles | Where-Object { -not (Test-Path $_) }
    return $missingFiles.Count -eq 0
}

# Check PHP extensions
Test-Case -Name "PHP Extensions" -Message "Required PHP extensions should be installed" -Test {
    $requiredExtensions = @(
        "pdo",
        "pdo_mysql",
        "redis",
        "curl",
        "mbstring",
        "xml",
        "zip"
    )
    
    $installedExtensions = php -m
    $missingExtensions = $requiredExtensions | Where-Object { $installedExtensions -notcontains $_ }
    return $missingExtensions.Count -eq 0
}

# Check Node.js packages
Test-Case -Name "Node.js Packages" -Message "Required Node.js packages should be installed" -Test {
    $requiredPackages = @(
        "npm",
        "yarn"
    )
    
    $missingPackages = $requiredPackages | Where-Object {
        $package = $_
        $installed = $false
        try {
            $version = & $package --version
            $installed = $true
        }
        catch {
            $installed = $false
        }
        -not $installed
    }
    return $missingPackages.Count -eq 0
}

# Check Composer packages
Test-Case -Name "Composer Packages" -Message "Required Composer packages should be installed" -Test {
    $requiredPackages = @(
        "phpunit/phpunit",
        "mockery/mockery",
        "fakerphp/faker"
    )
    
    $composerJson = Get-Content composer.json | ConvertFrom-Json
    $installedPackages = $composerJson.require + $composerJson."require-dev"
    $missingPackages = $requiredPackages | Where-Object { $installedPackages -notcontains $_ }
    return $missingPackages.Count -eq 0
}

# End test suite and print summary
End-TestSuite -SuiteName "Prerequisites Check" -Results $testResults

# Return test results
return $testResults 