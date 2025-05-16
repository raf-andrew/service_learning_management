# MCP Test Module
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path $scriptPath ".." "utils"
. (Join-Path $utilsPath "logger.ps1")
. (Join-Path $utilsPath "mcp.ps1")
. (Join-Path $utilsPath "services.ps1")
. (Join-Path $utilsPath "data.ps1")
. (Join-Path $utilsPath "logs.ps1")
. (Join-Path $utilsPath "config.ps1")
. (Join-Path $utilsPath "environment.ps1")
. (Join-Path $utilsPath "github.ps1")

# Test Categories
$testCategories = @{
    "MCP" = @(
        "Initialize-MCP",
        "Invoke-MCPCommand",
        "Get-MCPStatus"
    )
    "Services" = @(
        "Start-Service",
        "Stop-Service",
        "Restart-Service",
        "Get-ServiceStatus",
        "Get-ServiceLogs",
        "Update-Service"
    )
    "Data" = @(
        "Get-Data",
        "Set-Data",
        "Remove-Data",
        "Backup-Data",
        "Restore-Data",
        "Test-DataValidation"
    )
    "Logs" = @(
        "Get-Logs",
        "Clear-Logs",
        "Backup-Logs",
        "Restore-Logs",
        "Rotate-Logs",
        "Get-LogStatus"
    )
    "Config" = @(
        "Get-Config",
        "Set-Config",
        "Remove-Config",
        "Backup-Config",
        "Restore-Config",
        "Test-Config",
        "Get-ConfigStatus"
    )
    "Environment" = @(
        "Get-EnvironmentConfig",
        "Set-EnvironmentConfig",
        "Remove-EnvironmentConfig",
        "Backup-EnvironmentConfig",
        "Restore-EnvironmentConfig",
        "Test-Environment",
        "Get-EnvironmentStatus"
    )
    "GitHub" = @(
        "Get-GitHubStatus",
        "Get-GitHubActions",
        "Start-GitHubAction",
        "Stop-GitHubAction",
        "Get-GitHubActionLogs",
        "Test-GitHubConnection"
    )
}

# Test Results
$testResults = @{
    "Passed" = 0
    "Failed" = 0
    "Skipped" = 0
    "Total" = 0
}

# Test Functions
function Test-MCPFunction {
    param (
        [Parameter(Mandatory=$true)]
        [string]$FunctionName,
        
        [Parameter(Mandatory=$true)]
        [string]$Category,
        
        [hashtable]$Parameters = @{}
    )
    
    Write-Log "Testing $Category function: $FunctionName" -Level "INFO" -Category "Test"
    
    try {
        # Execute function
        $result = & $FunctionName @Parameters
        
        if ($null -eq $result) {
            Write-Log "Test failed: $FunctionName returned null" -Level "ERROR" -Category "Test"
            $testResults.Failed++
            return $false
        }
        
        Write-Log "Test passed: $FunctionName" -Level "SUCCESS" -Category "Test"
        $testResults.Passed++
        return $true
    }
    catch {
        Write-Log "Test failed: $FunctionName - $_" -Level "ERROR" -Category "Test"
        $testResults.Failed++
        return $false
    }
}

function Test-MCPCategory {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Category
    )
    
    Write-Log "Testing $Category category..." -Level "INFO" -Category "Test"
    
    if (-not $testCategories.ContainsKey($Category)) {
        Write-Log "Invalid category: $Category" -Level "ERROR" -Category "Test"
        return $false
    }
    
    $success = $true
    
    foreach ($function in $testCategories[$Category]) {
        $testResults.Total++
        
        if (-not (Test-MCPFunction -FunctionName $function -Category $Category)) {
            $success = $false
        }
    }
    
    return $success
}

function Test-MCPAll {
    param (
        [string[]]$Categories = $testCategories.Keys
    )
    
    Write-Log "Starting MCP system tests..." -Level "INFO" -Category "Test"
    
    $success = $true
    
    foreach ($category in $Categories) {
        if (-not (Test-MCPCategory -Category $category)) {
            $success = $false
        }
    }
    
    # Print test results
    Write-Log "Test Results:" -Level "INFO" -Category "Test"
    Write-Log "  Total: $($testResults.Total)" -Level "INFO" -Category "Test"
    Write-Log "  Passed: $($testResults.Passed)" -Level "INFO" -Category "Test"
    Write-Log "  Failed: $($testResults.Failed)" -Level "INFO" -Category "Test"
    Write-Log "  Skipped: $($testResults.Skipped)" -Level "INFO" -Category "Test"
    
    return $success
}

# MCP System Tests
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# Test MCP Server
function Test-MCPServer {
    param (
        [string]$Environment = "local"
    )
    
    Write-Log "Testing MCP server..." -Level "INFO" -Category "MCP"
    
    try {
        $testResults = @{
            Status = $true
            Tests = @{}
        }
        
        # Test server initialization
        $testResults.Tests["Initialization"] = @{
            Status = $false
            Message = ""
        }
        
        if (Initialize-MCPServer -Environment $Environment -Verify:$true) {
            $testResults.Tests["Initialization"].Status = $true
            $testResults.Tests["Initialization"].Message = "Server initialized successfully"
        }
        else {
            $testResults.Tests["Initialization"].Message = "Failed to initialize server"
            $testResults.Status = $false
        }
        
        # Test server health
        $testResults.Tests["Health"] = @{
            Status = $false
            Message = ""
        }
        
        $health = Test-MCPServerHealth -Detailed:$true
        if ($health.Overall) {
            $testResults.Tests["Health"].Status = $true
            $testResults.Tests["Health"].Message = "Server health check passed"
        }
        else {
            $testResults.Tests["Health"].Message = "Server health check failed"
            $testResults.Status = $false
        }
        
        # Test service management
        $testResults.Tests["Services"] = @{
            Status = $false
            Message = ""
        }
        
        $services = @("mysql", "redis", "apache")
        $serviceStatus = $true
        foreach ($service in $services) {
            $status = Get-ServiceStatus -Service $service
            if ($status.Status -ne "Running") {
                $serviceStatus = $false
                break
            }
        }
        
        if ($serviceStatus) {
            $testResults.Tests["Services"].Status = $true
            $testResults.Tests["Services"].Message = "All services running"
        }
        else {
            $testResults.Tests["Services"].Message = "One or more services not running"
            $testResults.Status = $false
        }
        
        # Test data sources
        $testResults.Tests["DataSources"] = @{
            Status = $false
            Message = ""
        }
        
        $config = Get-EnvironmentConfig -Environment $Environment
        $dataSourceStatus = $true
        foreach ($source in $config.Data.Sources.Keys) {
            if (-not (Test-DataSource -Source $source -Environment $Environment)) {
                $dataSourceStatus = $false
                break
            }
        }
        
        if ($dataSourceStatus) {
            $testResults.Tests["DataSources"].Status = $true
            $testResults.Tests["DataSources"].Message = "All data sources accessible"
        }
        else {
            $testResults.Tests["DataSources"].Message = "One or more data sources not accessible"
            $testResults.Status = $false
        }
        
        # Test logging
        $testResults.Tests["Logging"] = @{
            Status = $false
            Message = ""
        }
        
        $logStatus = Get-LogStatus
        if ($logStatus.Healthy) {
            $testResults.Tests["Logging"].Status = $true
            $testResults.Tests["Logging"].Message = "Logging system healthy"
        }
        else {
            $testResults.Tests["Logging"].Message = "Logging system issues detected"
            $testResults.Status = $false
        }
        
        # Test configuration
        $testResults.Tests["Configuration"] = @{
            Status = $false
            Message = ""
        }
        
        $configStatus = Get-ConfigStatus
        if ($configStatus.Healthy) {
            $testResults.Tests["Configuration"].Status = $true
            $testResults.Tests["Configuration"].Message = "Configuration system healthy"
        }
        else {
            $testResults.Tests["Configuration"].Message = "Configuration system issues detected"
            $testResults.Status = $false
        }
        
        # Test self-healing
        $testResults.Tests["SelfHealing"] = @{
            Status = $false
            Message = ""
        }
        
        if (Start-SelfHeal -Environment $Environment) {
            $testResults.Tests["SelfHealing"].Status = $true
            $testResults.Tests["SelfHealing"].Message = "Self-healing system functional"
        }
        else {
            $testResults.Tests["SelfHealing"].Message = "Self-healing system issues detected"
            $testResults.Status = $false
        }
        
        # Test health monitoring
        $testResults.Tests["HealthMonitoring"] = @{
            Status = $false
            Message = ""
        }
        
        if (Start-HealthMonitor -Environment $Environment) {
            $testResults.Tests["HealthMonitoring"].Status = $true
            $testResults.Tests["HealthMonitoring"].Message = "Health monitoring system functional"
            Stop-HealthMonitor
        }
        else {
            $testResults.Tests["HealthMonitoring"].Message = "Health monitoring system issues detected"
            $testResults.Status = $false
        }
        
        # Test developer utilities
        $testResults.Tests["DeveloperUtilities"] = @{
            Status = $false
            Message = ""
        }
        
        $systemStatus = Get-SystemStatus -Detailed:$true
        if ($systemStatus) {
            $testResults.Tests["DeveloperUtilities"].Status = $true
            $testResults.Tests["DeveloperUtilities"].Message = "Developer utilities functional"
        }
        else {
            $testResults.Tests["DeveloperUtilities"].Message = "Developer utilities issues detected"
            $testResults.Status = $false
        }
        
        # Export test results
        $reportPath = Join-Path -Path $mcpPath -ChildPath "logs" "test-report.json"
        $reportJson = ConvertTo-Json -InputObject $testResults -Depth 10
        Set-Content -Path $reportPath -Value $reportJson
        
        if ($testResults.Status) {
            Write-Log "All MCP tests passed" -Level "SUCCESS" -Category "MCP"
        }
        else {
            Write-Log "Some MCP tests failed" -Level "ERROR" -Category "MCP"
        }
        
        return $testResults
    }
    catch {
        Write-Log "Error testing MCP server: $_" -Level "ERROR" -Category "MCP"
        return @{
            Status = $false
            Tests = @{
                "Error" = @{
                    Status = $false
                    Message = $_.Exception.Message
                }
            }
        }
    }
}

# Run tests if script is executed directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    $testResults = Test-MCPServer -Environment "local"
    if (-not $testResults.Status) {
        Write-Error "MCP tests failed"
        exit 1
    }
    exit 0
}

# Export functions
Export-ModuleMember -Function @(
    "Test-MCPFunction",
    "Test-MCPCategory",
    "Test-MCPAll",
    "Test-MCPServer"
) 