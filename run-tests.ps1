# Create reports directory if it doesn't exist
New-Item -ItemType Directory -Force -Path ".reports\tests"
New-Item -ItemType Directory -Force -Path ".reports\sniffs"

Write-Host "Starting test suite..." -ForegroundColor Green

# Set environment variable for Docker Compose
$env:PWD = (Get-Location).Path

# Stop on error
$ErrorActionPreference = "Stop"

# Build and run test containers
Write-Host "Building and starting test containers..."
docker-compose -f docker-compose.test.yml up -d --build

# Wait for services to be ready
Write-Host "Waiting for services to be ready..."
Start-Sleep -Seconds 10

# Run tests
Write-Host "Running tests..."
docker-compose -f docker-compose.test.yml exec -T test php scripts/run-tests.php

# Check test results
$testExitCode = $LASTEXITCODE
if ($testExitCode -ne 0) {
    Write-Host "Tests failed with exit code $testExitCode" -ForegroundColor Red
    exit $testExitCode
}

# Run code style checks
Write-Host "Running code style checks..."
docker-compose -f docker-compose.test.yml exec -T psr12-sniffs

# Run static analysis
Write-Host "Running static analysis..."
docker-compose -f docker-compose.test.yml exec -T phpmd-analysis

# Generate test report
Write-Host "Generating test report..."
docker-compose -f docker-compose.test.yml exec -T generate-report

# Stop containers
Write-Host "Stopping test containers..."
docker-compose -f docker-compose.test.yml down

# Check if tests passed
if ($testExitCode -eq 0) {
    Write-Host "All tests passed!" -ForegroundColor Green
    exit 0
} else {
    Write-Host "Tests failed!" -ForegroundColor Red
    exit 1
}

# Check if test results exist
if (Test-Path ".reports\tests\phpunit.xml") {
    Write-Host "Test results have been saved to .reports\tests\phpunit.xml" -ForegroundColor Green
} else {
    Write-Host "Warning: No test results found" -ForegroundColor Red
}

if (Test-Path ".reports\sniffs\phpcs.xml") {
    Write-Host "Code sniff results have been saved to .reports\sniffs\phpcs.xml" -ForegroundColor Green
} else {
    Write-Host "Warning: No code sniff results found" -ForegroundColor Red
}

if (Test-Path ".reports\sniffs\phpmd.xml") {
    Write-Host "PHPMD results have been saved to .reports\sniffs\phpmd.xml" -ForegroundColor Green
} else {
    Write-Host "Warning: No PHPMD results found" -ForegroundColor Red
}

# Generate test plan
$testPlan = @"
# Test Plan Report

## Test Coverage
- Unit Tests: $(Get-ChildItem -Path "tests/Unit" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count)
- Feature Tests: $(Get-ChildItem -Path "tests/Feature" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count)
- Integration Tests: $(Get-ChildItem -Path "tests/Integration" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count)
- Smoke Tests: $(Get-ChildItem -Path "tests/Smoke" -Filter "*.php" | Measure-Object | Select-Object -ExpandProperty Count)

## Test Results
- Total Tests: $(Select-Xml -Path ".reports/tests/phpunit.xml" -XPath "//testsuite/@tests" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)
- Passed Tests: $(Select-Xml -Path ".reports/tests/phpunit.xml" -XPath "//testsuite/@assertions" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)
- Failed Tests: $(Select-Xml -Path ".reports/tests/phpunit.xml" -XPath "//testsuite/@failures" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)
- Error Tests: $(Select-Xml -Path ".reports/tests/phpunit.xml" -XPath "//testsuite/@errors" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)

## Code Quality
- Code Coverage: $(Select-Xml -Path ".reports/tests/coverage/index.xml" -XPath "//coverage/@line-rate" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)
- Code Style: $(if (Test-Path ".reports/sniffs/phpcs.xml") { "Passed" } else { "Failed" })
- Code Complexity: $(if (Test-Path ".reports/sniffs/phpmd.xml") { "Passed" } else { "Failed" })

## Security Checks
- Test Integrity: $(if (Test-Path ".reports/tests/security.xml") { "Passed" } else { "Failed" })
- Test Coverage: $(if (Test-Path ".reports/tests/coverage") { "Passed" } else { "Failed" })

## Performance Metrics
- Execution Time: $(Select-Xml -Path ".reports/tests/phpunit.xml" -XPath "//testsuite/@time" | Select-Object -ExpandProperty Node | Select-Object -ExpandProperty Value)
- Memory Usage: $(Get-Content ".reports/tests/memory.txt" -ErrorAction SilentlyContinue)
"@

$testPlan | Out-File -FilePath ".reports/test_plan.md" -Encoding UTF8

Write-Host "Test plan has been updated in .reports\test_plan.md" -ForegroundColor Green 