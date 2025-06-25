#!/bin/bash

# Create necessary directories
mkdir -p .reports/tests
mkdir -p .reports/coverage
mkdir -p .reports/performance
mkdir -p .reports/security

# Build the test container
docker-compose -f docker-compose.test.yml build test

# Run the tests
docker-compose -f docker-compose.test.yml run --rm test php scripts/run-systematic-tests.php

# Run code style checks
docker-compose -f docker-compose.test.yml run --rm psr12-sniffs

# Run code complexity analysis
docker-compose -f docker-compose.test.yml run --rm phpmd-analysis

# Generate final report
docker-compose -f docker-compose.test.yml run --rm generate-report

# Check if all tests passed
if [ -f .reports/tests/summary.json ]; then
    FAILED_TESTS=$(jq '.total.failed' .reports/tests/summary.json)
    ERRORS=$(jq '.total.errors' .reports/tests/summary.json)
    
    if [ "$FAILED_TESTS" -eq 0 ] && [ "$ERRORS" -eq 0 ]; then
        echo "All tests passed successfully!"
        exit 0
    else
        echo "Some tests failed. Please check the reports for details."
        exit 1
    fi
else
    echo "Test summary report not found. Tests may have failed to run."
    exit 1
fi 