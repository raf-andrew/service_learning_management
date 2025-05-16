# Define PHP path
$phpPath = "C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"

# Verify PHP exists
if (-not (Test-Path $phpPath)) {
    Write-Host "Error: PHP not found at $phpPath" -ForegroundColor Red
    exit 1
}

# Create test reports directory if it doesn't exist
$reportsDir = "storage\app\test-reports"
if (-not (Test-Path $reportsDir)) {
    New-Item -ItemType Directory -Path $reportsDir -Force | Out-Null
}

# Run code quality analyzer tests
Write-Host "Running code quality analyzer tests..." -ForegroundColor Yellow
& $phpPath artisan test:run ANA-002-TEST tests/Unit/Analysis/CodeQualityAnalyzerTest.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "Code quality analyzer tests failed!" -ForegroundColor Red
    exit 1
}

# Run test reporter tests
Write-Host "Running test reporter tests..." -ForegroundColor Yellow
& $phpPath artisan test:run ANA-001-TEST tests/Unit/Analysis/TestReporterTest.php
if ($LASTEXITCODE -ne 0) {
    Write-Host "Test reporter tests failed!" -ForegroundColor Red
    exit 1
}

# Verify reports were generated
$reports = Get-ChildItem $reportsDir -Filter "ANA-002-TEST_*.json"
if ($reports.Count -eq 0) {
    Write-Host "Error: No test reports were generated!" -ForegroundColor Red
    exit 1
}

# Display latest report
$latestReport = $reports | Sort-Object LastWriteTime -Descending | Select-Object -First 1
$reportContent = Get-Content $latestReport.FullName | ConvertFrom-Json

Write-Host "`nTest Report Summary:" -ForegroundColor Green
Write-Host "Checklist Item: $($reportContent.checklist_item)"
Write-Host "Timestamp: $($reportContent.timestamp)"
Write-Host "Total Tests: $($reportContent.summary.total_tests)"
Write-Host "Passed Tests: $($reportContent.summary.passed_tests)"
Write-Host "Failed Tests: $($reportContent.summary.failed_tests)"
Write-Host "Coverage: $($reportContent.summary.coverage_percentage)%"

if ($reportContent.summary.failed_tests -gt 0) {
    Write-Host "`nFailed Tests:" -ForegroundColor Red
    $reportContent.results | Where-Object { -not $_.passed } | ForEach-Object {
        Write-Host "- $($_.test_name): $($_.details.message)"
    }
    exit 1
}

Write-Host "`nAll tests passed successfully!" -ForegroundColor Green
Write-Host "Report saved to: $($latestReport.FullName)"
exit 0 