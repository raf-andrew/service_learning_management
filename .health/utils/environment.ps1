# Environment Utility
$ErrorActionPreference = "Stop"

# Environment configuration
$envConfig = @{
    Local = @{
        Services = @{
            MySQL = @{
                Host = "localhost"
                Port = 3306
            }
            Redis = @{
                Host = "localhost"
                Port = 6379
            }
            Apache = @{
                Host = "localhost"
                Port = 80
            }
        }
        GitHub = @{
            API = "https://api.github.com"
            Web = "https://github.com"
        }
        Logging = @{
            Level = "DEBUG"
            Retention = 30
        }
    }
    Remote = @{
        Services = @{
            MySQL = @{
                Host = "mysql.service.internal"
                Port = 3306
            }
            Redis = @{
                Host = "redis.service.internal"
                Port = 6379
            }
            Apache = @{
                Host = "apache.service.internal"
                Port = 80
            }
        }
        GitHub = @{
            API = "https://api.github.com"
            Web = "https://github.com"
        }
        Logging = @{
            Level = "INFO"
            Retention = 7
        }
    }
}

# Get current environment
function Get-Environment {
    $env = $env:HEALTH_ENV
    if (-not $env) {
        $env = "Local"
    }
    return $env
}

# Get environment configuration
function Get-EnvironmentConfig {
    param (
        [string]$Environment = (Get-Environment)
    )

    return $envConfig[$Environment]
}

# Switch environment
function Set-Environment {
    param (
        [ValidateSet("Local", "Remote")]
        [string]$Environment
    )

    $env:HEALTH_ENV = $Environment
    Write-Log "Switched to $Environment environment" -Level "INFO" -Category "Environment"
}

# Get service configuration
function Get-ServiceConfig {
    param (
        [string]$Service,
        [string]$Environment = (Get-Environment)
    )

    $config = Get-EnvironmentConfig -Environment $Environment
    return $config.Services[$Service]
}

# Get GitHub configuration
function Get-GitHubConfig {
    param (
        [string]$Environment = (Get-Environment)
    )

    $config = Get-EnvironmentConfig -Environment $Environment
    return $config.GitHub
}

# Get logging configuration
function Get-LoggingConfig {
    param (
        [string]$Environment = (Get-Environment)
    )

    $config = Get-EnvironmentConfig -Environment $Environment
    return $config.Logging
}

# Export functions
Export-ModuleMember -Function Get-Environment, Get-EnvironmentConfig, Set-Environment, Get-ServiceConfig, Get-GitHubConfig, Get-LoggingConfig 