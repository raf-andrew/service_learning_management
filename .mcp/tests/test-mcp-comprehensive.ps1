# MCP Comprehensive Test Framework
$ErrorActionPreference = "Stop"

Write-Host "[DEBUG] Test framework starting..."

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path (Join-Path -Path $scriptPath -ChildPath "..") -ChildPath ".." | Join-Path -ChildPath "mcp"

Write-Host "[DEBUG] Importing core MCP from: $mcpPath"

# Check for required NuGet packages
$requiredPackages = @(
    @{ Name = "MySql.Data"; Version = "8.0.33" },
    @{ Name = "StackExchange.Redis"; Version = "2.6.122" }
)

foreach ($package in $requiredPackages) {
    $packagePath = Join-Path -Path $scriptPath -ChildPath "packages\$($package.Name).$($package.Version)"
    if (-not (Test-Path $packagePath)) {
        Write-Host "[WARNING] Required package $($package.Name) not found. Installing..."
        try {
            # Create packages directory if it doesn't exist
            $packagesDir = Join-Path -Path $scriptPath -ChildPath "packages"
            if (-not (Test-Path $packagesDir)) {
                New-Item -ItemType Directory -Path $packagesDir -Force | Out-Null
            }
            
            # Install package using NuGet
            nuget install $package.Name -Version $package.Version -OutputDirectory $packagesDir
        }
        catch {
            Write-Host "[ERROR] Failed to install package $($package.Name): $_"
            Write-Host "[ERROR] Please install the package manually using: nuget install $($package.Name) -Version $($package.Version) -OutputDirectory $scriptPath\packages"
            exit 1
        }
    }
    
    # Add package reference
    Add-Type -Path (Join-Path -Path $packagePath -ChildPath "lib\netstandard2.0\$($package.Name).dll")
}

# Import core MCP
. (Join-Path -Path $mcpPath -ChildPath "init.ps1")

Write-Host "[DEBUG] Core MCP imported successfully"

# Test Report Structure
$testReport = @{
    StartTime = Get-Date
    EndTime = $null
    Environment = $null
    Services = @{}
    Tests = @{
        Total = 0
        Passed = 0
        Failed = 0
        Skipped = 0
        Warnings = 0
    }
    Categories = @{}
    Issues = @()
    Certifications = @()
}

# Remote Service Configurations
$remoteConfig = @{
    MySQL = @{
        Host = "codespaces-mysql"
        Port = 3306
        User = "root"
        Password = ""
        Database = "test"
        HealthCheck = @{
            Enabled = $true
            Interval = 5
            Timeout = 5
            RetryCount = 3
        }
    }
    Redis = @{
        Host = "codespaces-redis"
        Port = 6379
        Password = ""
        HealthCheck = @{
            Enabled = $true
            Interval = 5
            Timeout = 5
            RetryCount = 3
        }
    }
    Nginx = @{
        Host = "codespaces-nginx"
        Port = 80
        SSL = $false
        HealthCheck = @{
            Enabled = $true
            Interval = 5
            Timeout = 5
            RetryCount = 3
        }
    }
}

# Test Categories with Remote Service Tests
$testCategories = @{
    "Core MCP" = @{
        Tests = @(
            @{
                Name = "MCP Initialization"
                Function = "Initialize-MCP"
                Parameters = @{
                    Environment = "codespaces"
                    Verify = $true
                    AutoHeal = $true
                }
                EdgeCases = @(
                    @{ Environment = "invalid" }
                    @{ Environment = $null }
                )
            },
            @{
                Name = "Remote Service Health Check"
                Function = "Test-ServiceHealth"
                Parameters = @{
                    Service = "mysql"
                    Config = $remoteConfig.MySQL
                }
                EdgeCases = @(
                    @{ Service = "nonexistent" }
                    @{ Config = @{ Host = "invalid" } }
                )
            }
        )
    }
    "Service Management" = @{
        Tests = @(
            @{
                Name = "Remote MySQL Service"
                Function = "Test-ServiceHealth"
                Parameters = @{
                    Service = "mysql"
                    Config = $remoteConfig.MySQL
                }
                EdgeCases = @(
                    @{ Service = "nonexistent" }
                    @{ Config = @{ Host = "invalid" } }
                )
            },
            @{
                Name = "Remote Redis Service"
                Function = "Test-ServiceHealth"
                Parameters = @{
                    Service = "redis"
                    Config = $remoteConfig.Redis
                }
                EdgeCases = @(
                    @{ Service = "nonexistent" }
                    @{ Config = @{ Host = "invalid" } }
                )
            }
        )
    }
    "Data Management" = @{
        Tests = @(
            @{
                Name = "Remote MySQL Data Operations"
                Function = "Test-DataOperations"
                Parameters = @{
                    Operations = @("read", "write")
                    Config = $remoteConfig.MySQL
                }
                EdgeCases = @(
                    @{ Operations = @("invalid") }
                    @{ Config = @{ Host = "invalid" } }
                )
            }
        )
    }
    "Logging System" = @{
        Tests = @(
            @{
                Name = "Remote Log Operations"
                Function = "Test-LogOperations"
                Parameters = @{
                    Operations = @("write", "read", "rotate")
                    Config = @{
                        Path = "/var/log/mcp"
                        Level = "INFO"
                        Remote = $true
                    }
                }
                EdgeCases = @(
                    @{ LogLevel = "invalid" }
                    @{ Config = @{ Path = "/invalid/path" } }
                )
            }
        )
    }
}

# Test Functions
function Test-ServiceHealth {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Config
    )
    
    Write-Host "[DEBUG] Testing remote service health for $Service at $($Config.Host):$($Config.Port)"
    
    $results = @{
        Success = $true
        Details = @()
    }
    
    try {
        # Test TCP connection
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $connection = $tcpClient.BeginConnect($Config.Host, $Config.Port, $null, $null)
        $wait = $connection.AsyncWaitHandle.WaitOne($Config.HealthCheck.Timeout * 1000, $false)
        
        if ($wait) {
            $tcpClient.EndConnect($connection)
            $results.Details += "Successfully connected to $Service at $($Config.Host):$($Config.Port)"
            
            # Test service-specific health
            switch ($Service) {
                "mysql" {
                    $connectionString = "Server=$($Config.Host);Port=$($Config.Port);Database=$($Config.Database);Uid=$($Config.User);Pwd=$($Config.Password)"
                    $connection = New-Object MySql.Data.MySqlClient.MySqlConnection($connectionString)
                    $connection.Open()
                    $results.Details += "MySQL connection successful"
                    $connection.Close()
                }
                "redis" {
                    $redis = New-Object StackExchange.Redis.ConnectionMultiplexer("$($Config.Host):$($Config.Port)")
                    $redis.GetDatabase().Ping()
                    $results.Details += "Redis connection successful"
                }
                "nginx" {
                    $response = Invoke-WebRequest -Uri "http://$($Config.Host):$($Config.Port)" -UseBasicParsing
                    $results.Details += "Nginx response: $($response.StatusCode)"
                }
            }
        } else {
            $results.Success = $false
            $results.Details += "Failed to connect to $Service at $($Config.Host):$($Config.Port)"
        }
    }
    catch {
        $results.Success = $false
        $errorMsg = "Error testing {0}: {1}" -f $Service, $_.Exception.Message
        $results.Details += $errorMsg
    }
    finally {
        if ($tcpClient) {
            $tcpClient.Close()
        }
    }
    
    return $results
}

function Test-DataOperations {
    param (
        [Parameter(Mandatory=$true)]
        [string[]]$Operations,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Config
    )
    
    Write-Host "[DEBUG] Testing remote data operations with config: $($Config | ConvertTo-Json)"
    
    $results = @{
        Success = $true
        Details = @()
    }
    
    try {
        # Test MySQL connection
        $connectionString = "Server=$($Config.Host);Port=$($Config.Port);Database=$($Config.Database);Uid=$($Config.User);Pwd=$($Config.Password)"
        $connection = New-Object MySql.Data.MySqlClient.MySqlConnection($connectionString)
        $connection.Open()
        
        foreach ($operation in $Operations) {
            try {
                switch ($operation) {
                    "read" {
                        $command = $connection.CreateCommand()
                        $command.CommandText = "SELECT 1"
                        $reader = $command.ExecuteReader()
                        $reader.Close()
                        $results.Details += "Read operation successful"
                    }
                    "write" {
                        $command = $connection.CreateCommand()
                        $command.CommandText = "CREATE TABLE IF NOT EXISTS test_table (id INT)"
                        $command.ExecuteNonQuery()
                        $results.Details += "Write operation successful"
                    }
                    default {
                        throw "Invalid operation: $operation"
                    }
                }
            }
            catch {
                $results.Success = $false
                $results.Details += "Failed to perform $operation : $_"
            }
        }
        
        $connection.Close()
    }
    catch {
        $results.Success = $false
        $results.Details += "Failed to connect to database: $_"
    }
    
    return $results
}

function Test-LogOperations {
    param (
        [Parameter(Mandatory=$true)]
        [string[]]$Operations,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Config
    )
    
    Write-Host "[DEBUG] Testing remote log operations with config: $($Config | ConvertTo-Json)"
    
    $results = @{
        Success = $true
        Details = @()
    }
    
    foreach ($operation in $Operations) {
        try {
            switch ($operation) {
                "write" {
                    Write-Log -Message "Test log entry" -Level $Config.Level -Remote $Config.Remote
                    $results.Details += "Write operation successful"
                }
                "read" {
                    $logs = Get-Logs -Count 10 -Remote $Config.Remote
                    $results.Details += "Read operation successful"
                }
                "rotate" {
                    Rotate-Logs -Remote $Config.Remote
                    $results.Details += "Rotate operation successful"
                }
                default {
                    throw "Invalid operation: $operation"
                }
            }
        }
        catch {
            $results.Success = $false
            $results.Details += "Failed to perform $operation : $_"
        }
    }
    
    return $results
}

function Test-Category {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Category
    )
    
    Write-Host "[DEBUG] Testing category: $Category"
    
    if (-not $testCategories.ContainsKey($Category)) {
        Write-Host "Invalid category: $Category"
        return $false
    }
    
    $categoryResults = @{
        Success = $true
        Tests = @()
    }
    
    foreach ($test in $testCategories[$Category].Tests) {
        $testResults = @{
            Name = $test.Name
            Success = $false
            Details = @()
            EdgeCases = @()
        }
        
        try {
            # Run main test
            Write-Host "[DEBUG] Running test: $($test.Name)"
            Write-Host "[DEBUG] Function: $($test.Function)"
            Write-Host "[DEBUG] Parameters: $($test.Parameters | ConvertTo-Json)"
            
            $params = $test.Parameters
            $result = & $test.Function @params
            $testResults.Success = $result.Success
            $testResults.Details = $result.Details
            
            # Run edge cases
            foreach ($edgeCase in $test.EdgeCases) {
                Write-Host "[DEBUG] Running edge case with parameters: $($edgeCase | ConvertTo-Json)"
                $edgeResult = @{
                    Parameters = $edgeCase
                    Success = $false
                    Error = $null
                }
                
                try {
                    $edgeParams = $edgeCase
                    $result = & $test.Function @edgeParams
                    $edgeResult.Success = $result.Success
                }
                catch {
                    $edgeResult.Success = $true # Expected to fail
                    $edgeResult.Error = $_.Exception.Message
                }
                
                $testResults.EdgeCases += $edgeResult
            }
        }
        catch {
            Write-Host "[ERROR] Test failed: $_"
            $testResults.Success = $false
            $testResults.Details += "Test failed: $_"
        }
        
        $categoryResults.Tests += $testResults
        if (-not $testResults.Success) {
            $categoryResults.Success = $false
        }
    }
    
    return $categoryResults
}

function Export-TestReport {
    param (
        [Parameter(Mandatory=$true)]
        [string]$OutputPath
    )
    
    $testReport.EndTime = Get-Date
    $duration = $testReport.EndTime - $testReport.StartTime
    
    $report = @"
MCP System Test Report
=====================
Start Time: $($testReport.StartTime)
End Time: $($testReport.EndTime)
Duration: $($duration.TotalMinutes) minutes
Environment: $($testReport.Environment)

Test Summary
-----------
Total Tests: $($testReport.Tests.Total)
Passed: $($testReport.Tests.Passed)
Failed: $($testReport.Tests.Failed)
Skipped: $($testReport.Tests.Skipped)
Warnings: $($testReport.Tests.Warnings)

Service Status
-------------
"@
    
    foreach ($service in $testReport.Services.Keys) {
        $report += "`n$service : $($testReport.Services[$service])"
    }
    
    $report += "`n`nTest Categories`n---------------"
    foreach ($category in $testReport.Categories.Keys) {
        $report += "`n$category : $($testReport.Categories[$category])"
    }
    
    if ($testReport.Issues.Count -gt 0) {
        $report += "`n`nIssues Found`n------------"
        foreach ($issue in $testReport.Issues) {
            $report += "`n- $issue"
        }
    }
    
    if ($testReport.Certifications.Count -gt 0) {
        $report += "`n`nCertifications`n--------------"
        foreach ($cert in $testReport.Certifications) {
            $report += "`n- $cert"
        }
    }
    
    $report | Out-File -FilePath $OutputPath -Encoding UTF8
    return $OutputPath
}

# Main Test Execution
function Start-MCPComprehensiveTest {
    param (
        [string]$Environment = "codespaces",
        [string]$OutputPath = ".\test-report.txt",
        [string]$LogLevel = "INFO",
        [int]$RetryCount = 3,
        [int]$RetryDelay = 5,
        [int]$Timeout = 30,
        [hashtable]$RemoteServices = @{
            Enabled = $true
            HostPrefix = "codespaces-"
            DefaultPorts = @{
                MySQL = 3306
                Redis = 6379
                Nginx = 80
            }
        }
    )
    
    Write-Host "[DEBUG] Starting comprehensive MCP system test..."
    Write-Host "[DEBUG] Environment: $Environment"
    Write-Host "[DEBUG] Output Path: $OutputPath"
    
    $testReport.Environment = $Environment
    
    # Test each category
    foreach ($category in $testCategories.Keys) {
        Write-Host "[DEBUG] Testing category: $category"
        
        $results = Test-Category -Category $category
        $testReport.Categories[$category] = if ($results.Success) { "PASSED" } else { "FAILED" }
        
        if (-not $results.Success) {
            $testReport.Issues += "Category $category failed: $($results.Tests | Where-Object { -not $_.Success } | ForEach-Object { $_.Name })"
        }
        
        $testReport.Tests.Total += $results.Tests.Count
        $testReport.Tests.Passed += ($results.Tests | Where-Object { $_.Success }).Count
        $testReport.Tests.Failed += ($results.Tests | Where-Object { -not $_.Success }).Count
    }
    
    # Generate report
    $reportPath = Export-TestReport -OutputPath $OutputPath
    Write-Host "[DEBUG] Test report generated at: $reportPath"
    
    return $testReport
}

# Export functions
Export-ModuleMember -Function @(
    "Start-MCPComprehensiveTest",
    "Test-ServiceHealth",
    "Test-DataOperations",
    "Test-LogOperations",
    "Test-Category",
    "Export-TestReport"
) 