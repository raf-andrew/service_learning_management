# Check Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [string]$Environment = "local",
    [switch]$AutoHeal,
    [switch]$Detailed
)

# Check system health
function Test-SystemHealth {
    Write-Log "Checking system health..." -Level "INFO" -Category "HealthCheck"
    
    try {
        $health = @{
            Status = "Healthy"
            Issues = @()
            Services = @()
            GitHub = @{
                Authentication = $false
                Scopes = @()
                Actions = $false
            }
        }
        
        # Check PowerShell version
        $psVersion = $PSVersionTable.PSVersion
        if ($psVersion.Major -lt 5) {
            $health.Status = "Unhealthy"
            $health.Issues += @{
                Type = "System"
                Message = "PowerShell version $($psVersion.ToString()) is not supported. Please upgrade to PowerShell 5.0 or later."
            }
        }
        
        # Check required tools
        $requiredTools = @("php", "composer", "node", "npm", "jq", "gh")
        foreach ($tool in $requiredTools) {
            try {
                $null = Get-Command $tool -ErrorAction Stop
            }
            catch {
                $health.Status = "Unhealthy"
                $health.Issues += @{
                    Type = "System"
                    Message = "Required tool '$tool' is not installed."
                }
            }
        }
        
        # Check services
        $services = @(
            @{ Name = "MySQL"; Host = "localhost"; Port = 3306 },
            @{ Name = "Redis"; Host = "localhost"; Port = 6379 },
            @{ Name = "Apache"; Host = "localhost"; Port = 80 }
        )
        
        foreach ($service in $services) {
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($service.Host, $service.Port).Wait(1000)
                $isConnected = $tcpClient.Connected
                $tcpClient.Close()
                
                $health.Services += @{
                    Name = $service.Name
                    Status = $isConnected ? "Running" : "Stopped"
                }
                
                if (-not $isConnected) {
                    $health.Status = "Unhealthy"
                    $health.Issues += @{
                        Type = "Service"
                        Message = "Service '$($service.Name)' is not running."
                    }
                }
            }
            catch {
                $health.Services += @{
                    Name = $service.Name
                    Status = "Error"
                }
                
                $health.Status = "Unhealthy"
                $health.Issues += @{
                    Type = "Service"
                    Message = "Error checking service '$($service.Name)': $_"
                }
            }
        }
        
        # Check GitHub integration
        try {
            $githubStatus = Get-GitHubStatus
            $health.GitHub.Authentication = $githubStatus.Authentication
            $health.GitHub.Scopes = $githubStatus.Scopes
            $health.GitHub.Actions = $githubStatus.Actions
            
            if (-not $githubStatus.Authentication) {
                $health.Status = "Unhealthy"
                $health.Issues += @{
                    Type = "GitHub"
                    Message = "GitHub authentication failed."
                }
            }
            
            if (-not $githubStatus.Actions) {
                $health.Status = "Unhealthy"
                $health.Issues += @{
                    Type = "GitHub"
                    Message = "GitHub Actions is not available."
                }
            }
        }
        catch {
            $health.Status = "Unhealthy"
            $health.Issues += @{
                Type = "GitHub"
                Message = "Error checking GitHub integration: $_"
            }
        }
        
        Write-Log "System health check completed" -Level "SUCCESS" -Category "HealthCheck"
        return $health
    }
    catch {
        Write-Log "Error checking system health: $_" -Level "ERROR" -Category "HealthCheck"
        return $null
    }
}

# Main execution
try {
    Write-Host "Checking Health Monitoring System..."
    Write-Host "================================="
    
    # Switch to environment
    if (-not (Switch-Environment -Environment $Environment)) {
        Write-Host "Failed to switch environment" -ForegroundColor "Red"
        exit 1
    }
    
    # Check health
    $health = Test-SystemHealth
    
    if ($null -eq $health) {
        Write-Host "Failed to check health" -ForegroundColor "Red"
        exit 1
    }
    
    # Display health status
    Write-Host "`nStatus: $($health.Status)" -ForegroundColor ($health.Status -eq "Healthy" ? "Green" : "Red")
    
    if ($health.Issues.Count -gt 0) {
        Write-Host "`nIssues:"
        foreach ($issue in $health.Issues) {
            Write-Host "  - [$($issue.Type)] $($issue.Message)" -ForegroundColor "Yellow"
        }
    }
    
    Write-Host "`nServices:"
    foreach ($service in $health.Services) {
        Write-Host "  - $($service.Name): $($service.Status)" -ForegroundColor ($service.Status -eq "Running" ? "Green" : "Red")
    }
    
    Write-Host "`nGitHub Integration:"
    Write-Host "  - Authentication: $($health.GitHub.Authentication)"
    Write-Host "  - Scopes: $($health.GitHub.Scopes -join ', ')"
    Write-Host "  - Actions: $($health.GitHub.Actions)"
    
    if ($Detailed) {
        # Get environment status
        Write-Host "`nEnvironment Status:"
        $envStatus = Get-EnvironmentStatus
        Write-Host "  - Current Environment: $($envStatus.CurrentEnvironment)"
        Write-Host "  - Services:"
        foreach ($service in $envStatus.Services) {
            Write-Host "    - $($service.Name): $($service.Status)" -ForegroundColor ($service.Status -eq "Running" ? "Green" : "Red")
        }
        
        # Get log status
        Write-Host "`nLog Status:"
        $logDir = Join-Path $scriptPath "logs"
        if (Test-Path $logDir) {
            $logs = Get-ChildItem $logDir -File
            foreach ($log in $logs) {
                $size = [math]::Round($log.Length / 1KB, 2)
                Write-Host "  - $($log.Name): $size KB"
            }
        }
    }
    
    # Auto-heal if needed
    if ($AutoHeal -and $health.Status -eq "Unhealthy") {
        Write-Host "`nAttempting to heal issues..."
        
        foreach ($issue in $health.Issues) {
            Write-Host "  - Healing $($issue.Type) issue: $($issue.Message)"
            
            switch ($issue.Type) {
                "System" {
                    # Handle system issues
                    if ($issue.Message -like "*PowerShell version*") {
                        Write-Host "    - Please upgrade PowerShell manually"
                    }
                    elseif ($issue.Message -like "*Required tool*") {
                        $tool = $issue.Message -replace "Required tool '([^']+)'.*", '$1'
                        Write-Host "    - Installing $tool..."
                        # Add installation logic here
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
    if ($health.Status -eq "Healthy") {
        Write-Host "`nHealth check completed successfully!" -ForegroundColor "Green"
        exit 0
    } else {
        Write-Host "`nHealth check completed with issues" -ForegroundColor "Yellow"
        exit 1
    }
}
catch {
    Write-Log "Error checking health monitoring system: $_" -Level "ERROR" -Category "HealthCheck"
    exit 1
} 