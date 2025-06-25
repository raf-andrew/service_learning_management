#!/bin/bash

# Create temporary directories for test results
mkdir -p .temp/unit-tests
mkdir -p .temp/code-sniffs
mkdir -p .temp/phpmd

# Run unit tests
echo "Running unit tests..."
docker run --rm -v $(pwd):/var/www/html php:8.2-fpm bash -c "
    cd /var/www/html && \
    composer install && \
    ./vendor/bin/phpunit --log-junit .temp/unit-tests/junit.xml --testdox-html .temp/unit-tests/testdox.html"

# Run PSR-12 code sniffs
echo "Running PSR-12 code sniffs..."
docker run --rm -v $(pwd):/var/www/html php:8.2-fpm bash -c "
    cd /var/www/html && \
    composer install && \
    ./vendor/bin/phpcs --standard=PSR12 --report=junit --report-file=.temp/code-sniffs/psr12.xml \
    src/MCP/Core/ServiceHealthAgent.php \
    tests/Unit/MCP/Core/ServiceHealthAgentTest.php \
    src/MCP/Core/DeploymentAutomationAgent.php \
    tests/Unit/MCP/Core/DeploymentAutomationAgentTest.php"

# Run PHPMD
echo "Running PHPMD..."
docker run --rm -v $(pwd):/var/www/html php:8.2-fpm bash -c "
    cd /var/www/html && \
    composer install && \
    ./vendor/bin/phpmd src/MCP/Core/ServiceHealthAgent.php,tests/Unit/MCP/Core/ServiceHealthAgentTest.php,src/MCP/Core/DeploymentAutomationAgent.php,tests/Unit/MCP/Core/DeploymentAutomationAgentTest.php xml cleancode,codesize,controversial,design,naming,unusedcode > .temp/phpmd/phpmd.xml"

# Generate summary report
echo "Generating summary report..."
docker run --rm -v $(pwd):/var/www/html php:8.2-fpm bash -c "
    cd /var/www/html && \
    composer install && \
    php scripts/generate-report.php"

echo "Test execution complete. Results are in .temp directory."

cd /var/www/html
composer install
find . -type f -name '*.php' -exec dos2unix {} \;
php scripts/run-systematic-tests.php 