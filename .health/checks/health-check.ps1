# Health Check System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath ".." "utils" "environment.ps1")

# Health check results
$healthResults = @{
    Timestamp = Get-Date
    Environment = Get-Environment
    Checks = @()
    Status = "Unknown"
    Issues = @()
}

# Run a health check
function Invoke-HealthCheck {
    param (
        [string]$CheckName,
        [scriptblock]$CheckScript,
        [string]$Category
    )

    Write-Log "Running health check: $CheckName" -Level "INFO" -Category $Category

    try {
        $result = & $CheckScript
        $healthResults.Checks += @{
            Name = $CheckName
            Category = $Category
            Status = $result.Status
            Message = $result.Message
            Details = $result.Details
            Timestamp = Get-Date
        }

        if ($result.Status -ne "Healthy") {
            $healthResults.Issues += @{
                Check = $CheckName
                Category = $Category
                Message = $result.Message
                Details = $result.Details
            }
        }

        Write-Log "Health check completed: $CheckName - $($result.Status)" -Level $result.Status -Category $Category
    }
    catch {
        $errorMessage = $_.Exception.Message
        Write-Log "Error in health check $CheckName : $errorMessage" -Level "ERROR" -Category $Category
        
        $healthResults.Checks += @{
            Name = $CheckName
            Category = $Category
            Status = "Error"
            Message = $errorMessage
            Details = $_.Exception
            Timestamp = Get-Date
        }

        $healthResults.Issues += @{
            Check = $CheckName
            Category = $Category
            Message = $errorMessage
            Details = $_.Exception
        }
    }
}

# Run all health checks
function Start-HealthChecks {
    Write-Log "Starting health checks..." -Level "INFO"

    # System Requirements
    Invoke-HealthCheck -CheckName "PowerShell Version" -Category "System" -CheckScript {
        $version = $PSVersionTable.PSVersion
        $requiredVersion = [Version]"7.0.0"
        
        if ($version -ge $requiredVersion) {
            @{
                Status = "Healthy"
                Message = "PowerShell version $version is sufficient"
                Details = @{ Version = $version }
            }
        } else {
            @{
                Status = "Unhealthy"
                Message = "PowerShell version $version is below required version $requiredVersion"
                Details = @{ 
                    Current = $version
                    Required = $requiredVersion
                }
            }
        }
    }

    # Required Tools
    Invoke-HealthCheck -CheckName "Required Tools" -Category "System" -CheckScript {
        $tools = @(
            @{ Name = "php"; Command = "php -v" },
            @{ Name = "composer"; Command = "composer -V" },
            @{ Name = "node"; Command = "node -v" },
            @{ Name = "npm"; Command = "npm -v" },
            @{ Name = "jq"; Command = "jq --version" },
            @{ Name = "gh"; Command = "gh --version" }
        )

        $missingTools = @()
        foreach ($tool in $tools) {
            try {
                $output = Invoke-Expression $tool.Command 2>&1
                Write-Log "$($tool.Name) is available: $output" -Level "INFO"
            } catch {
                $missingTools += $tool.Name
            }
        }

        if ($missingTools.Count -eq 0) {
            @{
                Status = "Healthy"
                Message = "All required tools are available"
                Details = @{ Tools = $tools.Name }
            }
        } else {
            @{
                Status = "Unhealthy"
                Message = "Missing required tools: $($missingTools -join ', ')"
                Details = @{ MissingTools = $missingTools }
            }
        }
    }

    # GitHub Integration
    Invoke-HealthCheck -CheckName "GitHub Authentication" -Category "GitHub" -CheckScript {
        try {
            $authStatus = gh auth status 2>&1
            if ($LASTEXITCODE -eq 0) {
                @{
                    Status = "Healthy"
                    Message = "GitHub authentication is valid"
                    Details = @{ Status = $authStatus }
                }
            } else {
                @{
                    Status = "Unhealthy"
                    Message = "GitHub authentication failed"
                    Details = @{ Error = $authStatus }
                }
            }
        } catch {
            @{
                Status = "Unhealthy"
                Message = "GitHub CLI not available"
                Details = @{ Error = $_.Exception.Message }
            }
        }
    }

    # Service Health
    Invoke-HealthCheck -CheckName "Service Health" -Category "Services" -CheckScript {
        $services = @(
            @{ Name = "MySQL"; Port = 3306 },
            @{ Name = "Redis"; Port = 6379 },
            @{ Name = "Apache"; Port = 80 }
        )

        $unhealthyServices = @()
        foreach ($service in $services) {
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect("localhost", $service.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                if (-not $success) {
                    $unhealthyServices += $service.Name
                }
                $tcpClient.Close()
            } catch {
                $unhealthyServices += $service.Name
            }
        }

        if ($unhealthyServices.Count -eq 0) {
            @{
                Status = "Healthy"
                Message = "All services are running"
                Details = @{ Services = $services.Name }
            }
        } else {
            @{
                Status = "Unhealthy"
                Message = "Unhealthy services: $($unhealthyServices -join ', ')"
                Details = @{ UnhealthyServices = $unhealthyServices }
            }
        }
    }

    # Update overall status
    if ($healthResults.Issues.Count -eq 0) {
        $healthResults.Status = "Healthy"
    } else {
        $healthResults.Status = "Unhealthy"
    }

    # Save results
    $resultsPath = Join-Path (Join-Path $scriptPath ".." "logs") "health-check-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
    $healthResults | ConvertTo-Json -Depth 10 | Set-Content -Path $resultsPath

    Write-Log "Health checks completed. Status: $($healthResults.Status)" -Level $healthResults.Status
    return $healthResults
}

# Run health checks if script is executed directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    Start-HealthChecks
} 