# Initialize Health Monitoring System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")

# Parse command line arguments
param (
    [string]$Environment = "local",
    [switch]$Force,
    [switch]$Verify,
    [switch]$AutoHeal
)

# Initialize directories
function Initialize-Directories {
    Write-Log "Initializing directories..." -Level "INFO" -Category "Init"
    
    try {
        # Create required directories
        $directories = @(
            "logs",
            "logs/General",
            "logs/HealthChecks",
            "logs/Monitoring",
            "logs/SelfHealing",
            "logs/Tests"
        )
        
        foreach ($dir in $directories) {
            $path = Join-Path $scriptPath $dir
            if (-not (Test-Path $path)) {
                New-Item -ItemType Directory -Path $path -Force | Out-Null
                Write-Log "Created directory: $dir" -Level "INFO" -Category "Init"
            }
        }
        
        Write-Log "Directories initialized successfully" -Level "SUCCESS" -Category "Init"
        return $true
    }
    catch {
        Write-Log "Error initializing directories: $_" -Level "ERROR" -Category "Init"
        return $false
    }
}

# Initialize environment
function Initialize-Environment {
    param (
        [string]$Environment
    )
    
    Write-Log "Initializing environment: $Environment" -Level "INFO" -Category "Init"
    
    try {
        # Switch to environment
        if (-not (Switch-Environment -Environment $Environment)) {
            return $false
        }
        
        # Verify GitHub authentication
        if (-not (Test-GitHubAuth)) {
            Write-Log "GitHub authentication failed" -Level "ERROR" -Category "Init"
            return $false
        }
        
        Write-Log "Environment initialized successfully" -Level "SUCCESS" -Category "Init"
        return $true
    }
    catch {
        Write-Log "Error initializing environment: $_" -Level "ERROR" -Category "Init"
        return $false
    }
}

# Initialize services
function Initialize-Services {
    param (
        [string]$Environment
    )
    
    Write-Log "Initializing services..." -Level "INFO" -Category "Init"
    
    try {
        $config = Get-EnvironmentConfig -Environment $Environment
        $services = @("MySQL", "Redis", "Apache")
        $failed = @()
        
        foreach ($service in $services) {
            $serviceConfig = Get-ServiceConfig -Service $service
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $result = $tcpClient.BeginConnect($serviceConfig.Host, $serviceConfig.Port, $null, $null)
                $success = $result.AsyncWaitHandle.WaitOne(1000)
                $tcpClient.Close()
                
                if (-not $success) {
                    $failed += $service
                }
            }
            catch {
                $failed += $service
            }
        }
        
        if ($failed.Count -gt 0) {
            Write-Log "Failed to connect to services: $($failed -join ', ')" -Level "ERROR" -Category "Init"
            return $false
        }
        
        Write-Log "Services initialized successfully" -Level "SUCCESS" -Category "Init"
        return $true
    }
    catch {
        Write-Log "Error initializing services: $_" -Level "ERROR" -Category "Init"
        return $false
    }
}

# Main initialization
try {
    Write-Host "Initializing Health Monitoring System..."
    Write-Host "====================================="
    
    # Initialize directories
    if (-not (Initialize-Directories)) {
        if (-not $Force) {
            exit 1
        }
    }
    
    # Initialize environment
    if (-not (Initialize-Environment -Environment $Environment)) {
        if ($AutoHeal) {
            Write-Host "`nAttempting to heal environment..."
            $issue = @{
                Check = "Environment"
                Category = "Environment"
                Message = "Environment initialization failed"
                Details = @{ Environment = $Environment }
            }
            
            if (Start-Healing -Issue $issue) {
                Write-Host "Environment healed successfully" -ForegroundColor "Green"
            } else {
                Write-Host "Failed to heal environment" -ForegroundColor "Red"
                if (-not $Force) {
                    exit 1
                }
            }
        } elseif (-not $Force) {
            exit 1
        }
    }
    
    # Initialize services
    if (-not (Initialize-Services -Environment $Environment)) {
        if ($AutoHeal) {
            Write-Host "`nAttempting to heal services..."
            $issue = @{
                Check = "Services"
                Category = "Services"
                Message = "Service initialization failed"
                Details = @{ Environment = $Environment }
            }
            
            if (Start-Healing -Issue $issue) {
                Write-Host "Services healed successfully" -ForegroundColor "Green"
            } else {
                Write-Host "Failed to heal services" -ForegroundColor "Red"
                if (-not $Force) {
                    exit 1
                }
            }
        } elseif (-not $Force) {
            exit 1
        }
    }
    
    # Verify initialization if requested
    if ($Verify) {
        Write-Host "`nVerifying initialization..."
        if (-not (Test-Environment -Environment $Environment)) {
            Write-Host "Initialization verification failed" -ForegroundColor "Red"
            if (-not $Force) {
                exit 1
            }
        } else {
            Write-Host "Initialization verified successfully" -ForegroundColor "Green"
        }
    }
    
    Write-Host "`nHealth Monitoring System initialized successfully!" -ForegroundColor "Green"
}
catch {
    Write-Log "Error during initialization: $_" -Level "ERROR" -Category "Init"
    exit 1
} 