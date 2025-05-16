# Core MCP Functionality
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$utilsPath = Join-Path -Path $scriptPath -ChildPath "utils"

# Core MCP Functions
function Initialize-MCP {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Environment,
        
        [Parameter(Mandatory=$false)]
        [bool]$Verify = $false
    )
    
    try {
        # Initialize environment
        Set-Environment -Name $Environment
        
        # Initialize services
        Initialize-Services
        
        # Initialize logging
        Initialize-Logging
        
        # Verify if requested
        if ($Verify) {
            Test-MCPHealth
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to initialize MCP: $_"
        return $false
    }
}

function Test-MCPHealth {
    param (
        [Parameter(Mandatory=$false)]
        [bool]$Detailed = $false,
        
        [Parameter(Mandatory=$false)]
        [string[]]$Services = @()
    )
    
    $health = @{
        Status = "Healthy"
        Services = @{}
        Issues = @()
    }
    
    try {
        # Check core services
        $coreServices = @("mysql", "redis", "nginx")
        if ($Services.Count -gt 0) {
            $coreServices = $Services
        }
        
        foreach ($service in $coreServices) {
            $serviceHealth = Test-ServiceHealth -Service $service
            $health.Services[$service] = $serviceHealth
            
            if (-not $serviceHealth.Healthy) {
                $health.Status = "Unhealthy"
                $health.Issues += "Service $service is unhealthy: $($serviceHealth.Issue)"
            }
        }
        
        # Additional checks if detailed
        if ($Detailed) {
            # Check disk space
            $diskSpace = Get-DiskSpace
            if ($diskSpace.FreeSpace -lt 10GB) {
                $health.Status = "Warning"
                $health.Issues += "Low disk space: $($diskSpace.FreeSpace)GB free"
            }
            
            # Check memory usage
            $memory = Get-MemoryUsage
            if ($memory.UsagePercent -gt 90) {
                $health.Status = "Warning"
                $health.Issues += "High memory usage: $($memory.UsagePercent)%"
            }
        }
        
        return $health
    }
    catch {
        Write-Error "Failed to check MCP health: $_"
        return @{
            Status = "Error"
            Services = @{}
            Issues = @("Failed to check health: $_")
        }
    }
}

function Start-Service {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    try {
        # Start the service
        $result = Invoke-ServiceAction -Service $Service -Action "start"
        
        # Verify service is running
        $status = Get-ServiceStatus -Service $Service
        if ($status.State -ne "Running") {
            throw "Service failed to start: $($status.Error)"
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to start service $Service : $_"
        return $false
    }
}

function Stop-Service {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    try {
        # Stop the service
        $result = Invoke-ServiceAction -Service $Service -Action "stop"
        
        # Verify service is stopped
        $status = Get-ServiceStatus -Service $Service
        if ($status.State -ne "Stopped") {
            throw "Service failed to stop: $($status.Error)"
        }
        
        return $true
    }
    catch {
        Write-Error "Failed to stop service $Service : $_"
        return $false
    }
}

function Restart-Service {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    try {
        # Stop the service
        Stop-Service -Service $Service
        
        # Start the service
        Start-Service -Service $Service
        
        return $true
    }
    catch {
        Write-Error "Failed to restart service $Service : $_"
        return $false
    }
}

function Get-ServiceStatus {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    try {
        # Get service status
        $status = Invoke-ServiceAction -Service $Service -Action "status"
        
        return @{
            State = $status.State
            Error = $status.Error
            Details = $status.Details
        }
    }
    catch {
        Write-Error "Failed to get service status for $Service : $_"
        return @{
            State = "Error"
            Error = $_.Exception.Message
            Details = @{}
        }
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Initialize-MCP",
    "Test-MCPHealth",
    "Start-Service",
    "Stop-Service",
    "Restart-Service",
    "Get-ServiceStatus"
) 