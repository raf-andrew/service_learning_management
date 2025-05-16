# MCP Data Source Management
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath "environment.ps1")

# Data Source Management
function Test-DataSource {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Source,
        [Parameter(Mandatory=$true)]
        [string]$Environment
    )
    Write-Log "Testing data source: $Source in $Environment" -Level "INFO" -Category "Data"
    $config = Get-EnvironmentConfig -Environment $Environment
    if ($null -eq $config) { return $false }
    $sourceConfig = $config.Data.Sources[$Source]
    if ($null -eq $sourceConfig) {
        Write-Log "Data source config not found: $Source" -Level "ERROR" -Category "Data"
        return $false
    }
    try {
        switch ($sourceConfig.Type) {
            "MySQL" {
                $cmd = "mysql -h $($config.Services.MySQL.Host) -P $($config.Services.MySQL.Port) -u $($config.Services.MySQL.User) --password=$($config.Services.MySQL.Password) -e 'SELECT 1 FROM $($sourceConfig.Table) LIMIT 1;' $($config.Services.MySQL.Database)"
                $result = Invoke-Expression $cmd
                if ($LASTEXITCODE -eq 0) { return $true } else { return $false }
            }
            "Redis" {
                $cmd = "redis-cli -h $($config.Services.Redis.Host) -p $($config.Services.Redis.Port) EXISTS $($sourceConfig.Key)"
                $result = Invoke-Expression $cmd
                if ($result -ge 0) { return $true } else { return $false }
            }
            "File" {
                $filePath = Join-Path $scriptPath ".." $sourceConfig.Path
                if (Test-Path $filePath) { return $true } else { return $false }
            }
            default {
                Write-Log "Unknown data source type: $($sourceConfig.Type)" -Level "ERROR" -Category "Data"
                return $false
            }
        }
    } catch {
        Write-Log "Error testing data source: $_" -Level "ERROR" -Category "Data"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Test-DataSource"
) 