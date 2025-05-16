# Environment Test Script
# Tests system requirements and dependencies

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "TestLogger.ps1")

# Initialize test results
$testResults = @{
    StartTime = Get-Date
    EndTime = $null
    Tests = @()
    Environment = @{
        OS = $null
        PowerShell = $null
        Docker = $null
        PHP = $null
        Extensions = @{}
        Services = @{}
    }
}

# Function to log test results
function Add-TestResult {
    param (
        [string]$Name,
        [bool]$Passed,
        [string]$Details,
        [string]$Category
    )
    
    $testResults.Tests += @{
        Name = $Name
        Status = if ($Passed) { "Passed" } else { "Failed" }
        Details = $Details
        Time = Get-Date
        Category = $Category
    }
}

# Function to test Docker availability
function Test-DockerAvailability {
    try {
        $dockerVersion = docker --version
        if ($dockerVersion) {
            Add-TestResult -Name "Docker Availability" -Passed $true -Details $dockerVersion -Category "Docker"
            $testResults.Environment.Docker = $dockerVersion
            return $true
        }
    }
    catch {
        Add-TestResult -Name "Docker Availability" -Passed $false -Details "Docker not found" -Category "Docker"
        return $false
    }
}

# Function to test PHP installation
function Test-PHPInstallation {
    try {
        if (Test-DockerAvailability) {
            $phpVersion = docker exec service_learning_management php -v
        } else {
            $phpVersion = php -v
        }
        
        if ($phpVersion) {
            Add-TestResult -Name "PHP Installation" -Passed $true -Details $phpVersion -Category "PHP"
            $testResults.Environment.PHP = $phpVersion
            return $true
        }
    }
    catch {
        Add-TestResult -Name "PHP Installation" -Passed $false -Details "PHP not found" -Category "PHP"
        return $false
    }
}

# Function to test PHP extension
function Test-PHPExtension {
    param (
        [string]$ExtensionName
    )
    
    try {
        if (Test-DockerAvailability) {
            $extension = docker exec service_learning_management php -m | Select-String $ExtensionName
        } else {
            $extension = php -m | Select-String $ExtensionName
        }
        
        if ($extension) {
            Add-TestResult -Name "PHP Extension: $ExtensionName" -Passed $true -Details $extension -Category "PHP"
            $testResults.Environment.Extensions[$ExtensionName] = $true
            return $true
        } else {
            Add-TestResult -Name "PHP Extension: $ExtensionName" -Passed $false -Details "Extension not found" -Category "PHP"
            $testResults.Environment.Extensions[$ExtensionName] = $false
            return $false
        }
    }
    catch {
        Add-TestResult -Name "PHP Extension: $ExtensionName" -Passed $false -Details "Error checking extension" -Category "PHP"
        $testResults.Environment.Extensions[$ExtensionName] = $false
        return $false
    }
}

# Function to test MySQL connection
function Test-MySQLConnection {
    try {
        if (Test-DockerAvailability) {
            $result = docker exec service_learning_management php -r "try { new PDO('mysql:host=mysql;dbname=service_learning', 'dev', 'dev'); echo 'Connected successfully'; } catch(PDOException \$e) { echo \$e->getMessage(); }"
        } else {
            $result = php -r "try { new PDO('mysql:host=localhost;dbname=service_learning', 'root', ''); echo 'Connected successfully'; } catch(PDOException \$e) { echo \$e->getMessage(); }"
        }
        
        if ($result -match "Connected successfully") {
            Add-TestResult -Name "MySQL Connection" -Passed $true -Details "Connected successfully" -Category "Services"
            $testResults.Environment.Services.MySQL = $true
            return $true
        } else {
            Add-TestResult -Name "MySQL Connection" -Passed $false -Details $result -Category "Services"
            $testResults.Environment.Services.MySQL = $false
            return $false
        }
    }
    catch {
        Add-TestResult -Name "MySQL Connection" -Passed $false -Details "Error testing connection" -Category "Services"
        $testResults.Environment.Services.MySQL = $false
        return $false
    }
}

# Function to test Redis connection
function Test-RedisConnection {
    try {
        if (Test-DockerAvailability) {
            $result = docker exec service_learning_management php -r "try { \$redis = new Redis(); \$redis->connect('redis', 6379); echo 'Connected successfully'; } catch(Exception \$e) { echo \$e->getMessage(); }"
        } else {
            $result = php -r "try { \$redis = new Redis(); \$redis->connect('127.0.0.1', 6379); echo 'Connected successfully'; } catch(Exception \$e) { echo \$e->getMessage(); }"
        }
        
        if ($result -match "Connected successfully") {
            Add-TestResult -Name "Redis Connection" -Passed $true -Details "Connected successfully" -Category "Services"
            $testResults.Environment.Services.Redis = $true
            return $true
        } else {
            Add-TestResult -Name "Redis Connection" -Passed $false -Details $result -Category "Services"
            $testResults.Environment.Services.Redis = $false
            return $false
        }
    }
    catch {
        Add-TestResult -Name "Redis Connection" -Passed $false -Details "Error testing connection" -Category "Services"
        $testResults.Environment.Services.Redis = $false
        return $false
    }
}

# Function to write report
function Write-EnvironmentReport {
    $reportPath = Join-Path $PSScriptRoot "..\.test\results\environment-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    $report = @"
# Environment Test Report
Generated: $(Get-Date)

## System Information
- OS: $($testResults.Environment.OS)
- PowerShell: $($testResults.Environment.PowerShell)
- Duration: $((Get-Date) - $testResults.StartTime).TotalSeconds seconds

## Test Results
"@

    foreach ($test in $testResults.Tests) {
        $report += @"

### $($test.Name)
- Status: $($test.Status)
- Details: $($test.Details)
- Time: $($test.Time)
"@
    }

    Set-Content -Path $reportPath -Value $report
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [INFO] Environment report generated: $reportPath"
}

# Main execution
try {
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [INFO] Starting environment tests..."
    
    # Set environment information
    $testResults.Environment.OS = [System.Environment]::OSVersion
    $testResults.Environment.PowerShell = $PSVersionTable.PSVersion
    
    # Run tests
    Test-DockerAvailability
    Test-PHPInstallation
    
    # Test required PHP extensions
    $requiredExtensions = @("pdo", "pdo_mysql", "redis", "mbstring", "xml", "curl", "fileinfo", "gd", "intl", "mysqli", "zip")
    foreach ($extension in $requiredExtensions) {
        Test-PHPExtension -ExtensionName $extension
    }
    
    # Test service connections
    Test-MySQLConnection
    Test-RedisConnection
    
    # Generate report
    Write-EnvironmentReport
    
    # Save test results
    $resultsPath = Join-Path $PSScriptRoot "..\.test\tracking\environment-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $testResults.EndTime = Get-Date
    $testResults | ConvertTo-Json -Depth 10 | Set-Content -Path $resultsPath
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [INFO] Environment data saved: $resultsPath"
    
    # Check for failures
    $failedTests = $testResults.Tests | Where-Object { $_.Status -eq "Failed" }
    if ($failedTests) {
        Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [WARNING] Environment tests completed with failures"
        exit 1
    } else {
        Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [INFO] Environment tests completed successfully"
        exit 0
    }
}
catch {
    Write-Host "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff')] [ERROR] Error running environment tests: $_"
    exit 1
} 