# Initialize MCP System
$ErrorActionPreference = "Stop"

# Import required modules
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
. (Join-Path $scriptPath "utils" "init-utils.ps1")

# Parse command line arguments
param (
    [string]$Environment = "local",
    [switch]$Force,
    [switch]$Verify,
    [switch]$AutoHeal
)

# Check prerequisites
function Test-Prerequisites {
    Write-Log "Checking prerequisites..." -Level "INFO" -Category "Init"
    
    try {
        $prerequisites = @{
            IsValid = $true
            Issues = @()
            Tools = @{}
            Services = @{}
            Network = @{
                RemoteAccess = $false
                SSL = $false
                Ports = @()
            }
        }
        
        # Check PowerShell version
        $psVersion = $PSVersionTable.PSVersion
        if ($psVersion.Major -lt 5) {
            $prerequisites.IsValid = $false
            $prerequisites.Issues += @{
                Type = "System"
                Message = "PowerShell version $($psVersion.ToString()) is not supported. Please upgrade to PowerShell 5.0 or later."
            }
        }
        
        # Check .NET Framework
        $dotNetVersion = (Get-ItemProperty "HKLM:\SOFTWARE\Microsoft\NET Framework Setup\NDP\v4\Full").Version
        if ([version]$dotNetVersion -lt [version]"4.7.2") {
            $prerequisites.IsValid = $false
            $prerequisites.Issues += @{
                Type = "System"
                Message = ".NET Framework version $dotNetVersion is not supported. Please upgrade to .NET Framework 4.7.2 or later."
            }
        }
        
        # Check required tools
        $requiredTools = @{
            "php" = "8.0"
            "composer" = "2.0"
            "node" = "16.0"
            "npm" = "8.0"
            "jq" = "1.6"
            "gh" = "2.0"
        }
        
        foreach ($tool in $requiredTools.Keys) {
            try {
                $version = & $tool --version 2>&1
                $prerequisites.Tools[$tool] = @{
                    Installed = $true
                    Version = $version
                    Required = $requiredTools[$tool]
                }
                
                if ([version]$version -lt [version]$requiredTools[$tool]) {
                    $prerequisites.IsValid = $false
                    $prerequisites.Issues += @{
                        Type = "Tool"
                        Message = "Tool '$tool' version $version is not supported. Please upgrade to version $($requiredTools[$tool]) or later."
                    }
                }
            }
            catch {
                $prerequisites.IsValid = $false
                $prerequisites.Tools[$tool] = @{
                    Installed = $false
                    Required = $requiredTools[$tool]
                }
                $prerequisites.Issues += @{
                    Type = "Tool"
                    Message = "Required tool '$tool' is not installed."
                }
            }
        }
        
        # Check services
        $services = @(
            @{ Name = "MySQL"; Port = 3306; Version = "8.0" },
            @{ Name = "Redis"; Port = 6379; Version = "6.0" },
            @{ Name = "Apache"; Port = 80; Version = "2.4" }
        )
        
        foreach ($service in $services) {
            try {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($service.Host, $service.Port).Wait(1000)
                $isConnected = $tcpClient.Connected
                $tcpClient.Close()
                
                $prerequisites.Services[$service.Name] = @{
                    Running = $isConnected
                    Port = $service.Port
                    Version = $service.Version
                }
                
                if (-not $isConnected) {
                    $prerequisites.IsValid = $false
                    $prerequisites.Issues += @{
                        Type = "Service"
                        Message = "Service '$($service.Name)' is not running."
                    }
                }
            }
            catch {
                $prerequisites.Services[$service.Name] = @{
                    Running = $false
                    Port = $service.Port
                    Version = $service.Version
                }
                
                $prerequisites.IsValid = $false
                $prerequisites.Issues += @{
                    Type = "Service"
                    Message = "Error checking service '$($service.Name)': $_"
                }
            }
        }
        
        # Check network
        try {
            # Check remote access
            $remoteHost = "api.example.com"
            $tcpClient = New-Object System.Net.Sockets.TcpClient
            $tcpClient.ConnectAsync($remoteHost, 443).Wait(1000)
            $prerequisites.Network.RemoteAccess = $tcpClient.Connected
            $tcpClient.Close()
            
            if (-not $prerequisites.Network.RemoteAccess) {
                $prerequisites.IsValid = $false
                $prerequisites.Issues += @{
                    Type = "Network"
                    Message = "Cannot connect to remote host: $remoteHost"
                }
            }
            
            # Check SSL
            $request = [System.Net.WebRequest]::Create("https://$remoteHost")
            $request.Method = "HEAD"
            $response = $request.GetResponse()
            $prerequisites.Network.SSL = $response.Server -match "nginx|apache"
            $response.Close()
            
            if (-not $prerequisites.Network.SSL) {
                $prerequisites.IsValid = $false
                $prerequisites.Issues += @{
                    Type = "Network"
                    Message = "SSL certificate validation failed for: $remoteHost"
                }
            }
            
            # Check required ports
            $requiredPorts = @(80, 443, 3306, 6379)
            foreach ($port in $requiredPorts) {
                $tcpClient = New-Object System.Net.Sockets.TcpClient
                $tcpClient.ConnectAsync($remoteHost, $port).Wait(1000)
                $isOpen = $tcpClient.Connected
                $tcpClient.Close()
                
                $prerequisites.Network.Ports += @{
                    Port = $port
                    Open = $isOpen
                }
                
                if (-not $isOpen) {
                    $prerequisites.IsValid = $false
                    $prerequisites.Issues += @{
                        Type = "Network"
                        Message = "Port $port is not open on $remoteHost"
                    }
                }
            }
        }
        catch {
            $prerequisites.IsValid = $false
            $prerequisites.Issues += @{
                Type = "Network"
                Message = "Error checking network: $_"
            }
        }
        
        Write-Log "Prerequisites check completed" -Level "SUCCESS" -Category "Init"
        return $prerequisites
    }
    catch {
        Write-Log "Error checking prerequisites: $_" -Level "ERROR" -Category "Init"
        return $null
    }
}

# Initialize directories
function Initialize-Directories {
    Write-Log "Initializing directories..." -Level "INFO" -Category "Init"
    
    try {
        # Create required directories
        $directories = @(
            "logs",
            "logs/general",
            "logs/services",
            "logs/data",
            "logs/config",
            "logs/audit",
            "tests",
            "tests/unit",
            "tests/integration",
            "tests/e2e"
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
    Write-Log "Initializing environment..." -Level "INFO" -Category "Init"
    
    try {
        # Load environment configuration
        $envConfig = Get-EnvironmentConfig -Environment $Environment
        if ($null -eq $envConfig) {
            Write-Log "Failed to load environment configuration" -Level "ERROR" -Category "Init"
            return $false
        }
        
        # Initialize MCP
        $initResult = Initialize-MCP -Environment $Environment -Verify:$Verify -AutoHeal:$AutoHeal
        if (-not $initResult) {
            Write-Log "Failed to initialize MCP" -Level "ERROR" -Category "Init"
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

# Main initialization
try {
    # Check prerequisites
    $prerequisites = Test-Prerequisites
    if ($null -eq $prerequisites) {
        Write-Log "Failed to check prerequisites" -Level "ERROR" -Category "Init"
        exit 1
    }
    
    if (-not $prerequisites.IsValid -and -not $Force) {
        Write-Log "Prerequisites check failed" -Level "ERROR" -Category "Init"
        foreach ($issue in $prerequisites.Issues) {
            Write-Log "$($issue.Type): $($issue.Message)" -Level "ERROR" -Category "Init"
        }
        exit 1
    }
    
    # Initialize directories
    if (-not (Initialize-Directories)) {
        Write-Log "Failed to initialize directories" -Level "ERROR" -Category "Init"
        exit 1
    }
    
    # Initialize environment
    if (-not (Initialize-Environment)) {
        Write-Log "Failed to initialize environment" -Level "ERROR" -Category "Init"
        exit 1
    }
    
    Write-Log "MCP system initialized successfully" -Level "SUCCESS" -Category "Init"
    exit 0
}
catch {
    Write-Log "Error initializing MCP system: $_" -Level "ERROR" -Category "Init"
    exit 1
} 