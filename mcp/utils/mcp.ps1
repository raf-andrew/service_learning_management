# MCP Utility Module
$ErrorActionPreference = "Stop"

# Get script path
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path

# Import required modules
# . (Join-Path $scriptPath "logger.ps1")
# . (Join-Path $scriptPath "environment.ps1")
# . (Join-Path $scriptPath "github.ps1")
# . (Join-Path $scriptPath "services.ps1")
# . (Join-Path $scriptPath "data.ps1")
# . (Join-Path $scriptPath "logs.ps1")
# . (Join-Path $scriptPath "config.ps1")

# MCP Configuration
$mcpConfig = @{
    API = "https://api.example.com"
    Version = "1.0.0"
    Timeout = 30
    RetryCount = 3
    RetryDelay = 5
    LogLevel = "INFO"
    Environment = @{
        Type = "local"  # local or remote
        AutoDetect = $true
        Fallback = "local"
    }
    Services = @{
        MySQL = @{
            Host = "localhost"
            Port = 3306
            User = "root"
            Password = ""
            Database = "mcp"
            HealthCheck = @{
                Enabled = $true
                Interval = 60
                Timeout = 5
                RetryCount = 3
            }
        }
        Redis = @{
            Host = "localhost"
            Port = 6379
            Password = ""
            HealthCheck = @{
                Enabled = $true
                Interval = 60
                Timeout = 5
                RetryCount = 3
            }
        }
        Apache = @{
            Host = "localhost"
            Port = 80
            SSL = $false
            HealthCheck = @{
                Enabled = $true
                Interval = 60
                Timeout = 5
                RetryCount = 3
            }
        }
    }
    Data = @{
        Sources = @{
            Users = @{
                Type = "MySQL"
                Table = "users"
                Fields = @("id", "username", "email", "status")
                HealthCheck = @{
                    Enabled = $true
                    Interval = 300
                    Timeout = 10
                }
            }
            Logs = @{
                Type = "Redis"
                Key = "logs"
                Expiry = 86400
                HealthCheck = @{
                    Enabled = $true
                    Interval = 300
                    Timeout = 10
                }
            }
            Config = @{
                Type = "File"
                Path = "config.json"
                Format = "JSON"
                HealthCheck = @{
                    Enabled = $true
                    Interval = 300
                    Timeout = 10
                }
            }
        }
        Validation = @{
            Users = @{
                Username = "^[a-zA-Z0-9_]{3,20}$"
                Email = "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                Status = "^(active|inactive|suspended)$"
            }
        }
    }
    Logs = @{
        General = @{
            Path = "logs/general"
            Level = "INFO"
            Rotation = @{
                Size = "10MB"
                Count = 10
            }
            RemoteSync = @{
                Enabled = $true
                Interval = 300
            }
        }
        Services = @{
            Path = "logs/services"
            Level = "INFO"
            Rotation = @{
                Size = "10MB"
                Count = 10
            }
            RemoteSync = @{
                Enabled = $true
                Interval = 300
            }
        }
        Data = @{
            Path = "logs/data"
            Level = "INFO"
            Rotation = @{
                Size = "10MB"
                Count = 10
            }
            RemoteSync = @{
                Enabled = $true
                Interval = 300
            }
        }
        Config = @{
            Path = "logs/config"
            Level = "INFO"
            Rotation = @{
                Size = "10MB"
                Count = 10
            }
            RemoteSync = @{
                Enabled = $true
                Interval = 300
            }
        }
        Audit = @{
            Path = "logs/audit"
            Level = "INFO"
            Rotation = @{
                Size = "10MB"
                Count = 10
            }
            RemoteSync = @{
                Enabled = $true
                Interval = 300
            }
        }
    }
    Config = @{
        Path = "config"
        Backup = @{
            Enabled = $true
            Path = "backups"
            Retention = 30
            RemoteSync = @{
                Enabled = $true
                Interval = 3600
            }
        }
        Validation = @{
            Schema = "config/schema.json"
            Rules = "config/rules.json"
        }
        Deployment = @{
            Strategy = "rolling"
            Rollback = $true
            Verification = $true
        }
    }
    Developer = @{
        Tools = @{
            LogTail = @{
                Enabled = $true
                MaxLines = 1000
                Follow = $true
            }
            Audit = @{
                Enabled = $true
                Retention = 90
            }
            Monitoring = @{
                Enabled = $true
                Metrics = @("cpu", "memory", "disk", "network")
                Interval = 60
            }
        }
    }
}

# Initialize MCP
function Initialize-MCP {
    param (
        [string]$Environment = "local",
        [switch]$Verify,
        [switch]$AutoHeal
    )
    
    Write-Log "Initializing MCP..." -Level "INFO" -Category "MCP"
    
    try {
        # Load environment configuration
        $envConfig = Get-EnvironmentConfig -Environment $Environment
        if ($null -eq $envConfig) {
            Write-Log "Failed to load environment configuration" -Level "ERROR" -Category "MCP"
            return $false
        }
        
        # Update MCP configuration
        $mcpConfig.API = $envConfig.API
        $mcpConfig.Services = $envConfig.Services
        $mcpConfig.Data = $envConfig.Data
        $mcpConfig.Logs = $envConfig.Logs
        $mcpConfig.Config = $envConfig.Config
        
        # Auto-detect environment if enabled
        if ($mcpConfig.Environment.AutoDetect) {
            $detectedEnv = Test-EnvironmentType
            if ($detectedEnv -ne $Environment) {
                Write-Log "Auto-detected environment: $detectedEnv" -Level "INFO" -Category "MCP"
                $Environment = $detectedEnv
            }
        }
        
        # Verify services
        if ($Verify) {
            $services = @("MySQL", "Redis", "Apache")
            foreach ($service in $services) {
                $serviceConfig = $mcpConfig.Services[$service]
                try {
                    $healthCheck = Test-ServiceHealth -Service $service -Config $serviceConfig
                    if (-not $healthCheck.IsHealthy) {
                        Write-Log "Service '$service' health check failed: $($healthCheck.Message)" -Level "ERROR" -Category "MCP"
                        if ($AutoHeal) {
                            Write-Log "Attempting to heal service '$service'..." -Level "INFO" -Category "MCP"
                            $healResult = Start-ServiceHealing -Service $service -Config $serviceConfig
                            if (-not $healResult.Success) {
                                Write-Log "Failed to heal service '$service': $($healResult.Message)" -Level "ERROR" -Category "MCP"
                                return $false
                            }
                        } else {
                            return $false
                        }
                    }
                }
                catch {
                    Write-Log "Error checking service '$service': $_" -Level "ERROR" -Category "MCP"
                    if ($AutoHeal) {
                        Write-Log "Attempting to heal service '$service'..." -Level "INFO" -Category "MCP"
                        $healResult = Start-ServiceHealing -Service $service -Config $serviceConfig
                        if (-not $healResult.Success) {
                            Write-Log "Failed to heal service '$service': $($healResult.Message)" -Level "ERROR" -Category "MCP"
                            return $false
                        }
                    } else {
                        return $false
                    }
                }
            }
        }
        
        Write-Log "MCP initialized successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error initializing MCP: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Test environment type
function Test-EnvironmentType {
    Write-Log "Testing environment type..." -Level "INFO" -Category "MCP"
    
    try {
        # Check if we can connect to remote services
        $remoteServices = @("MySQL", "Redis", "Apache")
        $remoteCount = 0
        
        foreach ($service in $remoteServices) {
            $serviceConfig = $mcpConfig.Services[$service]
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($serviceConfig.Host, $serviceConfig.Port).Wait(1000)
                if ($tcpClient.Connected) {
                    $remoteCount++
                }
                $tcpClient.Close()
            }
            catch {
                # Ignore connection errors
            }
        }
        
        # If we can connect to most remote services, consider it a remote environment
        if ($remoteCount -ge 2) {
            return "remote"
        }
        
        return "local"
    }
    catch {
        Write-Log "Error testing environment type: $_" -Level "ERROR" -Category "MCP"
        return $mcpConfig.Environment.Fallback
    }
}

# Test service health
function Test-ServiceHealth {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Config
    )
    
    Write-Log "Testing health for service: $Service" -Level "INFO" -Category "MCP"
    
    try {
        $healthCheck = $Config.HealthCheck
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
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($Config.Host, $Config.Port).Wait($healthCheck.Timeout * 1000)
                $success = $tcpClient.Connected
                $tcpClient.Close()
                
                if ($success) {
                    return @{
                        IsHealthy = $true
                        Message = "Service is healthy"
                    }
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
            IsHealthy = $false
            Message = "Service health check failed: $error"
        }
    }
    catch {
        Write-Log "Error testing service health: $_" -Level "ERROR" -Category "MCP"
        return @{
            IsHealthy = $false
            Message = "Error testing service health: $_"
        }
    }
}

# Start service healing
function Start-ServiceHealing {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [hashtable]$Config
    )
    
    Write-Log "Starting healing for service: $Service" -Level "INFO" -Category "MCP"
    
    try {
        # Attempt to restart the service
        $result = Invoke-MCPCommand -Command "restart" -Service $Service -Parameters @{
            Force = $true
        }
        
        if ($null -eq $result) {
            return @{
                Success = $false
                Message = "Failed to restart service"
            }
        }
        
        # Verify service health after restart
        $healthCheck = Test-ServiceHealth -Service $Service -Config $Config
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
        Write-Log "Error healing service: $_" -Level "ERROR" -Category "MCP"
        return @{
            Success = $false
            Message = "Error healing service: $_"
        }
    }
}

# Execute MCP command
function Invoke-MCPCommand {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Command,
        
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [hashtable]$Parameters = @{},
        
        [int]$Timeout = $mcpConfig.Timeout,
        
        [int]$RetryCount = $mcpConfig.RetryCount,
        
        [int]$RetryDelay = $mcpConfig.RetryDelay
    )
    
    Write-Log "Executing MCP command: $Command on service: $Service" -Level "INFO" -Category "MCP"
    
    try {
        $attempt = 0
        $success = $false
        $result = $null
        
        while (-not $success -and $attempt -lt $RetryCount) {
            $attempt++
            
            try {
                # Build command URL
                $url = "$($mcpConfig.API)/$Service/$Command"
                
                # Build request body
                $body = @{
                    Parameters = $Parameters
                    Timestamp = Get-Date -Format "o"
                    Version = $mcpConfig.Version
                    Environment = $mcpConfig.Environment.Type
                }
                
                # Send request
                $response = Invoke-RestMethod -Uri $url -Method Post -Body ($body | ConvertTo-Json) -TimeoutSec $Timeout
                
                # Process response
                if ($response.Success) {
                    $success = $true
                    $result = $response.Result
                    Write-Log "MCP command executed successfully" -Level "SUCCESS" -Category "MCP"
                } else {
                    Write-Log "MCP command failed: $($response.Error)" -Level "ERROR" -Category "MCP"
                    if ($attempt -lt $RetryCount) {
                        Write-Log "Retrying in $RetryDelay seconds..." -Level "INFO" -Category "MCP"
                        Start-Sleep -Seconds $RetryDelay
                    }
                }
            }
            catch {
                Write-Log "Error executing MCP command: $_" -Level "ERROR" -Category "MCP"
                if ($attempt -lt $RetryCount) {
                    Write-Log "Retrying in $RetryDelay seconds..." -Level "INFO" -Category "MCP"
                    Start-Sleep -Seconds $RetryDelay
                }
            }
        }
        
        if (-not $success) {
            Write-Log "MCP command failed after $RetryCount attempts" -Level "ERROR" -Category "MCP"
            return $null
        }
        
        return $result
    }
    catch {
        Write-Log "Error executing MCP command: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

# Get MCP status
function Get-MCPStatus {
    param (
        [string]$Service,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting MCP status..." -Level "INFO" -Category "MCP"
    
    try {
        $status = @{
            Version = $mcpConfig.Version
            Services = @{}
            Data = @{}
            Logs = @{}
            Config = @{}
        }
        
        # Get service status
        if ($Service) {
            $serviceConfig = $mcpConfig.Services[$Service]
            if ($null -eq $serviceConfig) {
                Write-Log "Service '$Service' not found" -Level "ERROR" -Category "MCP"
                return $null
            }
            
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($serviceConfig.Host, $serviceConfig.Port).Wait(1000)
                $isConnected = $tcpClient.Connected
                $tcpClient.Close()
                
                $serviceStatus = if ($isConnected) { "Running" } else { "Stopped" }
                $status.Services[$Service] = @{
                    Status = $serviceStatus
                    Host = $serviceConfig.Host
                    Port = $serviceConfig.Port
                }
                
                if ($Detailed) {
                    $status.Services[$Service].Config = $serviceConfig
                }
            }
            catch {
                $status.Services[$Service] = @{
                    Status = "Error"
                    Host = $serviceConfig.Host
                    Port = $serviceConfig.Port
                    Error = $_.Exception.Message
                }
            }
        } else {
            foreach ($service in $mcpConfig.Services.Keys) {
                $serviceConfig = $mcpConfig.Services[$service]
                try {
                    $tcpClient = New-Object System.Net.Sockets.TcpClient
                    $tcpClient.ConnectAsync($serviceConfig.Host, $serviceConfig.Port).Wait(1000)
                    $isConnected = $tcpClient.Connected
                    $tcpClient.Close()
                    
                    $serviceStatus = if ($isConnected) { "Running" } else { "Stopped" }
                    $status.Services[$service] = @{
                        Status = $serviceStatus
                        Host = $serviceConfig.Host
                        Port = $serviceConfig.Port
                    }
                    
                    if ($Detailed) {
                        $status.Services[$service].Config = $serviceConfig
                    }
                }
                catch {
                    $status.Services[$service] = @{
                        Status = "Error"
                        Host = $serviceConfig.Host
                        Port = $serviceConfig.Port
                        Error = $_.Exception.Message
                    }
                }
            }
        }
        
        # Get data status
        foreach ($source in $mcpConfig.Data.Sources.Keys) {
            $sourceConfig = $mcpConfig.Data.Sources[$source]
            $status.Data[$source] = @{
                Type = $sourceConfig.Type
                Status = "Unknown"
            }
            
            if ($Detailed) {
                $status.Data[$source].Config = $sourceConfig
            }
        }
        
        # Get log status
        foreach ($category in $mcpConfig.Logs.Keys) {
            $logConfig = $mcpConfig.Logs[$category]
            $logPath = Join-Path $scriptPath ".." $logConfig.Path
            
            $status.Logs[$category] = @{
                Path = $logPath
                Level = $logConfig.Level
                Exists = Test-Path $logPath
            }
            
            if ($Detailed) {
                $status.Logs[$category].Config = $logConfig
            }
        }
        
        # Get config status
        $configPath = Join-Path $scriptPath ".." $mcpConfig.Config.Path
        $status.Config = @{
            Path = $configPath
            Exists = Test-Path $configPath
            Backup = @{
                Enabled = $mcpConfig.Config.Backup.Enabled
                Path = Join-Path $scriptPath ".." $mcpConfig.Config.Backup.Path
                Exists = Test-Path (Join-Path $scriptPath ".." $mcpConfig.Config.Backup.Path)
            }
        }
        
        if ($Detailed) {
            $status.Config.Config = $mcpConfig.Config
        }
        
        Write-Log "MCP status retrieved successfully" -Level "SUCCESS" -Category "MCP"
        return $status
    }
    catch {
        Write-Log "Error getting MCP status: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

# Export functions
# Export-ModuleMember -Function @(
#     "Initialize-MCP",
#     "Test-EnvironmentType",
#     "Test-ServiceHealth",
#     "Start-ServiceHealing",
#     "Invoke-MCPCommand",
#     "Get-MCPStatus"
# ) 