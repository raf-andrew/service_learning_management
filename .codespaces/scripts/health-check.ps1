# Health Check Script for Codespaces Services
$ErrorActionPreference = "Stop"

# Create necessary directories
$logDir = ".codespaces/log"
$failuresDir = ".codespaces/log/failures"
$completeDir = ".codespaces/log/complete"

New-Item -ItemType Directory -Force -Path $logDir | Out-Null
New-Item -ItemType Directory -Force -Path $failuresDir | Out-Null
New-Item -ItemType Directory -Force -Path $completeDir | Out-Null

# Generate timestamp for log file
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$logFile = "$logDir/health-check-$timestamp.log"

function Write-Log {
    param($Message)
    $logMessage = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): $Message"
    Add-Content -Path $logFile -Value $logMessage
    Write-Host $logMessage
}

function Test-ServiceHealth {
    param(
        [string]$ServiceName,
        [string]$Host,
        [int]$Port
    )
    
    try {
        $tcpClient = New-Object System.Net.Sockets.TcpClient
        $result = $tcpClient.BeginConnect($Host, $Port, $null, $null)
        $success = $result.AsyncWaitHandle.WaitOne(5000, $false)
        
        if ($success) {
            $tcpClient.EndConnect($result)
            Write-Log "✅ $ServiceName is healthy (Port $Port)"
            return $true
        } else {
            Write-Log "❌ $ServiceName is not responding (Port $Port)"
            return $false
        }
    } catch {
        Write-Log "❌ $ServiceName health check failed: $_"
        return $false
    } finally {
        if ($tcpClient) { $tcpClient.Close() }
    }
}

# Check MySQL
$mysqlHealthy = Test-ServiceHealth -ServiceName "MySQL" -Host "localhost" -Port 3306

# Check Redis
$redisHealthy = Test-ServiceHealth -ServiceName "Redis" -Host "localhost" -Port 6379

# Check MailHog
$mailhogHealthy = Test-ServiceHealth -ServiceName "MailHog" -Host "localhost" -Port 1025

# Generate health report
$report = @{
    timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    services = @{
        mysql = @{
            status = if ($mysqlHealthy) { "healthy" } else { "unhealthy" }
            port = 3306
        }
        redis = @{
            status = if ($redisHealthy) { "healthy" } else { "unhealthy" }
            port = 6379
        }
        mailhog = @{
            status = if ($mailhogHealthy) { "healthy" } else { "unhealthy" }
            port = 1025
        }
    }
}

# Save report
$reportFile = "$logDir/health-report-$timestamp.json"
$report | ConvertTo-Json | Set-Content $reportFile

# Move log file to appropriate directory
if (-not ($mysqlHealthy -and $redisHealthy -and $mailhogHealthy)) {
    Move-Item -Path $logFile -Destination "$failuresDir/health-check-$timestamp.log" -Force
    Write-Log "❌ Health check failed - see $failuresDir/health-check-$timestamp.log for details"
    exit 1
} else {
    Move-Item -Path $logFile -Destination "$completeDir/health-check-$timestamp.log" -Force
    Write-Log "✅ All services are healthy"
    exit 0
} 