# MCP Developer Utilities
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# Get System Status
function Get-SystemStatus {
    param (
        [string]$Environment = "local"
    )
    
    Write-Log "Getting system status..." -Level "INFO" -Category "MCP"
    
    try {
        $status = @{
            Server = Get-MCPServerStatus
            Health = Test-MCPServerHealth -Detailed:$true
            Prerequisites = Test-Prerequisites -Environment $Environment
            Logs = @{
                Recent = Get-ServiceLogs -Lines 10
                Errors = Get-ServiceLogs -Level "ERROR" -Lines 5
            }
            Config = Get-ConfigStatus
        }
        
        return $status
    }
    catch {
        Write-Log "Error getting system status: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

# Get Service Logs
function Get-ServiceLogs {
    param (
        [string]$Service,
        [string]$Level,
        [int]$Lines = 50,
        [switch]$Follow
    )
    
    Write-Log "Getting service logs..." -Level "INFO" -Category "MCP"
    
    try {
        $logPath = Join-Path -Path $mcpPath -ChildPath "logs"
        $logs = @()
        
        if ($Service) {
            $logFile = Join-Path -Path $logPath -ChildPath "$Service.log"
            if (Test-Path -Path $logFile) {
                $logs += Get-Content -Path $logFile -Tail $Lines
            }
        }
        else {
            Get-ChildItem -Path $logPath -Filter "*.log" | ForEach-Object {
                $logs += Get-Content -Path $_.FullName -Tail $Lines
            }
        }
        
        if ($Level) {
            $logs = $logs | Where-Object { $_ -match "\[$Level\]" }
        }
        
        if ($Follow) {
            Get-Content -Path $logFile -Wait
        }
        else {
            return $logs
        }
    }
    catch {
        Write-Log "Error getting service logs: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

# Clear Service Logs
function Clear-ServiceLogs {
    param (
        [string]$Service,
        [switch]$Force
    )
    
    Write-Log "Clearing service logs..." -Level "INFO" -Category "MCP"
    
    try {
        $logPath = Join-Path -Path $mcpPath -ChildPath "logs"
        
        if ($Service) {
            $logFile = Join-Path -Path $logPath -ChildPath "$Service.log"
            if (Test-Path -Path $logFile) {
                if ($Force -or $Host.UI.PromptForChoice("Clear Logs", "Are you sure you want to clear $Service logs?", @("&Yes", "&No"), 1) -eq 0) {
                    Clear-Content -Path $logFile
                    Write-Log "Cleared logs for $Service" -Level "SUCCESS" -Category "MCP"
                }
            }
        }
        else {
            if ($Force -or $Host.UI.PromptForChoice("Clear Logs", "Are you sure you want to clear all logs?", @("&Yes", "&No"), 1) -eq 0) {
                Get-ChildItem -Path $logPath -Filter "*.log" | ForEach-Object {
                    Clear-Content -Path $_.FullName
                }
                Write-Log "Cleared all logs" -Level "SUCCESS" -Category "MCP"
            }
        }
    }
    catch {
        Write-Log "Error clearing service logs: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Get System Metrics
function Get-SystemMetrics {
    param (
        [int]$Duration = 60,
        [string]$Environment = "local"
    )
    
    Write-Log "Getting system metrics..." -Level "INFO" -Category "MCP"
    
    try {
        $metrics = @{
            CPU = @()
            Memory = @()
            Disk = @()
            Network = @()
            Services = @{}
        }
        
        $endTime = (Get-Date).AddSeconds($Duration)
        
        while ((Get-Date) -lt $endTime) {
            # CPU metrics
            $metrics.CPU += Get-Counter '\Processor(_Total)\% Processor Time' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            
            # Memory metrics
            $metrics.Memory += Get-Counter '\Memory\Available MBytes' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            
            # Disk metrics
            $metrics.Disk += Get-Counter '\LogicalDisk(_Total)\% Free Space' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            
            # Network metrics
            $metrics.Network += Get-Counter '\Network Interface(*)\Bytes Total/sec' | Select-Object -ExpandProperty CounterSamples | Select-Object -ExpandProperty CookedValue
            
            # Service metrics
            $services = @("mysql", "redis", "apache")
            foreach ($service in $services) {
                if (-not $metrics.Services.ContainsKey($service)) {
                    $metrics.Services[$service] = @()
                }
                $metrics.Services[$service] += (Get-ServiceStatus -Service $service).Status
            }
            
            Start-Sleep -Seconds 1
        }
        
        return $metrics
    }
    catch {
        Write-Log "Error getting system metrics: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

# Export System Report
function Export-SystemReport {
    param (
        [string]$Path,
        [switch]$IncludeMetrics,
        [string]$Environment = "local"
    )
    
    Write-Log "Exporting system report..." -Level "INFO" -Category "MCP"
    
    try {
        $report = @{
            Timestamp = Get-Date
            Environment = $Environment
            Status = Get-SystemStatus -Environment $Environment
            Logs = @{
                Recent = Get-ServiceLogs -Lines 10
                Errors = Get-ServiceLogs -Level "ERROR" -Lines 5
            }
        }
        
        if ($IncludeMetrics) {
            $report.Metrics = Get-SystemMetrics -Environment $Environment
        }
        
        if (-not $Path) {
            $Path = Join-Path -Path $mcpPath -ChildPath "logs/system-report.json"
        }
        
        $report | ConvertTo-Json -Depth 10 | Out-File -FilePath $Path
        Write-Log "System report exported to $Path" -Level "SUCCESS" -Category "MCP"
        
        return $true
    }
    catch {
        Write-Log "Error exporting system report: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function @(
    "Get-SystemStatus",
    "Get-ServiceLogs",
    "Clear-ServiceLogs",
    "Get-SystemMetrics",
    "Export-SystemReport"
) 