# MCP Setup Runner
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$mcpPath = Join-Path -Path $scriptPath -ChildPath ".."
$utilsPath = Join-Path -Path $mcpPath -ChildPath "utils"
. (Join-Path -Path $utilsPath -ChildPath "init.ps1")

# Run MCP Setup
function Start-MCPSetup {
    param (
        [string]$Environment = "local",
        [switch]$Force,
        [switch]$Verify,
        [switch]$AutoHeal,
        [switch]$SkipTests
    )
    
    Write-Log "Starting MCP setup..." -Level "INFO" -Category "MCP"
    
    try {
        # Check prerequisites
        $prerequisites = Test-Prerequisites -Environment $Environment -AutoHeal:$AutoHeal
        if (-not $prerequisites.Status) {
            Write-Log "Prerequisite check failed" -Level "ERROR" -Category "MCP"
            if (-not $Force) {
                return $false
            }
        }
        
        # Initialize MCP server
        if (-not (Initialize-MCPServer -Environment $Environment)) {
            Write-Log "Failed to initialize MCP server" -Level "ERROR" -Category "MCP"
            if (-not $Force) {
                return $false
            }
        }
        
        # Start MCP server
        if (-not (Start-MCPServer -Environment $Environment)) {
            Write-Log "Failed to start MCP server" -Level "ERROR" -Category "MCP"
            if (-not $Force) {
                return $false
            }
        }
        
        # Run tests if not skipped
        if (-not $SkipTests) {
            $testResults = Start-MCPTests -Environment $Environment -Force:$Force
            if (-not $testResults.Status) {
                Write-Log "Tests failed" -Level "ERROR" -Category "MCP"
                if (-not $Force) {
                    return $false
                }
            }
        }
        
        # Start health monitor
        if (-not (Start-HealthMonitor -Environment $Environment -AutoHeal:$AutoHeal)) {
            Write-Log "Failed to start health monitor" -Level "ERROR" -Category "MCP"
            if (-not $Force) {
                return $false
            }
        }
        
        # Export initial system report
        $reportPath = Join-Path -Path $mcpPath -ChildPath "logs/setup-report.json"
        Export-SystemReport -Path $reportPath -IncludeMetrics:$true -Environment $Environment
        
        Write-Log "MCP setup completed successfully" -Level "SUCCESS" -Category "MCP"
        return $true
    }
    catch {
        Write-Log "Error during MCP setup: $_" -Level "ERROR" -Category "MCP"
        return $false
    }
}

# Run setup if script is executed directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    $setupResult = Start-MCPSetup -Environment "local" -AutoHeal:$true
    if (-not $setupResult) {
        Write-Error "MCP setup failed"
        exit 1
    }
    exit 0
}

# Export functions
Export-ModuleMember -Function @(
    "Start-MCPSetup"
) 