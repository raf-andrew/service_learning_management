# Token management script for Codespaces setup
$ErrorActionPreference = "Stop"

# Color definitions for output
$ColorRed = [System.ConsoleColor]::Red
$ColorGreen = [System.ConsoleColor]::Green
$ColorYellow = [System.ConsoleColor]::Yellow

# Configuration
$TOKEN_DIR = ".codespaces/testing/tokens"
$ENV_FILE = ".env"
$ENCRYPTION_KEY_FILE = ".codespaces/testing/keys/encryption.key"

# Logging function
function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "ERROR" { $ColorRed }
        "WARN" { $ColorYellow }
        "INFO" { $ColorGreen }
        default { [System.ConsoleColor]::White }
    }
    
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

# Ensure required directories exist
function Initialize-Directories {
    Write-Log "Initializing token management directories..."
    
    $directories = @(
        $TOKEN_DIR,
        (Split-Path $ENCRYPTION_KEY_FILE -Parent)
    )
    
    foreach ($dir in $directories) {
        if (-not (Test-Path $dir)) {
            try {
                New-Item -ItemType Directory -Path $dir -Force | Out-Null
                Write-Log "Created directory: $dir" "INFO"
            }
            catch {
                Write-Log "Failed to create directory: $dir" "ERROR"
                return $false
            }
        }
    }
    return $true
}

# Generate encryption key if not exists
function Initialize-EncryptionKey {
    Write-Log "Checking encryption key..."
    
    if (-not (Test-Path $ENCRYPTION_KEY_FILE)) {
        try {
            $key = [System.Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
            Set-Content -Path $ENCRYPTION_KEY_FILE -Value $key -Force
            Write-Log "Generated new encryption key" "INFO"
        }
        catch {
            Write-Log "Failed to generate encryption key" "ERROR"
            return $false
        }
    }
    return $true
}

# Encrypt token
function Protect-Token {
    param(
        [string]$Token,
        [string]$TokenType
    )
    
    Write-Log "Encrypting $TokenType token..."
    
    try {
        $key = Get-Content $ENCRYPTION_KEY_FILE
        $secureString = ConvertTo-SecureString $Token -AsPlainText -Force
        $encryptedToken = ConvertFrom-SecureString $secureString -Key ([System.Convert]::FromBase64String($key))
        
        $tokenFile = Join-Path $TOKEN_DIR "$TokenType.token"
        Set-Content -Path $tokenFile -Value $encryptedToken -Force
        
        Write-Log "Token encrypted and stored successfully" "INFO"
        return $true
    }
    catch {
        Write-Log "Failed to encrypt token: $_" "ERROR"
        return $false
    }
}

# Decrypt token
function Unprotect-Token {
    param(
        [string]$TokenType
    )
    
    Write-Log "Decrypting $TokenType token..."
    
    try {
        $tokenFile = Join-Path $TOKEN_DIR "$TokenType.token"
        if (-not (Test-Path $tokenFile)) {
            Write-Log "No stored token found" "WARN"
            return $null
        }
        
        $key = Get-Content $ENCRYPTION_KEY_FILE
        $encryptedToken = Get-Content $tokenFile
        $secureString = ConvertTo-SecureString $encryptedToken -Key ([System.Convert]::FromBase64String($key))
        $token = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secureString)
        )
        
        Write-Log "Token decrypted successfully" "INFO"
        return $token
    }
    catch {
        Write-Log "Failed to decrypt token: $_" "ERROR"
        return $null
    }
}

# Test GitHub token
function Test-GitHubToken {
    param(
        [string]$Token
    )
    
    Write-Log "Testing GitHub token..."
    
    try {
        $headers = @{
            "Authorization" = "token $Token"
            "Accept" = "application/vnd.github.v3+json"
        }
        
        $response = Invoke-WebRequest -Uri "https://api.github.com/user" -Headers $headers -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-Log "GitHub token is valid" "INFO"
            return $true
        }
    }
    catch {
        Write-Log "GitHub token is invalid: $_" "ERROR"
        return $false
    }
    return $false
}

# Main token management function
function Manage-Tokens {
    param(
        [switch]$ForceNew
    )
    
    Write-Log "Starting token management..."
    
    if (-not (Initialize-Directories)) {
        Write-Log "Failed to initialize directories" "ERROR"
        return $false
    }
    
    if (-not (Initialize-EncryptionKey)) {
        Write-Log "Failed to initialize encryption key" "ERROR"
        return $false
    }
    
    # Handle GitHub token
    $githubToken = if (-not $ForceNew) { Unprotect-Token -TokenType "github" }
    
    if ($ForceNew -or $null -eq $githubToken -or -not (Test-GitHubToken -Token $githubToken)) {
        Write-Log "Please enter your GitHub token:" "INFO"
        $githubToken = Read-Host -AsSecureString | ConvertFrom-SecureString
        
        if (-not (Test-GitHubToken -Token $githubToken)) {
            Write-Log "Invalid GitHub token. Please try again." "ERROR"
            return $false
        }
        
        if (-not (Protect-Token -Token $githubToken -TokenType "github")) {
            Write-Log "Failed to store GitHub token" "ERROR"
            return $false
        }
    }
    
    Write-Log "Token management completed successfully" "INFO"
    return $true
}

# Execute if run directly
if ($MyInvocation.InvocationName -eq $MyInvocation.MyCommand.Name) {
    Manage-Tokens
} 