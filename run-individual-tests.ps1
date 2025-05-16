# Create necessary directories
New-Item -ItemType Directory -Force -Path ".reports\tests" | Out-Null
New-Item -ItemType Directory -Force -Path ".reports\sniffs" | Out-Null
New-Item -ItemType Directory -Force -Path ".reports\temp" | Out-Null

# Set environment variable for Docker Compose
$env:PWD = (Get-Location).Path

function Run-DockerTest {
    param (
        [string]$TestName,
        [string]$Command
    )
    Write-Host "Running $TestName..." -ForegroundColor Yellow
    
    docker-compose -f docker-compose.test.yml run --rm test sh -c "$Command"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "$TestName completed successfully" -ForegroundColor Green
    } else {
        Write-Host "$TestName failed" -ForegroundColor Red
    }
    Write-Host "----------------------------------------" -ForegroundColor Blue
}

# Run Unit Tests for Service Health Agent
Run-DockerTest "Service Health Agent Tests" @"
    composer install &&
    ./vendor/bin/phpunit --filter=ServiceHealthAgentTest --log-junit=.reports/temp/service_health_test.xml
"@

# Run Unit Tests for Deployment Automation Agent
Run-DockerTest "Deployment Automation Agent Tests" @"
    composer install &&
    ./vendor/bin/phpunit --filter=DeploymentAutomationAgentTest --log-junit=.reports/temp/deployment_test.xml
"@

# Run PSR-12 Sniffs
Run-DockerTest "PSR-12 Compliance Check" @"
    composer install &&
    ./vendor/bin/phpcs --standard=PSR12 --report=junit --report-file=.reports/temp/psr12_sniff.xml src/MCP/Core/ServiceHealthAgent.php src/MCP/Core/DeploymentAutomationAgent.php
"@

# Run PHPMD Analysis
Run-DockerTest "PHPMD Analysis" @"
    composer install &&
    ./vendor/bin/phpmd src/MCP/Core xml cleancode,codesize,controversial,design,naming,unusedcode > .reports/temp/phpmd.xml
"@

# Combine all results
Write-Host "Collecting all test results..." -ForegroundColor Yellow
Get-ChildItem ".reports\temp\*.xml" | ForEach-Object {
    $destinationPath = ".reports\tests\$($_.Name)"
    Copy-Item $_.FullName -Destination $destinationPath -Force
    Write-Host "Copied $($_.Name) to reports directory" -ForegroundColor Green
}

# Update test plan using Docker
Write-Host "Updating test plan..." -ForegroundColor Yellow
if (Test-Path ".reports\temp\*.xml") {
    docker-compose -f docker-compose.test.yml run --rm test php scripts/update-test-plan.php
    Write-Host "Test plan updated" -ForegroundColor Green
} else {
    Write-Host "No test results found to update test plan" -ForegroundColor Red
} 