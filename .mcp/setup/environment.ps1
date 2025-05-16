# MCP Environment Configuration
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path (Join-Path $scriptPath "..") (Join-Path "utils" "logger.ps1"))

# Environment Configuration
$envConfig = @{
    Local = @{
        Services = @{
            MySQL = @{
                Host = "localhost"
                Port = 3306
                User = "root"
                Password = ""
                Database = "mcp"
            }
            Redis = @{
                Host = "localhost"
                Port = 6379
                Password = ""
            }
            Apache = @{
                Host = "localhost"
                Port = 80
                SSL = $false
            }
        }
        Data = @{
            Sources = @{
                Users = @{
                    Type = "MySQL"
                    Table = "users"
                }
                Logs = @{
                    Type = "Redis"
                    Key = "logs"
                }
                Config = @{
                    Type = "File"
                    Path = "config.json"
                }
            }
        }
        Logs = @{
            General = @{
                Path = "logs/general"
                Level = "INFO"
            }
            Services = @{
                Path = "logs/services"
                Level = "INFO"
            }
            Data = @{
                Path = "logs/data"
                Level = "INFO"
            }
            Config = @{
                Path = "logs/config"
                Level = "INFO"
            }
            Audit = @{
                Path = "logs/audit"
                Level = "INFO"
            }
        }
    }
    Remote = @{
        Services = @{
            MySQL = @{
                Host = "db.example.com"
                Port = 3306
                User = "mcp"
                Password = ""
                Database = "mcp"
            }
            Redis = @{
                Host = "cache.example.com"
                Port = 6379
                Password = ""
            }
            Apache = @{
                Host = "web.example.com"
                Port = 443
                SSL = $true
            }
        }
        Data = @{
            Sources = @{
                Users = @{
                    Type = "MySQL"
                    Table = "users"
                }
                Logs = @{
                    Type = "Redis"
                    Key = "logs"
                }
                Config = @{
                    Type = "File"
                    Path = "config.json"
                }
            }
        }
        Logs = @{
            General = @{
                Path = "logs/general"
                Level = "INFO"
                RemoteSync = @{
                    Enabled = $true
                    Interval = 300
                }
            }
            Services = @{
                Path = "logs/services"
                Level = "INFO"
                RemoteSync = @{
                    Enabled = $true
                    Interval = 300
                }
            }
            Data = @{
                Path = "logs/data"
                Level = "INFO"
                RemoteSync = @{
                    Enabled = $true
                    Interval = 300
                }
            }
            Config = @{
                Path = "logs/config"
                Level = "INFO"
                RemoteSync = @{
                    Enabled = $true
                    Interval = 300
                }
            }
            Audit = @{
                Path = "logs/audit"
                Level = "INFO"
                RemoteSync = @{
                    Enabled = $true
                    Interval = 300
                }
            }
        }
    }
}

# Get environment configuration
function Get-EnvironmentConfig {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    
    Write-Log "Getting environment configuration for: $Environment" -Level "INFO" -Category "Environment"
    
    if (-not $envConfig.ContainsKey($Environment)) {
        Write-Log "Invalid environment: $Environment" -Level "ERROR" -Category "Environment"
        return $null
    }
    
    return $envConfig[$Environment]
}

# Test environment connectivity
function Test-EnvironmentConnectivity {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    
    Write-Log "Testing environment connectivity for: $Environment" -Level "INFO" -Category "Environment"
    
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) {
        return $false
    }
    
    $success = $true
    
    # Test service connectivity
    foreach ($service in $config.Services.Keys) {
        $serviceConfig = $config.Services[$service]
        try {
            $tcpClient = New-Object System.Net.Sockets.TcpClient
            $tcpClient.ConnectAsync($serviceConfig.Host, $serviceConfig.Port).Wait(1000)
            if (-not $tcpClient.Connected) {
                Write-Log "Failed to connect to service '$service' at $($serviceConfig.Host):$($serviceConfig.Port)" -Level "ERROR" -Category "Environment"
                $success = $false
            }
            $tcpClient.Close()
        }
        catch {
            Write-Log "Error connecting to service '$service': $_" -Level "ERROR" -Category "Environment"
            $success = $false
        }
    }
    
    return $success
}

# Export functions
Export-ModuleMember -Function @(
    "Get-EnvironmentConfig",
    "Test-EnvironmentConnectivity"
) 