#!/bin/bash

# Run code quality analyzer tests
php artisan test:run ANA-002-TEST tests/Unit/Analysis/CodeQualityAnalyzerTest.php

# Run test reporter tests
php artisan test:run ANA-001-TEST tests/Unit/Analysis/TestReporterTest.php

# Check if tests passed
if [ $? -eq 0 ]; then
    echo "All tests passed successfully!"
    exit 0
else
    echo "Some tests failed. Please check the reports for details."
    exit 1
fi 