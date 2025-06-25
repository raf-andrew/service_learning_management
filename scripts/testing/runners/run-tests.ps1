# Create temporary directories for test results
New-Item -ItemType Directory -Force -Path ".temp/unit-tests"
New-Item -ItemType Directory -Force -Path ".temp/code-sniffs"
New-Item -ItemType Directory -Force -Path ".temp/phpmd"

# Run unit tests
Write-Host "Running unit tests..."
docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit unit-tests

# Run PSR-12 code sniffs
Write-Host "`nRunning PSR-12 code sniffs..."
docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit psr12-sniffs

# Run PHPMD analysis
Write-Host "`nRunning PHPMD analysis..."
docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit phpmd-analysis

# Generate summary report
Write-Host "`nGenerating summary report..."
docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit generate-report

Write-Host "`nTest execution complete. Results are in .temp directory." 