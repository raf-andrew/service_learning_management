# MCP Main Script
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "logger.ps1")
. (Join-Path $scriptPath "utils" "services.ps1")
. (Join-Path $scriptPath "utils" "data.ps1")
. (Join-Path $scriptPath "utils" "logs.ps1")
. (Join-Path $scriptPath "utils" "config.ps1")
. (Join-Path $scriptPath "utils" "environment.ps1")
. (Join-Path $scriptPath "utils" "github.ps1")
. (Join-Path $scriptPath "tests" "test-mcp.ps1")

# Parse command line arguments
param (
    [Parameter(Mandatory=$true)]
    [ValidateSet("init", "start", "stop", "restart", "status", "test", "help")]
    [string]$Command,
    
    [string]$Environment = "development",
    
    [switch]$Verify,
    
    [switch]$AutoHeal,
    
    [switch]$Force,
    
    [string]$Service,
    
    [string]$Category,
    
    [hashtable]$Parameters = @{}
)

# Main Functions
function Initialize-MCPSystem {
    param (
        [string]$Environment,
        
        [switch]$Verify,
        
        [switch]$AutoHeal
    )
    
    Write-Log "Initializing MCP system..." -Level "INFO" -Category "MCP"
    
    try {
        # Initialize MCP
        if (-not (Initialize-MCP -Environment $Environment -Verify:$Verify -AutoHeal:$AutoHeal)) {
            Write-Log "Failed to initialize MCP" -Level "ERROR" -Category "MCP"
            return $false
        }
        
        Write-Log "MCP system initialized successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error initializing MCP system: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

function Start-MCPSystem {
    param (
        [string]$Environment,
        
        [string]$Service,
        
        [switch]$Verify,
        
        [switch]$AutoHeal
    )
    
    Write-Log "Starting MCP system..." -Level "INFO" -Category "MCP"
    
    try {
        if ($Service) {
            # Start specific service
            if (-not (Start-Service -Service $Service -Verify:$Verify -AutoHeal:$AutoHeal)) {
                Write-Log "Failed to start service: $Service" -Level "ERROR" -Category "MCP"
                return $false
            }
        }
        else {
            # Start all services
            $services = @("mysql", "redis", "apache")
            
            foreach ($svc in $services) {
                if (-not (Start-Service -Service $svc -Verify:$Verify -AutoHeal:$AutoHeal)) {
                    Write-Log "Failed to start service: $svc" -Level "ERROR" -Category "MCP"
                    return $false
                }
            }
        }
        
        Write-Log "MCP system started successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error starting MCP system: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

function Stop-MCPSystem {
    param (
        [string]$Service,
        
        [switch]$Force
    )
    
    Write-Log "Stopping MCP system..." -Level "INFO" -Category "MCP"
    
    try {
        if ($Service) {
            # Stop specific service
            if (-not (Stop-Service -Service $Service -Force:$Force)) {
                Write-Log "Failed to stop service: $Service" -Level "ERROR" -Category "MCP"
                return $false
            }
        }
        else {
            # Stop all services
            $services = @("apache", "redis", "mysql")
            
            foreach ($svc in $services) {
                if (-not (Stop-Service -Service $svc -Force:$Force)) {
                    Write-Log "Failed to stop service: $svc" -Level "ERROR" -Category "MCP"
                    return $false
                }
            }
        }
        
        Write-Log "MCP system stopped successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error stopping MCP system: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

function Restart-MCPSystem {
    param (
        [string]$Environment,
        
        [string]$Service,
        
        [switch]$Verify,
        
        [switch]$AutoHeal,
        
        [switch]$Force
    )
    
    Write-Log "Restarting MCP system..." -Level "INFO" -Category "MCP"
    
    try {
        if (-not (Stop-MCPSystem -Service $Service -Force:$Force)) {
            return $false
        }
        
        if (-not (Start-MCPSystem -Environment $Environment -Service $Service -Verify:$Verify -AutoHeal:$AutoHeal)) {
            return $false
        }
        
        Write-Log "MCP system restarted successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error restarting MCP system: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

function Get-MCPSystemStatus {
    param (
        [string]$Service,
        
        [switch]$Detailed
    )
    
    Write-Log "Getting MCP system status..." -Level "INFO" -Category "MCP"
    
    try {
        if ($Service) {
            # Get specific service status
            $status = Get-ServiceStatus -Service $Service -Detailed:$Detailed
            
            if ($null -eq $status) {
                Write-Log "Failed to get service status: $Service" -Level "ERROR" -Category "MCP"
                return $null
            }
            
            return $status
        }
        else {
            # Get all system status
            $status = @{
                "MCP" = Get-MCPStatus -Detailed:$Detailed
                "Services" = @{}
                "Data" = Get-Data -Source "status" -Detailed:$Detailed
                "Logs" = Get-LogStatus -Detailed:$Detailed
                "Config" = Get-ConfigStatus -Detailed:$Detailed
                "Environment" = Get-EnvironmentStatus -Detailed:$Detailed
                "GitHub" = Get-GitHubStatus -Detailed:$Detailed
            }
            
            $services = @("mysql", "redis", "apache")
            
            foreach ($svc in $services) {
                $status.Services[$svc] = Get-ServiceStatus -Service $svc -Detailed:$Detailed
            }
            
            return $status
        }
    }
    catch {
        Write-Log "Error getting MCP system status: $_" -Level "ERROR" -Category "MCP"
        return $null
    }
}

function Test-MCPSystem {
    param (
        [string]$Category
    )
    
    Write-Log "Testing MCP system..." -Level "INFO" -Category "MCP"
    
    try {
        if ($Category) {
            # Test specific category
            if (-not (Test-MCPCategory -Category $Category)) {
                Write-Log "Failed to test category: $Category" -Level "ERROR" -Category "MCP"
                return $false
            }
        }
        else {
            # Test all categories
            if (-not (Test-MCPAll)) {
                Write-Log "Failed to test all categories" -Level "ERROR" -Category "MCP"
                return $false
            }
        }
        
        Write-Log "MCP system tested successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error testing MCP system: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

function Show-MCPHelp {
    Write-Log "MCP System Help" -Level "INFO" -Category "MCP"
    Write-Log "Usage: .\mcp.ps1 -Command <command> [options]" -Level "INFO" -Category "MCP"
    Write-Log "" -Level "INFO" -Category "MCP"
    Write-Log "Commands:" -Level "INFO" -Category "MCP"
    Write-Log "  init     - Initialize MCP system" -Level "INFO" -Category "MCP"
    Write-Log "  start    - Start MCP system" -Level "INFO" -Category "MCP"
    Write-Log "  stop     - Stop MCP system" -Level "INFO" -Category "MCP"
    Write-Log "  restart  - Restart MCP system" -Level "INFO" -Category "MCP"
    Write-Log "  status   - Get MCP system status" -Level "INFO" -Category "MCP"
    Write-Log "  test     - Test MCP system" -Level "INFO" -Category "MCP"
    Write-Log "  help     - Show this help message" -Level "INFO" -Category "MCP"
    Write-Log "" -Level "INFO" -Category "MCP"
    Write-Log "Options:" -Level "INFO" -Category "MCP"
    Write-Log "  -Environment <env>  - Environment to use (default: development)" -Level "INFO" -Category "MCP"
    Write-Log "  -Verify            - Verify operations" -Level "INFO" -Category "MCP"
    Write-Log "  -AutoHeal          - Auto-heal issues" -Level "INFO" -Category "MCP"
    Write-Log "  -Force             - Force operations" -Level "INFO" -Category "MCP"
    Write-Log "  -Service <svc>     - Service to operate on" -Level "INFO" -Category "MCP"
    Write-Log "  -Category <cat>    - Category to test" -Level "INFO" -Category "MCP"
    Write-Log "  -Parameters <par>  - Additional parameters" -Level "INFO" -Category "MCP"
}

# Main execution
try {
    switch ($Command) {
        "init" {
            Initialize-MCPSystem -Environment $Environment -Verify:$Verify -AutoHeal:$AutoHeal
        }
        "start" {
            Start-MCPSystem -Environment $Environment -Service $Service -Verify:$Verify -AutoHeal:$AutoHeal
        }
        "stop" {
            Stop-MCPSystem -Service $Service -Force:$Force
        }
        "restart" {
            Restart-MCPSystem -Environment $Environment -Service $Service -Verify:$Verify -AutoHeal:$AutoHeal -Force:$Force
        }
        "status" {
            Get-MCPSystemStatus -Service $Service -Detailed:$Parameters.Detailed
        }
        "test" {
            Test-MCPSystem -Category $Category
        }
        "help" {
            Show-MCPHelp
        }
        default {
            Write-Log "Invalid command: $Command" -Level "ERROR" -Category "MCP"
            Show-MCPHelp
            exit 1
        }
    }
}
catch {
    Write-Log "Error executing command: $_" -Level "ERROR" -Category "MCP"
    exit 1
} 