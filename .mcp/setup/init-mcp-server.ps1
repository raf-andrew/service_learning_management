# MCP Server Initialization Script
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# MCP Server Configuration
$mcpServerConfig = @{
    Status = "Stopped"
    LastStartTime = $null
    LastHealthCheck = $null
    HealthStatus = @{
        Services = @{}
        DataSources = @{}
        Logs = @{}
        Config = @{}
    }
    AutoHeal = $true
    HealthCheckInterval = 60  # seconds
    MaxRetries = 3
    RetryDelay = 5  # seconds
}

# Initialize MCP Server
function Initialize-MCPServer {
    param (
        [string]$Environment = "local",
        [switch]$Force,
        [switch]$Verify,
        [switch]$AutoHeal
    )
    
    Write-Log "Initializing MCP Server..." -Level "INFO" -Category "MCP"
    
    try {
        # Check if server is already running
        if ($mcpServerConfig.Status -eq "Running" -and -not $Force) {
            Write-Log "MCP Server is already running" -Level "WARNING" -Category "MCP"
            return $true
        }
        
        # Set configuration
        $mcpServerConfig.AutoHeal = $AutoHeal
        $mcpServerConfig.Status = "Initializing"
        
        # Initialize MCP system
        if (-not (Initialize-MCPSystem -Environment $Environment -Verify:$Verify -AutoHeal:$AutoHeal)) {
            Write-Log "Failed to initialize MCP system" -Level "ERROR" -Category "MCP"
            $mcpServerConfig.Status = "Failed"
            return $false
        }
        
        # Start required services
        $services = @("mysql", "redis", "apache")
        foreach ($service in $services) {
            if (-not (Start-Service -Service $service -Verify:$Verify -AutoHeal:$AutoHeal)) {
                Write-Log "Failed to start service: $service" -Level "ERROR" -Category "MCP"
                if (-not $AutoHeal) {
                    $mcpServerConfig.Status = "Failed"
                    return $false
                }
            }
        }
        
        # Verify data sources
        $config = Get-EnvironmentConfig -Environment $Environment
        foreach ($source in $config.Data.Sources.Keys) {
            if (-not (Test-DataSource -Source $source -Environment $Environment)) {
                Write-Log "Data source check failed: $source" -Level "ERROR" -Category "MCP"
                if (-not $AutoHeal) {
                    $mcpServerConfig.Status = "Failed"
                    return $false
                }
            }
        }
        
        # Set server status
        $mcpServerConfig.Status = "Running"
        $mcpServerConfig.LastStartTime = Get-Date
        $mcpServerConfig.LastHealthCheck = Get-Date
        
        Write-Log "MCP Server initialized successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error initializing MCP Server: $_" -Level "ERROR" -Category "MCP"
        $mcpServerConfig.Status = "Failed"
        return $false
    }
}

# Check MCP Server Health
function Test-MCPServerHealth {
    param (
        [switch]$Detailed
    )
    
    Write-Log "Checking MCP Server health..." -Level "INFO" -Category "MCP"
    
    try {
        $healthStatus = @{
            Overall = $true
            Services = @{}
            DataSources = @{}
            Logs = @{}
            Config = @{}
            LastCheck = Get-Date
        }
        
        # Check services
        $services = @("mysql", "redis", "apache")
        foreach ($service in $services) {
            $status = Get-ServiceStatus -Service $service
            $healthStatus.Services[$service] = $status
            
            if ($status.Status -ne "Running") {
                $healthStatus.Overall = $false
                Write-Log "Service health check failed: $service" -Level "ERROR" -Category "MCP"
                
                if ($mcpServerConfig.AutoHeal) {
                    Write-Log "Attempting to heal service: $service" -Level "INFO" -Category "MCP"
                    if (-not (Start-Service -Service $service -AutoHeal:$true)) {
                        Write-Log "Failed to heal service: $service" -Level "ERROR" -Category "MCP"
                    }
                }
            }
        }
        
        # Check data sources
        $config = Get-EnvironmentConfig -Environment "local"
        foreach ($source in $config.Data.Sources.Keys) {
            $status = Test-DataSource -Source $source
            $healthStatus.DataSources[$source] = $status
            
            if (-not $status) {
                $healthStatus.Overall = $false
                Write-Log "Data source health check failed: $source" -Level "ERROR" -Category "MCP"
                
                if ($mcpServerConfig.AutoHeal) {
                    Write-Log "Attempting to heal data source: $source" -Level "INFO" -Category "MCP"
                    # Add data source healing logic here
                }
            }
        }
        
        # Check logs
        $logStatus = Get-LogStatus
        $healthStatus.Logs = $logStatus
        
        if (-not $logStatus.Healthy) {
            $healthStatus.Overall = $false
            Write-Log "Log health check failed" -Level "ERROR" -Category "MCP"
            
            if ($mcpServerConfig.AutoHeal) {
                Write-Log "Attempting to heal logs" -Level "INFO" -Category "MCP"
                # Add log healing logic here
            }
        }
        
        # Check configuration
        $configStatus = Get-ConfigStatus
        $healthStatus.Config = $configStatus
        
        if (-not $configStatus.Healthy) {
            $healthStatus.Overall = $false
            Write-Log "Config health check failed" -Level "ERROR" -Category "MCP"
            
            if ($mcpServerConfig.AutoHeal) {
                Write-Log "Attempting to heal configuration" -Level "INFO" -Category "MCP"
                # Add config healing logic here
            }
        }
        
        # Update server config
        $mcpServerConfig.LastHealthCheck = Get-Date
        $mcpServerConfig.HealthStatus = $healthStatus
        
        if ($Detailed) {
            return $healthStatus
        }
        
        return $healthStatus.Overall
    }
    catch {
        Write-Log "Error checking MCP Server health: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Start MCP Server
function Start-MCPServer {
    param (
        [string]$Environment = "local",
        [switch]$Force,
        [switch]$Verify,
        [switch]$AutoHeal
    )
    
    Write-Log "Starting MCP Server..." -Level "INFO" -Category "MCP"
    
    try {
        # Initialize server if not already running
        if ($mcpServerConfig.Status -ne "Running" -or $Force) {
            if (-not (Initialize-MCPServer -Environment $Environment -Force:$Force -Verify:$Verify -AutoHeal:$AutoHeal)) {
                return $false
            }
        }
        
        # Start health check loop
        $healthCheckJob = Start-Job -ScriptBlock {
            param($mcpPath)
            
            $utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
            . (Join-Path -Path $utilsPath -ChildPath "init.ps1")
            
            while ($true) {
                Test-MCPServerHealth
                Start-Sleep -Seconds 60
            }
        } -ArgumentList $mcpPath
        
        Write-Log "MCP Server started successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error starting MCP Server: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Stop MCP Server
function Stop-MCPServer {
    param (
        [switch]$Force
    )
    
    Write-Log "Stopping MCP Server..." -Level "INFO" -Category "MCP"
    
    try {
        # Stop health check job
        Get-Job | Where-Object { $_.Name -eq "MCPHealthCheck" } | Stop-Job
        
        # Stop services
        $services = @("apache", "redis", "mysql")
        foreach ($service in $services) {
            if (-not (Stop-Service -Service $service -Force:$Force)) {
                Write-Log "Failed to stop service: $service" -Level "ERROR" -Category "MCP"
                if (-not $Force) {
                    return $false
                }
            }
        }
        
        $mcpServerConfig.Status = "Stopped"
        Write-Log "MCP Server stopped successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error stopping MCP Server: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Get MCP Server Status
function Get-MCPServerStatus {
    param (
        [switch]$Detailed
    )
    
    $status = @{
        Status = $mcpServerConfig.Status
        LastStartTime = $mcpServerConfig.LastStartTime
        LastHealthCheck = $mcpServerConfig.LastHealthCheck
        AutoHeal = $mcpServerConfig.AutoHeal
    }
    
    if ($Detailed) {
        $status.HealthStatus = $mcpServerConfig.HealthStatus
    }
    
    return $status
}

# Export functions
Export-ModuleMember -Function @(
    "Initialize-MCPServer",
    "Test-MCPServerHealth",
    "Start-MCPServer",
    "Stop-MCPServer",
    "Get-MCPServerStatus"
) 