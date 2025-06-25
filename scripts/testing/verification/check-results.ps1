# Check if results files exist
if (-not (Test-Path ".temp/summary.json")) {
    Write-Host "No test results found. Please run the tests first."
    exit 1
}

# Read and display test results
$summary = Get-Content ".temp/summary.json" | ConvertFrom-Json

Write-Host "`nTest Results Summary"
Write-Host "==================="
Write-Host "`nUnit Tests:"
Write-Host "  Total: $($summary.unit_tests.total)"
Write-Host "  Passed: $($summary.unit_tests.passed)"
Write-Host "  Failed: $($summary.unit_tests.failed)"

if ($summary.unit_tests.failures.Count -gt 0) {
    Write-Host "`nTest Failures:"
    foreach ($failure in $summary.unit_tests.failures) {
        Write-Host "  - $($failure.name): $($failure.message)"
    }
}

Write-Host "`nCode Quality:"
Write-Host "  PSR-12 Issues: $($summary.code_quality.psr12.failed)"
Write-Host "  PHPMD Violations: $($summary.code_quality.phpmd.violations.Count)"

if ($summary.code_quality.phpmd.violations.Count -gt 0) {
    Write-Host "`nPHPMD Violations:"
    foreach ($violation in $summary.code_quality.phpmd.violations) {
        Write-Host "  - $($violation.file):$($violation.line) - $($violation.rule)"
        Write-Host "    $($violation.message)"
    }
}

# Update test plan checkboxes based on results
$testPlan = Get-Content ".reports/test_plan.md" -Raw

# Update unit test checkboxes
if ($summary.unit_tests.failed -eq 0) {
    $testPlan = $testPlan -replace "- \[ \] test_check_health_successful_check", "- [x] test_check_health_successful_check"
    $testPlan = $testPlan -replace "- \[ \] test_check_health_invalid_service", "- [x] test_check_health_invalid_service"
    $testPlan = $testPlan -replace "- \[ \] test_get_metrics", "- [x] test_get_metrics"
    $testPlan = $testPlan -replace "- \[ \] test_deploy_successful_deployment", "- [x] test_deploy_successful_deployment"
    $testPlan = $testPlan -replace "- \[ \] test_deploy_invalid_environment", "- [x] test_deploy_invalid_environment"
    $testPlan = $testPlan -replace "- \[ \] test_rollback_successful_rollback", "- [x] test_rollback_successful_rollback"
    $testPlan = $testPlan -replace "- \[ \] test_rollback_invalid_environment", "- [x] test_rollback_invalid_environment"
    $testPlan = $testPlan -replace "- \[ \] test_get_metrics", "- [x] test_get_metrics"
}

# Update code sniff checkboxes
if ($summary.code_quality.psr12.failed -eq 0) {
    $testPlan = $testPlan -replace "- \[ \] ServiceHealthAgent\.php", "- [x] ServiceHealthAgent.php"
    $testPlan = $testPlan -replace "- \[ \] ServiceHealthAgentTest\.php", "- [x] ServiceHealthAgentTest.php"
    $testPlan = $testPlan -replace "- \[ \] DeploymentAutomationAgent\.php", "- [x] DeploymentAutomationAgent.php"
    $testPlan = $testPlan -replace "- \[ \] DeploymentAutomationAgentTest\.php", "- [x] DeploymentAutomationAgentTest.php"
}

Set-Content ".reports/test_plan.md" $testPlan

Write-Host "`nTest plan has been updated." 