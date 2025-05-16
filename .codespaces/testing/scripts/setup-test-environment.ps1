# Test environment setup script for Codespaces

# Stop on error
$ErrorActionPreference = "Stop"

# Import required modules
. (Join-Path $PSScriptRoot "TestLogger.ps1")
. (Join-Path $PSScriptRoot "check-prerequisites.ps1")

# Initialize logging
Start-TestSuite -SuiteName "Environment Setup"

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
    
    Start-Test -TestName $Name -TestSuite "Environment Setup"
    $testResults.total++
    
    try {
        Write-TestDebug -Message "Executing test" -TestName $Name -TestSuite "Environment Setup" -Context @{
            Message = $Message
        }
        
        $result = & $Test
        
        if ($result) {
            $testResults.passed++
            End-Test -TestName $Name -TestSuite "Environment Setup" -Success $true -Message $Message
        }
        else {
            $testResults.failed += @{
                name = $Name
                message = $Message
            }
            End-Test -TestName $Name -TestSuite "Environment Setup" -Success $false -Message $Message
        }
    }
    catch {
        $testResults.failed += @{
            name = $Name
            message = $Message
            error = $_.Exception.Message
        }
        End-Test -TestName $Name -TestSuite "Environment Setup" -Success $false -Message $Message -Error $_.Exception.Message
    }
}

# Test GitHub CLI installation
Test-Case -Name "GitHub CLI Installation" -Message "GitHub CLI should be installed" -Test {
    $ghVersion = gh --version
    return $LASTEXITCODE -eq 0
}

# Test Docker installation
Test-Case -Name "Docker Installation" -Message "Docker should be installed" -Test {
    $dockerVersion = docker --version
    return $LASTEXITCODE -eq 0
}

# Test Docker Compose installation
Test-Case -Name "Docker Compose Installation" -Message "Docker Compose should be installed" -Test {
    $composeVersion = docker-compose --version
    return $LASTEXITCODE -eq 0
}

# Test PHP installation
Test-Case -Name "PHP Installation" -Message "PHP should be installed" -Test {
    $phpVersion = php --version
    return $LASTEXITCODE -eq 0
}

# Test Composer installation
Test-Case -Name "Composer Installation" -Message "Composer should be installed" -Test {
    $composerVersion = composer --version
    return $LASTEXITCODE -eq 0
}

# Test Node.js installation
Test-Case -Name "Node.js Installation" -Message "Node.js should be installed" -Test {
    $nodeVersion = node --version
    return $LASTEXITCODE -eq 0
}

# Test npm installation
Test-Case -Name "npm Installation" -Message "npm should be installed" -Test {
    $npmVersion = npm --version
    return $LASTEXITCODE -eq 0
}

# Test database connection
Test-Case -Name "Database Connection" -Message "Database should be accessible" -Test {
    $env:DB_HOST = "localhost"
    $env:DB_PORT = "3306"
    $env:DB_DATABASE = "service_learning_test"
    $env:DB_USERNAME = "root"
    $env:DB_PASSWORD = "root"
    
    $result = php scripts/verify-test-environment.php
    return $LASTEXITCODE -eq 0
}

# Test Redis connection
Test-Case -Name "Redis Connection" -Message "Redis should be accessible" -Test {
    $env:REDIS_HOST = "localhost"
    $env:REDIS_PORT = "6379"
    
    $result = php scripts/verify-test-environment.php
    return $LASTEXITCODE -eq 0
}

# Test GitHub authentication
Test-Case -Name "GitHub Authentication" -Message "GitHub should be authenticated" -Test {
    $authStatus = gh auth status
    return $LASTEXITCODE -eq 0
}

# Test Docker services
Test-Case -Name "Docker Services" -Message "Docker services should be running" -Test {
    docker-compose -f docker-compose.test.yml up -d
    return $LASTEXITCODE -eq 0
}

# End test suite and print summary
End-TestSuite -SuiteName "Environment Setup" -Results $testResults

# Return test results
return $testResults 