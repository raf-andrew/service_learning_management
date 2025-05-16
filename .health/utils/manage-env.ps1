# Manage Health Monitoring Environment
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "logger.ps1")
. (Join-Path $scriptPath "environment.ps1")
. (Join-Path $scriptPath "github.ps1")

# Parse command line arguments
param (
    [Parameter(Mandatory=$true)]
    [ValidateSet("local", "remote")]
    [string]$Environment,
    
    [switch]$Verify,
    [switch]$AutoHeal,
    [switch]$Detailed
)

# Switch environment
function Switch-Environment {
    param (
        [string]$Environment
    )
    
    Write-Log "Switching to $Environment environment..." -Level "INFO" -Category "Environment"
    
    try {
        # Get environment configuration
        $config = Get-EnvironmentConfig -Environment $Environment
        
        if ($null -eq $config) {
            Write-Log "Environment configuration not found" -Level "ERROR" -Category "Environment"
            return $false
        }
        
        # Update environment variables
        $env:HEALTH_ENV = $Environment
        $env:HEALTH_SERVICES = $config.Services | ConvertTo-Json
        $env:HEALTH_GITHUB_TOKEN = $config.GitHubToken
        $env:HEALTH_LOG_LEVEL = $config.LogLevel
        
        Write-Log "Environment switched successfully" -Level "SUCCESS" -Category "Environment"
        return $true
    }
    catch {
        Write-Log "Error switching environment: $_" -Level "ERROR" -Category "Environment"
        return $false
    }
}

# Verify environment
function Test-Environment {
    param (
        [string]$Environment
    )
    
    Write-Log "Verifying $Environment environment..." -Level "INFO" -Category "Environment"
    
    try {
        $status = @{
            IsValid = $true
            Issues = @()
            Services = @()
            GitHub = @{
                Authentication = $false
                Scopes = @()
                Actions = $false
            }
        }
        
        # Check environment variables
        if (-not $env:HEALTH_ENV) {
            $status.IsValid = $false
            $status.Issues += @{
                Type = "Environment"
                Message = "HEALTH_ENV environment variable is not set."
            }
        }
        
        if (-not $env:HEALTH_SERVICES) {
            $status.IsValid = $false
            $status.Issues += @{
                Type = "Environment"
                Message = "HEALTH_SERVICES environment variable is not set."
            }
        }
        
        if (-not $env:HEALTH_GITHUB_TOKEN) {
            $status.IsValid = $false
            $status.Issues += @{
                Type = "Environment"
                Message = "HEALTH_GITHUB_TOKEN environment variable is not set."
            }
        }
        
        if (-not $env:HEALTH_LOG_LEVEL) {
            $status.IsValid = $false
            $status.Issues += @{
                Type = "Environment"
                Message = "HEALTH_LOG_LEVEL environment variable is not set."
            }
        }
        
        # Check services
        $services = $env:HEALTH_SERVICES | ConvertFrom-Json
        foreach ($service in $services) {
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($service.Host, $service.Port).Wait(1000)
                $isConnected = $tcpClient.Connected
                $tcpClient.Close()
                
                $status.Services += @{
                    Name = $service.Name
                    Status = $isConnected ? "Running" : "Stopped"
                }
                
                if (-not $isConnected) {
                    $status.IsValid = $false
                    $status.Issues += @{
                        Type = "Service"
                        Message = "Service '$($service.Name)' is not running."
                    }
                }
            }
            catch {
                $status.Services += @{
                    Name = $service.Name
                    Status = "Error"
                }
                
                $status.IsValid = $false
                $status.Issues += @{
                    Type = "Service"
                    Message = "Error checking service '$($service.Name)': $_"
                }
            }
        }
        
        # Check GitHub integration
        try {
            $githubStatus = Get-GitHubStatus
            $status.GitHub.Authentication = $githubStatus.Authentication
            $status.GitHub.Scopes = $githubStatus.Scopes
            $status.GitHub.Actions = $githubStatus.Actions
            
            if (-not $githubStatus.Authentication) {
                $status.IsValid = $false
                $status.Issues += @{
                    Type = "GitHub"
                    Message = "GitHub authentication failed."
                }
            }
            
            if (-not $githubStatus.Actions) {
                $status.IsValid = $false
                $status.Issues += @{
                    Type = "GitHub"
                    Message = "GitHub Actions is not available."
                }
            }
        }
        catch {
            $status.IsValid = $false
            $status.Issues += @{
                Type = "GitHub"
                Message = "Error checking GitHub integration: $_"
            }
        }
        
        Write-Log "Environment verification completed" -Level "SUCCESS" -Category "Environment"
        return $status
    }
    catch {
        Write-Log "Error verifying environment: $_" -Level "ERROR" -Category "Environment"
        return $null
    }
}

# Main execution
try {
    Write-Host "Managing Environment..."
    Write-Host "====================="
    
    # Switch environment
    if (-not (Switch-Environment -Environment $Environment)) {
        Write-Host "Failed to switch environment" -ForegroundColor "Red"
        exit 1
    }
    
    # Verify environment if requested
    if ($Verify) {
        $status = Test-Environment -Environment $Environment
        
        if ($null -eq $status) {
            Write-Host "Failed to verify environment" -ForegroundColor "Red"
            exit 1
        }
        
        # Display status
        Write-Host "`nStatus: $($status.IsValid ? 'Valid' : 'Invalid')" -ForegroundColor ($status.IsValid ? "Green" : "Red")
        
        if ($status.Issues.Count -gt 0) {
            Write-Host "`nIssues:"
            foreach ($issue in $status.Issues) {
                Write-Host "  - [$($issue.Type)] $($issue.Message)" -ForegroundColor "Yellow"
            }
        }
        
        Write-Host "`nServices:"
        foreach ($service in $status.Services) {
            Write-Host "  - $($service.Name): $($service.Status)" -ForegroundColor ($service.Status -eq "Running" ? "Green" : "Red")
        }
        
        Write-Host "`nGitHub Integration:"
        Write-Host "  - Authentication: $($status.GitHub.Authentication)"
        Write-Host "  - Scopes: $($status.GitHub.Scopes -join ', ')"
        Write-Host "  - Actions: $($status.GitHub.Actions)"
        
        # Auto-heal if needed
        if ($AutoHeal -and -not $status.IsValid) {
            Write-Host "`nAttempting to heal issues..."
            
            foreach ($issue in $status.Issues) {
                Write-Host "  - Healing $($issue.Type) issue: $($issue.Message)"
                
                switch ($issue.Type) {
                    "Environment" {
                        # Handle environment issues
                        if ($issue.Message -like "*HEALTH_ENV*") {
                            Write-Host "    - Setting HEALTH_ENV environment variable..."
                            $env:HEALTH_ENV = $Environment
                        }
                        elseif ($issue.Message -like "*HEALTH_SERVICES*") {
                            Write-Host "    - Setting HEALTH_SERVICES environment variable..."
                            $config = Get-EnvironmentConfig -Environment $Environment
                            $env:HEALTH_SERVICES = $config.Services | ConvertTo-Json
                        }
                        elseif ($issue.Message -like "*HEALTH_GITHUB_TOKEN*") {
                            Write-Host "    - Setting HEALTH_GITHUB_TOKEN environment variable..."
                            $config = Get-EnvironmentConfig -Environment $Environment
                            $env:HEALTH_GITHUB_TOKEN = $config.GitHubToken
                        }
                        elseif ($issue.Message -like "*HEALTH_LOG_LEVEL*") {
                            Write-Host "    - Setting HEALTH_LOG_LEVEL environment variable..."
                            $config = Get-EnvironmentConfig -Environment $Environment
                            $env:HEALTH_LOG_LEVEL = $config.LogLevel
                        }
                    }
                    "Service" {
                        # Handle service issues
                        $service = $issue.Message -replace "Service '([^']+)'.*", '$1'
                        Write-Host "    - Starting $service..."
                        # Add service start logic here
                    }
                    "GitHub" {
                        # Handle GitHub issues
                        if ($issue.Message -like "*authentication*") {
                            Write-Host "    - Fixing GitHub authentication..."
                            # Add authentication fix logic here
                        }
                        elseif ($issue.Message -like "*Actions*") {
                            Write-Host "    - Fixing GitHub Actions..."
                            # Add Actions fix logic here
                        }
                    }
                }
            }
        }
        
        # Exit with appropriate code
        if ($status.IsValid) {
            Write-Host "`nEnvironment verification completed successfully!" -ForegroundColor "Green"
            exit 0
        } else {
            Write-Host "`nEnvironment verification completed with issues" -ForegroundColor "Yellow"
            exit 1
        }
    }
    
    if ($Detailed) {
        # Get environment configuration
        $config = Get-EnvironmentConfig -Environment $Environment
        
        Write-Host "`nEnvironment Configuration:"
        Write-Host "  - Name: $($config.Name)"
        Write-Host "  - Description: $($config.Description)"
        Write-Host "  - Services:"
        foreach ($service in $config.Services) {
            Write-Host "    - $($service.Name): $($service.Host):$($service.Port)"
        }
        Write-Host "  - Log Level: $($config.LogLevel)"
    }
    
    Write-Host "`nEnvironment switched successfully!" -ForegroundColor "Green"
}
catch {
    Write-Log "Error managing environment: $_" -Level "ERROR" -Category "Environment"
    exit 1
} 