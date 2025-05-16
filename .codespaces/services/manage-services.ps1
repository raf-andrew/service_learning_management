# Docker services management script

$ErrorActionPreference = "Stop"
$ProgressPreference = "SilentlyContinue"

# Import logger
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "../testing/scripts/TestLogger.ps1")

# Initialize service management results
$serviceResults = @{
    StartTime = Get-Date
    EndTime = $null
    Steps = @()
    Status = "Running"
}

# Function to add service step
function Add-ServiceStep {
    param (
        [string]$Name,
        [string]$Status,
        [string]$Details
    )
    
    $serviceResults.Steps += @{
        Name = $Name
        Status = $Status
        Details = $Details
        Time = Get-Date
    }
}

# Function to write service report
function Write-ServiceReport {
    $serviceResults.EndTime = Get-Date
    $duration = $serviceResults.EndTime - $serviceResults.StartTime

    $report = @"
# Service Management Report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

## Service Information
- Duration: $($duration.TotalSeconds) seconds
- Status: $($serviceResults.Status)

## Service Steps
"@

    foreach ($step in $serviceResults.Steps) {
        $report += @"

### $($step.Name)
- Status: $($step.Status)
- Details: $($step.Details)
- Time: $($step.Time)
"@
    }

    # Save report
    $reportPath = Join-Path $scriptPath "../testing/.test/results/service-report-$(Get-Date -Format 'yyyyMMdd-HHmmss').md"
    Set-Content -Path $reportPath -Value $report
    Write-TestLog "Service report generated: $reportPath" -Level "INFO"

    # Save JSON data
    $jsonPath = Join-Path $scriptPath "../testing/.test/tracking/service-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $serviceResults | ConvertTo-Json -Depth 10 | Set-Content -Path $jsonPath
    Write-TestLog "Service data saved: $jsonPath" -Level "INFO"
}

# Function to check if Docker is running
function Test-DockerRunning {
    try {
        $result = docker info 2>&1
        return $LASTEXITCODE -eq 0
    } catch {
        return $false
    }
}

# Function to check if a service is running
function Test-ServiceRunning {
    param (
        [string]$ServiceName
    )
    
    try {
        $result = docker ps --filter "name=$ServiceName" --format "{{.Names}}"
        return $result -eq $ServiceName
    } catch {
        return $false
    }
}

# Function to start a service
function Start-Service {
    param (
        [string]$ServiceName,
        [string]$ConfigFile
    )
    
    if (Test-ServiceRunning -ServiceName $ServiceName) {
        Write-TestLog "Service $ServiceName is already running" -Level "INFO"
        return $true
    }

    try {
        $config = Get-Content $ConfigFile | ConvertFrom-Json
        $composeFile = Join-Path $scriptPath "docker-compose.$ServiceName.yml"
        
        # Create docker-compose file
        @"
version: '3.8'

services:
  $ServiceName:
    image: $($config.image)
    container_name: $ServiceName
    ports:
      - "$($config.ports):$($config.ports)"
    environment:
"@ | Set-Content -Path $composeFile

        # Add environment variables
        foreach ($env in $config.environment.GetEnumerator()) {
            Add-Content -Path $composeFile -Value "      $($env.Key): $($env.Value)"
        }

        # Add volumes if present
        if ($config.volumes) {
            Add-Content -Path $composeFile -Value "    volumes:"
            foreach ($volume in $config.volumes) {
                Add-Content -Path $composeFile -Value "      - $volume"
            }
        }

        # Start the service
        docker-compose -f $composeFile up -d
        return $LASTEXITCODE -eq 0
    } catch {
        Write-TestError "Failed to start service $ServiceName : $_"
        return $false
    }
}

# Function to stop a service
function Stop-Service {
    param (
        [string]$ServiceName
    )
    
    if (-not (Test-ServiceRunning -ServiceName $ServiceName)) {
        Write-TestLog "Service $ServiceName is not running" -Level "INFO"
        return $true
    }

    try {
        $composeFile = Join-Path $scriptPath "docker-compose.$ServiceName.yml"
        if (Test-Path $composeFile) {
            docker-compose -f $composeFile down
        } else {
            docker stop $ServiceName
            docker rm $ServiceName
        }
        return $LASTEXITCODE -eq 0
    } catch {
        Write-TestError "Failed to stop service $ServiceName : $_"
        return $false
    }
}

# Main execution
try {
    Write-TestLog "Starting service management..." -Level "INFO"

    # Step 1: Check Docker
    Add-ServiceStep -Name "Check Docker" -Status "Running" -Details "Checking Docker status"
    if (Test-DockerRunning) {
        Add-ServiceStep -Name "Check Docker" -Status "Passed" -Details "Docker is running"
    } else {
        Add-ServiceStep -Name "Check Docker" -Status "Failed" -Details "Docker is not running"
        throw "Docker is not running"
    }

    # Step 2: Start MySQL
    Add-ServiceStep -Name "Start MySQL" -Status "Running" -Details "Starting MySQL service"
    $mysqlConfig = Join-Path $scriptPath "mysql.json"
    if (Start-Service -ServiceName "mysql" -ConfigFile $mysqlConfig) {
        Add-ServiceStep -Name "Start MySQL" -Status "Passed" -Details "MySQL service started"
    } else {
        Add-ServiceStep -Name "Start MySQL" -Status "Failed" -Details "Failed to start MySQL service"
        throw "Failed to start MySQL service"
    }

    # Step 3: Start Redis
    Add-ServiceStep -Name "Start Redis" -Status "Running" -Details "Starting Redis service"
    $redisConfig = Join-Path $scriptPath "redis.json"
    if (Start-Service -ServiceName "redis" -ConfigFile $redisConfig) {
        Add-ServiceStep -Name "Start Redis" -Status "Passed" -Details "Redis service started"
    } else {
        Add-ServiceStep -Name "Start Redis" -Status "Failed" -Details "Failed to start Redis service"
        throw "Failed to start Redis service"
    }

    # Step 4: Start MailHog
    Add-ServiceStep -Name "Start MailHog" -Status "Running" -Details "Starting MailHog service"
    $mailhogConfig = @{
        image = "mailhog/mailhog"
        ports = "1025:1025"
        environment = @{
            MH_HOSTNAME = "mailhog"
            MH_UI_BIND_ADDR = "0.0.0.0:8025"
            MH_API_BIND_ADDR = "0.0.0.0:8025"
        }
    } | ConvertTo-Json

    $mailhogConfigFile = Join-Path $scriptPath "mailhog.json"
    Set-Content -Path $mailhogConfigFile -Value $mailhogConfig

    if (Start-Service -ServiceName "mailhog" -ConfigFile $mailhogConfigFile) {
        Add-ServiceStep -Name "Start MailHog" -Status "Passed" -Details "MailHog service started"
    } else {
        Add-ServiceStep -Name "Start MailHog" -Status "Failed" -Details "Failed to start MailHog service"
        throw "Failed to start MailHog service"
    }

    # Service management completed successfully
    $serviceResults.Status = "Success"
    Write-ServiceReport
    Write-TestLog "Service management completed successfully" -Level "SUCCESS"
    exit 0
} catch {
    $serviceResults.Status = "Failed"
    Write-ServiceReport
    Write-TestError "Service management failed: $_"
    exit 1
} 