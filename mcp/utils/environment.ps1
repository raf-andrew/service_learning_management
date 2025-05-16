# Core Environment Utility Functions
$ErrorActionPreference = "Stop"

function Set-Environment {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Name
    )
    
    try {
        # Load environment configuration
        $config = Get-EnvironmentConfig -Name $Name
        
        # Set environment variables
        foreach ($key in $config.Keys) {
            [Environment]::SetEnvironmentVariable($key, $config[$key], "Process")
        }
        
        # Set PowerShell variables
        $env:ENVIRONMENT = $Name
        
        return $true
    }
    catch {
        Write-Error "Failed to set environment: $_"
        return $false
    }
}

function Get-EnvironmentConfig {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Name
    )
    
    try {
        # Load environment configuration from file
        $configPath = Join-Path -Path $PSScriptRoot -ChildPath ".." "config" "$Name.json"
        if (-not (Test-Path $configPath)) {
            throw "Environment configuration not found: $configPath"
        }
        
        $config = Get-Content -Path $configPath -Raw | ConvertFrom-Json -AsHashtable
        
        return $config
    }
    catch {
        Write-Error "Failed to get environment configuration: $_"
        return @{}
    }
}

function Get-DiskSpace {
    try {
        $disk = Get-PSDrive C
        return @{
            TotalSpace = $disk.Free + $disk.Used
            FreeSpace = $disk.Free
            UsedSpace = $disk.Used
            UsedPercent = [math]::Round(($disk.Used / ($disk.Free + $disk.Used)) * 100, 2)
        }
    }
    catch {
        Write-Error "Failed to get disk space: $_"
        return @{
            TotalSpace = 0
            FreeSpace = 0
            UsedSpace = 0
            UsedPercent = 0
        }
    }
}

function Get-MemoryUsage {
    try {
        $memory = Get-CimInstance Win32_OperatingSystem
        return @{
            TotalMemory = $memory.TotalVisibleMemorySize
            FreeMemory = $memory.FreePhysicalMemory
            UsedMemory = $memory.TotalVisibleMemorySize - $memory.FreePhysicalMemory
            UsagePercent = [math]::Round((($memory.TotalVisibleMemorySize - $memory.FreePhysicalMemory) / $memory.TotalVisibleMemorySize) * 100, 2)
        }
    }
    catch {
        Write-Error "Failed to get memory usage: $_"
        return @{
            TotalMemory = 0
            FreeMemory = 0
            UsedMemory = 0
            UsagePercent = 0
        }
    }
}

# Export functions
# Export-ModuleMember -Function @(
#     "Set-Environment",
#     "Get-EnvironmentConfig",
#     "Get-DiskSpace",
#     "Get-MemoryUsage"
# ) 