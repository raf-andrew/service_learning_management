# Setup Codespaces Environment
$ErrorActionPreference = "Stop"

# Create necessary directories
$directories = @(
    ".codespaces/log",
    ".codespaces/testing/.test/results",
    ".codespaces/testing/.test/.complete",
    ".codespaces/testing/.test/.failures"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created directory: $dir"
    }
}

# Create .env file if it doesn't exist
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item ".env.example" ".env"
        Write-Host "Created .env from .env.example"
    } else {
        Write-Host "Warning: .env.example not found"
    }
}

# Update .env with Codespaces settings
$envContent = Get-Content ".env" -Raw
$codespacesSettings = @"
# Codespaces Configuration
CODESPACES_ENABLED=true
CODESPACES_DB_HOST=localhost
CODESPACES_DB_PORT=3306
CODESPACES_DB_DATABASE=service_learning
CODESPACES_DB_USERNAME=root
CODESPACES_DB_PASSWORD=root
CODESPACES_REDIS_HOST=localhost
CODESPACES_REDIS_PORT=6379
CODESPACES_REDIS_PASSWORD=null
CODESPACES_MAIL_HOST=localhost
CODESPACES_MAIL_PORT=1025
CODESPACES_MAIL_USERNAME=null
CODESPACES_MAIL_PASSWORD=null
CODESPACES_MAIL_ENCRYPTION=null
CODESPACES_LOG_LEVEL=debug
"@

if (-not ($envContent -match "CODESPACES_ENABLED")) {
    Add-Content ".env" "`n$codespacesSettings"
    Write-Host "Added Codespaces settings to .env"
}

# Create initial checklist file if it doesn't exist
$checklistFile = ".codespaces/testing/.test/checklist-tracking.json"
if (-not (Test-Path $checklistFile)) {
    $initialChecklist = @{
        items = @()
        last_updated = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    }
    $initialChecklist | ConvertTo-Json | Set-Content $checklistFile
    Write-Host "Created initial checklist file"
}

# Verify GitHub CLI installation
if (-not (Get-Command gh -ErrorAction SilentlyContinue)) {
    Write-Host "Installing GitHub CLI..."
    winget install GitHub.cli
}

# Authenticate with GitHub
Write-Host "Authenticating with GitHub..."
gh auth login

Write-Host "Codespaces environment setup complete" 