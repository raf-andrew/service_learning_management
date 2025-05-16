# MCP Remote Service Test Module
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path $scriptPath ".." "utils"
. (Join-Path $utilsPath "logger.ps1")
. (Join-Path $utilsPath "mcp.ps1")

# Test Categories
$testCategories = @{
    "Environment" = @(
        "Test-EnvironmentType"
    )
    "Services" = @(
        "Test-ServiceHealth",
        "Start-ServiceHealing"
    )
    "Commands" = @(
        "Invoke-MCPCommand"
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
function Test-EnvironmentType {
    Write-Log "Testing environment type detection..." -Level "INFO" -Category "Test"
    
    try {
        $envType = Test-EnvironmentType
        if ($null -eq $envType) {
            Write-Log "Environment type detection failed" -Level "ERROR" -Category "Test"
            return $false
        }
        
        Write-Log "Detected environment type: $envType" -Level "INFO" -Category "Test"
        return $true
    }
    catch {
        Write-Log "Error testing environment type: $_" -Level "ERROR" -Category "Test"
        return $false
    }
}

function Test-ServiceHealth {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    Write-Log "Testing service health for: $Service" -Level "INFO" -Category "Test"
    
    try {
        $serviceConfig = $mcpConfig.Services[$Service]
        if ($null -eq $serviceConfig) {
            Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Test"
            return $false
        }
        
        $healthCheck = Test-ServiceHealth -Service $Service -Config $serviceConfig
        if (-not $healthCheck.IsHealthy) {
            Write-Log "Service health check failed: $($healthCheck.Message)" -Level "ERROR" -Category "Test"
            return $false
        }
        
        Write-Log "Service health check passed" -Level "INFO" -Category "Test"
        return $true
    }
    catch {
        Write-Log "Error testing service health: $_" -Level "ERROR" -Category "Test"
        return $false
    }
}

function Test-ServiceHealing {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    Write-Log "Testing service healing for: $Service" -Level "INFO" -Category "Test"
    
    try {
        $serviceConfig = $mcpConfig.Services[$Service]
        if ($null -eq $serviceConfig) {
            Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Test"
            return $false
        }
        
        $healResult = Start-ServiceHealing -Service $Service -Config $serviceConfig
        if (-not $healResult.Success) {
            Write-Log "Service healing failed: $($healResult.Message)" -Level "ERROR" -Category "Test"
            return $false
        }
        
        Write-Log "Service healing passed" -Level "INFO" -Category "Test"
        return $true
    }
    catch {
        Write-Log "Error testing service healing: $_" -Level "ERROR" -Category "Test"
        return $false
    }
}

function Test-MCPCommand {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Command,
        
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    Write-Log "Testing MCP command: $Command on service: $Service" -Level "INFO" -Category "Test"
    
    try {
        $result = Invoke-MCPCommand -Command $Command -Service $Service
        if ($null -eq $result) {
            Write-Log "MCP command failed" -Level "ERROR" -Category "Test"
            return $false
        }
        
        Write-Log "MCP command passed" -Level "INFO" -Category "Test"
        return $true
    }
    catch {
        Write-Log "Error testing MCP command: $_" -Level "ERROR" -Category "Test"
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
        
        if (-not (& $function)) {
            $success = $false
            $testResults.Failed++
        } else {
            $testResults.Passed++
        }
    }
    
    return $success
}

function Test-MCPAll {
    param (
        [string[]]$Categories = $testCategories.Keys
    )
    
    Write-Log "Starting MCP remote service tests..." -Level "INFO" -Category "Test"
    
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

# Export functions
Export-ModuleMember -Function @(
    "Test-EnvironmentType",
    "Test-ServiceHealth",
    "Test-ServiceHealing",
    "Test-MCPCommand",
    "Test-MCPCategory",
    "Test-MCPAll"
) 