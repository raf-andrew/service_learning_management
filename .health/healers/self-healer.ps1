# Self-Healing System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath ".." "utils" "logger.ps1")
. (Join-Path $scriptPath ".." "utils" "environment.ps1")

# Healing strategies
$healingStrategies = @{
    "PowerShell Version" = {
        param($issue)
        Write-Log "Attempting to upgrade PowerShell..." -Level "INFO" -Category "Healer"
        
        try {
            # Download and install PowerShell 7
            $url = "https://aka.ms/install-powershell.ps1"
            $installer = Join-Path $env:TEMP "install-powershell.ps1"
            Invoke-WebRequest -Uri $url -OutFile $installer
            & $installer -Quiet -UseMSI
            
            Write-Log "PowerShell upgrade completed" -Level "SUCCESS" -Category "Healer"
            return $true
        }
        catch {
            Write-Log "Failed to upgrade PowerShell: $_" -Level "ERROR" -Category "Healer"
            return $false
        }
    }

    "Required Tools" = {
        param($issue)
        Write-Log "Attempting to install missing tools..." -Level "INFO" -Category "Healer"
        
        try {
            # Install Chocolatey if not present
            if (-not (Get-Command choco -ErrorAction SilentlyContinue)) {
                Set-ExecutionPolicy Bypass -Scope Process -Force
                [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
                Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
            }

            # Install missing tools
            foreach ($tool in $issue.Details.MissingTools) {
                switch ($tool) {
                    "php" { choco install php -y }
                    "composer" { choco install composer -y }
                    "node" { choco install nodejs-lts -y }
                    "npm" { choco install nodejs-lts -y } # npm comes with node
                    "jq" { choco install jq -y }
                    "gh" { choco install gh -y }
                }
            }
            
            Write-Log "Tool installation completed" -Level "SUCCESS" -Category "Healer"
            return $true
        }
        catch {
            Write-Log "Failed to install tools: $_" -Level "ERROR" -Category "Healer"
            return $false
        }
    }

    "GitHub Authentication" = {
        param($issue)
        Write-Log "Attempting to fix GitHub authentication..." -Level "INFO" -Category "Healer"
        
        try {
            # Check if GitHub CLI is installed
            if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
                choco install gh -y
            }

            # Attempt to login
            gh auth login
            if ($LASTEXITCODE -eq 0) {
                Write-Log "GitHub authentication fixed" -Level "SUCCESS" -Category "Healer"
                return $true
            } else {
                Write-Log "GitHub authentication failed" -Level "ERROR" -Category "Healer"
                return $false
            }
        }
        catch {
            Write-Log "Failed to fix GitHub authentication: $_" -Level "ERROR" -Category "Healer"
            return $false
        }
    }

    "Service Health" = {
        param($issue)
        Write-Log "Attempting to fix service health issues..." -Level "INFO" -Category "Healer"
        
        try {
            foreach ($service in $issue.Details.UnhealthyServices) {
                switch ($service) {
                    "MySQL" {
                        # Start MySQL service
                        Start-Service -Name "MySQL*" -ErrorAction SilentlyContinue
                        if (-not $?) {
                            # Try to start MySQL through Laragon
                            $laragonPath = "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe"
                            if (Test-Path $laragonPath) {
                                Start-Process -FilePath $laragonPath
                            }
                        }
                    }
                    "Redis" {
                        # Start Redis service
                        Start-Service -Name "Redis*" -ErrorAction SilentlyContinue
                        if (-not $?) {
                            # Try to start Redis through Laragon
                            $laragonPath = "C:\laragon\bin\redis\redis-server.exe"
                            if (Test-Path $laragonPath) {
                                Start-Process -FilePath $laragonPath
                            }
                        }
                    }
                    "Apache" {
                        # Start Apache service
                        Start-Service -Name "Apache*" -ErrorAction SilentlyContinue
                        if (-not $?) {
                            # Try to start Apache through Laragon
                            $laragonPath = "C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe"
                            if (Test-Path $laragonPath) {
                                Start-Process -FilePath $laragonPath
                            }
                        }
                    }
                }
            }
            
            Write-Log "Service health fixes attempted" -Level "SUCCESS" -Category "Healer"
            return $true
        }
        catch {
            Write-Log "Failed to fix service health issues: $_" -Level "ERROR" -Category "Healer"
            return $false
        }
    }
}

# Attempt to heal an issue
function Start-Healing {
    param (
        [hashtable]$Issue
    )

    Write-Log "Starting healing process for: $($Issue.Check)" -Level "INFO" -Category "Healer"

    $healer = $healingStrategies[$Issue.Check]
    if ($healer) {
        $success = & $healer $Issue
        if ($success) {
            Write-Log "Successfully healed: $($Issue.Check)" -Level "SUCCESS" -Category "Healer"
        } else {
            Write-Log "Failed to heal: $($Issue.Check)" -Level "ERROR" -Category "Healer"
        }
        return $success
    } else {
        Write-Log "No healing strategy available for: $($Issue.Check)" -Level "WARNING" -Category "Healer"
        return $false
    }
}

# Export functions
Export-ModuleMember -Function Start-Healing 