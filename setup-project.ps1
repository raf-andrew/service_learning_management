# Set environment variable for Docker Compose
$env:PWD = (Get-Location).Path

Write-Host "Setting up Laravel project..." -ForegroundColor Yellow

# Create Laravel project
docker-compose -f docker-compose.test.yml run --rm test sh -c "
    composer create-project laravel/laravel . &&
    composer require --dev phpunit/phpunit phpmd/phpmd squizlabs/php_codesniffer &&
    mkdir -p src/MCP/Core &&
    chown -R www-data:www-data /var/www/html"

Write-Host "Project setup completed" -ForegroundColor Green 