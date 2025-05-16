# Setup environment files
$ErrorActionPreference = "Stop"

# Create .env.example if it doesn't exist
$envExample = @"
# Environment Configuration
# This file serves as a template for both local and codespaces environments
# Copy this file to .env and update the values as needed

# Application Environment
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# GitHub Configuration
GITHUB_TOKEN=
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=

# Database Configuration
# Local Development
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=service_learning
DB_USERNAME=root
DB_PASSWORD=

# Codespaces Development
CODESPACES_DB_CONNECTION=mysql
CODESPACES_DB_HOST=mysql
CODESPACES_DB_PORT=3306
CODESPACES_DB_DATABASE=service_learning
CODESPACES_DB_USERNAME=root
CODESPACES_DB_PASSWORD=

# Redis Configuration
# Local Development
REDIS_HOST=localhost
REDIS_PASSWORD=null
REDIS_PORT=6379

# Codespaces Development
CODESPACES_REDIS_HOST=redis
CODESPACES_REDIS_PASSWORD=null
CODESPACES_REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="\${APP_NAME}"

# Service Configuration
# Set to 'local' for local development or 'codespaces' for codespaces development
SERVICE_ENVIRONMENT=local

# Test Configuration
TEST_DB_CONNECTION=mysql
TEST_DB_HOST=localhost
TEST_DB_PORT=3306
TEST_DB_DATABASE=service_learning_test
TEST_DB_USERNAME=root
TEST_DB_PASSWORD=
"@

# Write .env.example
Set-Content -Path ".env.example" -Value $envExample

# Create .env from .env.example if it doesn't exist
if (-not (Test-Path ".env")) {
    Copy-Item -Path ".env.example" -Destination ".env"
    Write-Host "Created .env file from .env.example"
} else {
    Write-Host ".env file already exists"
}

# Ensure the GitHub token is set in .env
$envContent = Get-Content ".env"
$hasGitHubToken = $envContent | Where-Object { $_ -match "^GITHUB_TOKEN=" }
if (-not $hasGitHubToken) {
    Add-Content -Path ".env" -Value "GITHUB_TOKEN="
    Write-Host "Added GITHUB_TOKEN to .env"
}

Write-Host "Environment setup complete" 