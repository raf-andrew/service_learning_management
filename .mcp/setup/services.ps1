# MCP Service Management
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath "environment.ps1")

# Service Management Configuration
$serviceConfig = @{
    HealthCheck = @{
        Interval = 60
        Timeout = 5
        RetryCount = 3
        RetryDelay = 5
    }
    Commands = @{
        MySQL = @{
            Start = "net start MySQL80"
            Stop = "net stop MySQL80"
            Status = "sc query MySQL80"
            Health = "mysqladmin ping -h {host} -P {port} -u {user} -p{password}"
        }
        Redis = @{
            Start = "net start Redis"
            Stop = "net stop Redis"
            Status = "sc query Redis"
            Health = "redis-cli -h {host} -p {port} ping"
        }
        Apache = @{
            Start = "net start Apache2.4"
            Stop = "net stop Apache2.4"
            Status = "sc query Apache2.4"
            Health = "curl -s -o /dev/null -w '%{http_code}' http://{host}:{port}/"
        }
    }
    Metrics = @{
        CPU = @{
            Threshold = 80
            Interval = 60
        }
        Memory = @{
            Threshold = 80
            Interval = 60
        }
        Disk = @{
            Threshold = 80
            Interval = 300
        }
        Network = @{
            Threshold = 80
            Interval = 60
        }
    }
}

# Get service status
function Get-ServiceStatus {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    
    Write-Log "Getting status for service: $Service" -Level "INFO" -Category "Services"
    
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) {
        return $null
    }
    
    $serviceConfig = $config.Services[$Service]
    if ($null -eq $serviceConfig) {
        Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Services"
        return $null
    }
    
    try {
        $command = $serviceConfig.Commands.Status
        $result = Invoke-Expression $command
        
        return @{
            Name = $Service
            Status = $result.State
            StartTime = $result.StartTime
            Config = $serviceConfig
        }
    }
    catch {
        Write-Log "Error getting service status: $_" -Level "ERROR" -Category "Services"
        return $null
    }
}

# Check service health
function Test-ServiceHealth {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    
    Write-Log "Testing health for service: $Service" -Level "INFO" -Category "Services"
    
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) {
        return $null
    }
    
    $serviceConfig = $config.Services[$Service]
    if ($null -eq $serviceConfig) {
        Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Services"
        return $null
    }
    
    $healthCheck = $serviceConfig.HealthCheck
    if (-not $healthCheck.Enabled) {
        return @{
            IsHealthy = $true
            Message = "Health check disabled"
        }
    }
    
    $attempt = 0
    $success = $false
    $error = $null
    
    while (-not $success -and $attempt -lt $healthCheck.RetryCount) {
        $attempt++
        
        try {
            $command = $serviceConfig.Commands.Health
            $command = $command -replace "{host}", $serviceConfig.Host
            $command = $command -replace "{port}", $serviceConfig.Port
            $command = $command -replace "{user}", $serviceConfig.User
            $command = $command -replace "{password}", $serviceConfig.Password
            
            $result = Invoke-Expression $command
            
            if ($result -eq "PONG" -or $result -eq "200") {
                $success = $true
            }
        }
        catch {
            $error = $_.Exception.Message
        }
        
        if (-not $success -and $attempt -lt $healthCheck.RetryCount) {
            Start-Sleep -Seconds $healthCheck.Interval
        }
    }
    
    return @{
        IsHealthy = $success
        Message = if ($success) { "Service is healthy" } else { "Service health check failed: $error" }
    }
}

# Start service healing
function Start-ServiceHealing {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    
    Write-Log "Starting healing for service: $Service" -Level "INFO" -Category "Services"
    
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) {
        return $null
    }
    
    $serviceConfig = $config.Services[$Service]
    if ($null -eq $serviceConfig) {
        Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Services"
        return $null
    }
    
    try {
        # Stop service
        $stopCommand = $serviceConfig.Commands.Stop
        Invoke-Expression $stopCommand
        
        # Wait for service to stop
        Start-Sleep -Seconds 5
        
        # Start service
        $startCommand = $serviceConfig.Commands.Start
        Invoke-Expression $startCommand
        
        # Wait for service to start
        Start-Sleep -Seconds 10
        
        # Verify service health
        $healthCheck = Test-ServiceHealth -Service $Service -Environment $Environment
        if (-not $healthCheck.IsHealthy) {
            return @{
                Success = $false
                Message = "Service still unhealthy after restart: $($healthCheck.Message)"
            }
        }
        
        return @{
            Success = $true
            Message = "Service healed successfully"
        }
    }
    catch {
        Write-Log "Error healing service: $_" -Level "ERROR" -Category "Services"
        return @{
            Success = $false
            Message = "Error healing service: $_"
        }
    }
}

# Get service metrics
function Get-ServiceMetrics {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [string]$Environment,
        
        [string[]]$Metrics = @("CPU", "Memory", "Disk", "Network")
    )
    
    Write-Log "Getting metrics for service: $Service" -Level "INFO" -Category "Services"
    
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) {
        return $null
    }
    
    $serviceConfig = $config.Services[$Service]
    if ($null -eq $serviceConfig) {
        Write-Log "Service configuration not found: $Service" -Level "ERROR" -Category "Services"
        return $null
    }
    
    $result = @{
        Service = $Service
        Timestamp = Get-Date -Format "o"
        Metrics = @{}
    }
    
    foreach ($metric in $Metrics) {
        if ($serviceConfig.Metrics.ContainsKey($metric)) {
            $metricConfig = $serviceConfig.Metrics[$metric]
            
            try {
                switch ($metric) {
                    "CPU" {
                        $cpu = Get-Counter "\Processor(_Total)\% Processor Time"
                        $result.Metrics[$metric] = @{
                            Value = $cpu.CounterSamples.CookedValue
                            Threshold = $metricConfig.Threshold
                            Unit = "%"
                        }
                    }
                    "Memory" {
                        $memory = Get-Counter "\Memory\% Committed Bytes In Use"
                        $result.Metrics[$metric] = @{
                            Value = $memory.CounterSamples.CookedValue
                            Threshold = $metricConfig.Threshold
                            Unit = "%"
                        }
                    }
                    "Disk" {
                        $disk = Get-Counter "\LogicalDisk(_Total)\% Free Space"
                        $result.Metrics[$metric] = @{
                            Value = 100 - $disk.CounterSamples.CookedValue
                            Threshold = $metricConfig.Threshold
                            Unit = "%"
                        }
                    }
                    "Network" {
                        $network = Get-Counter "\Network Interface(*)\Bytes Total/sec"
                        $result.Metrics[$metric] = @{
                            Value = $network.CounterSamples.CookedValue
                            Threshold = $metricConfig.Threshold
                            Unit = "B/s"
                        }
                    }
                }
            }
            catch {
                Write-Log "Error getting metric '$metric': $_" -Level "ERROR" -Category "Services"
            }
        }
    }
    
    return $result
}

# Export functions
Export-ModuleMember -Function @(
    "Get-ServiceStatus",
    "Test-ServiceHealth",
    "Start-ServiceHealing",
    "Get-ServiceMetrics"
) 