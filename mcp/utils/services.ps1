# Core Services Utility Functions
$ErrorActionPreference = "Stop"

function Initialize-Services {
    param (
        [Parameter(Mandatory=$false)]
        [string[]]$Services = @("mysql", "redis", "nginx")
    )
    
    try {
        foreach ($service in $Services) {
            $status = Get-ServiceStatus -Service $service
            if ($status.State -ne "Running") {
                Start-Service -Service $service
            }
        }
        return $true
    }
    catch {
        Write-Error "Failed to initialize services: $_"
        return $false
    }
}

function Test-ServiceHealth {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service
    )
    
    try {
        $status = Get-ServiceStatus -Service $Service
        
        return @{
            Healthy = ($status.State -eq "Running")
            State = $status.State
            Issue = $status.Error
            Details = $status.Details
        }
    }
    catch {
        Write-Error "Failed to check service health for $Service : $_"
        return @{
            Healthy = $false
            State = "Error"
            Issue = $_.Exception.Message
            Details = @{}
        }
    }
}

function Invoke-ServiceAction {
    param (
        [Parameter(Mandatory=$true)]
        [string]$Service,
        
        [Parameter(Mandatory=$true)]
        [ValidateSet("start", "stop", "restart", "status")]
        [string]$Action
    )
    
    try {
        switch ($Action) {
            "start" {
                $result = Start-Process -FilePath "net" -ArgumentList "start $Service" -NoNewWindow -Wait -PassThru
            }
            "stop" {
                $result = Start-Process -FilePath "net" -ArgumentList "stop $Service" -NoNewWindow -Wait -PassThru
            }
            "restart" {
                $result = Start-Process -FilePath "net" -ArgumentList "stop $Service" -NoNewWindow -Wait -PassThru
                Start-Sleep -Seconds 2
                $result = Start-Process -FilePath "net" -ArgumentList "start $Service" -NoNewWindow -Wait -PassThru
            }
            "status" {
                $result = Get-Service -Name $Service
            }
        }
        
        return @{
            Success = ($result.ExitCode -eq 0)
            State = if ($Action -eq "status") { $result.Status } else { $null }
            Error = if ($result.ExitCode -ne 0) { $result.StandardError } else { $null }
            Details = @{
                ExitCode = $result.ExitCode
                Output = $result.StandardOutput
            }
        }
    }
    catch {
        Write-Error "Failed to invoke service action $Action for $Service : $_"
        return @{
            Success = $false
            State = $null
            Error = $_.Exception.Message
            Details = @{
                ExitCode = -1
                Output = $null
            }
        }
    }
}

# Export functions
# Export-ModuleMember -Function @(
#     "Initialize-Services",
#     "Test-ServiceHealth",
#     "Invoke-ServiceAction"
# ) 