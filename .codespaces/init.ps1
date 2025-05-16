# Set error action preference
$ErrorActionPreference = "Stop"

# Define paths
$servicesDir = ".codespaces/services"
$logsDir = ".codespaces/log"
$testDir = ".codespaces/testing"
$completeDir = "$testDir/.complete"
$failuresDir = "$testDir/.failures"

# Create necessary directories
$directories = @(
    $servicesDir,
    $logsDir,
    $testDir,
    $completeDir,
    $failuresDir
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "Created directory: $dir"
    }
}

# Initialize services
$services = @("database", "redis", "mail")
foreach ($service in $services) {
    $configPath = "$servicesDir/$service.json"
    if (Test-Path $configPath) {
        Write-Host "Creating service: $service"
        php artisan codespaces:services create $service --config=$configPath
    }
}

# Activate services
foreach ($service in $services) {
    Write-Host "Activating service: $service"
    php artisan codespaces:services activate $service
}

# Check service health
Write-Host "Checking service health..."
php artisan codespaces:services health

# Create initial checklist
$checklist = @{
    "total_items" = 0
    "completed" = 0
    "pending" = 0
    "items" = @()
}

$checklistPath = "$testDir/checklist-tracking.json"
$checklist | ConvertTo-Json -Depth 10 | Set-Content $checklistPath

Write-Host "Codespaces environment initialized successfully!" 