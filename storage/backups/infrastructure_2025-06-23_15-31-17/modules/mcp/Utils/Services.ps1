# MCP Services Utility
# Provides service management functionality for the MCP system

function Test-ServiceHealth {
    param([string]$Service)
    
    try {
        $service = Get-Service -Name $Service -ErrorAction SilentlyContinue
        
        if (-not $service) {
            return @{
                Healthy = $false
                Status = "Not Found"
                Error = "Service not found"
                Running = $false
                StartType = "Unknown"
            }
        }
        
        $health = @{
            Healthy = $service.Status -eq "Running"
            Status = $service.Status
            Running = $service.Status -eq "Running"
            StartType = $service.StartType
            Error = $null
        }
        
        # Additional health checks based on service type
        switch ($Service.ToLower()) {
            "mysql" {
                $health.Healthy = $health.Healthy -and (Test-MySQLConnection)
            }
            "redis" {
                $health.Healthy = $health.Healthy -and (Test-RedisConnection)
            }
            "apache" {
                $health.Healthy = $health.Healthy -and (Test-ApacheConnection)
            }
        }
        
        return $health
        
    } catch {
        return @{
            Healthy = $false
            Status = "Error"
            Error = $_.Exception.Message
            Running = $false
            StartType = "Unknown"
        }
    }
}

function Start-Service {
    param([string]$Service)
    
    try {
        $service = Get-Service -Name $Service -ErrorAction SilentlyContinue
        
        if (-not $service) {
            return @{ Success = $false; Error = "Service not found" }
        }
        
        if ($service.Status -eq "Running") {
            return @{ Success = $true; Message = "Service already running" }
        }
        
        Start-Service -Name $Service -ErrorAction Stop
        
        # Wait for service to start
        $service.WaitForStatus("Running", (New-TimeSpan -Seconds 30))
        
        return @{ Success = $true; Message = "Service started successfully" }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Stop-Service {
    param([string]$Service)
    
    try {
        $service = Get-Service -Name $Service -ErrorAction SilentlyContinue
        
        if (-not $service) {
            return @{ Success = $false; Error = "Service not found" }
        }
        
        if ($service.Status -eq "Stopped") {
            return @{ Success = $true; Message = "Service already stopped" }
        }
        
        Stop-Service -Name $Service -ErrorAction Stop
        
        # Wait for service to stop
        $service.WaitForStatus("Stopped", (New-TimeSpan -Seconds 30))
        
        return @{ Success = $true; Message = "Service stopped successfully" }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Restart-Service {
    param([string]$Service)
    
    try {
        $service = Get-Service -Name $Service -ErrorAction SilentlyContinue
        
        if (-not $service) {
            return @{ Success = $false; Error = "Service not found" }
        }
        
        Restart-Service -Name $Service -ErrorAction Stop
        
        # Wait for service to restart
        $service.WaitForStatus("Running", (New-TimeSpan -Seconds 60))
        
        return @{ Success = $true; Message = "Service restarted successfully" }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Get-ServiceStatus {
    param([string]$Service)
    
    try {
        $service = Get-Service -Name $Service -ErrorAction SilentlyContinue
        
        if (-not $service) {
            return @{ Status = "Not Found"; Running = $false }
        }
        
        return @{
            Status = $service.Status
            Running = $service.Status -eq "Running"
            StartType = $service.StartType
            DisplayName = $service.DisplayName
            ServiceName = $service.ServiceName
        }
        
    } catch {
        return @{ Status = "Error"; Running = $false; Error = $_.Exception.Message }
    }
}

function Initialize-Services {
    param(
        [string]$Environment = "local",
        [switch]$Force
    )
    
    try {
        $services = @("mysql", "redis", "apache")
        $results = @{}
        
        foreach ($service in $services) {
            $health = Test-ServiceHealth -Service $service
            
            if (-not $health.Healthy) {
                if ($Force) {
                    $result = Start-Service -Service $service
                    $results[$service] = $result
                } else {
                    $results[$service] = @{ Success = $false; Error = "Service unhealthy" }
                }
            } else {
                $results[$service] = @{ Success = $true; Message = "Service healthy" }
            }
        }
        
        $overallSuccess = $results.Values | Where-Object { $_.Success } | Measure-Object | Select-Object -ExpandProperty Count
        $overallSuccess = $overallSuccess -eq $services.Count
        
        return @{
            Success = $overallSuccess
            Services = $results
            Error = if (-not $overallSuccess) { "Some services failed to initialize" } else { $null }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Start-Services {
    param([string]$Environment = "local")
    
    try {
        $services = @("mysql", "redis", "apache")
        $results = @{}
        
        foreach ($service in $services) {
            $results[$service] = Start-Service -Service $service
        }
        
        $overallSuccess = $results.Values | Where-Object { $_.Success } | Measure-Object | Select-Object -ExpandProperty Count
        $overallSuccess = $overallSuccess -eq $services.Count
        
        return @{
            Success = $overallSuccess
            Services = $results
            Error = if (-not $overallSuccess) { "Some services failed to start" } else { $null }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

function Stop-Services {
    param([string]$Environment = "local")
    
    try {
        $services = @("apache", "redis", "mysql") # Reverse order for graceful shutdown
        $results = @{}
        
        foreach ($service in $services) {
            $results[$service] = Stop-Service -Service $service
        }
        
        $overallSuccess = $results.Values | Where-Object { $_.Success } | Measure-Object | Select-Object -ExpandProperty Count
        $overallSuccess = $overallSuccess -eq $services.Count
        
        return @{
            Success = $overallSuccess
            Services = $results
            Error = if (-not $overallSuccess) { "Some services failed to stop" } else { $null }
        }
        
    } catch {
        return @{ Success = $false; Error = $_.Exception.Message }
    }
}

# Service-specific health checks
function Test-MySQLConnection {
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $tcpClient.ConnectAsync("localhost", 3306).Wait(1000)
        $isConnected = $tcpClient.Connected
        $tcpClient.Close()
        return $isConnected
    } catch {
        return $false
    }
}

function Test-RedisConnection {
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $tcpClient.ConnectAsync("localhost", 6379).Wait(1000)
        $isConnected = $tcpClient.Connected
        $tcpClient.Close()
        return $isConnected
    } catch {
        return $false
    }
}

function Test-ApacheConnection {
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $tcpClient.ConnectAsync("localhost", 80).Wait(1000)
        $isConnected = $tcpClient.Connected
        $tcpClient.Close()
        return $isConnected
    } catch {
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Test-ServiceHealth",
    "Start-Service",
    "Stop-Service",
    "Restart-Service",
    "Get-ServiceStatus",
    "Initialize-Services",
    "Start-Services",
    "Stop-Services"
) 