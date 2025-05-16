# Health Monitoring Setup Guide

## Prerequisites
- PHP 8.1 or higher
- Composer
- MySQL 8.0 or higher
- Redis (optional, for caching and queue)
- Node.js 16+ (for frontend assets)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd service_learning_management
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install frontend dependencies:
```bash
npm install
```

4. Copy environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure environment variables in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=health_monitoring
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

7. Run database migrations:
```bash
php artisan migrate
```

8. Create initial API key:
```bash
php artisan api-key:create "Initial API Key"
```

9. Build frontend assets:
```bash
npm run build
```

## Configuration

### Health Check Settings
Configure health check parameters in `config/health.php`:

```php
return [
    'default_timeout' => 30,
    'default_retry_attempts' => 3,
    'default_retry_delay' => 5,
    'check_interval' => 60,
    'alert_threshold' => 3,
];
```

### Queue Configuration
Configure queue workers in `config/queue.php`:

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'health-checks',
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### Rate Limiting
Configure rate limits in `config/api.php`:

```php
return [
    'rate_limit' => [
        'requests_per_minute' => 100,
        'throttle_by' => 'api_key',
    ],
];
```

## Running the System

1. Start the queue worker:
```bash
php artisan queue:work --queue=health-checks
```

2. Start the scheduler (for periodic health checks):
```bash
php artisan schedule:work
```

3. Start the development server:
```bash
php artisan serve
```

## Monitoring

### Logs
- Application logs: `storage/logs/laravel.log`
- Health check logs: `storage/logs/health-checks.log`
- Queue logs: `storage/logs/queue.log`

### Metrics
Access metrics dashboard at `/metrics` (requires authentication)

### Alerts
Configure alert notifications in `config/notifications.php`:

```php
return [
    'channels' => [
        'mail' => [
            'enabled' => true,
            'recipients' => ['admin@example.com'],
        ],
        'slack' => [
            'enabled' => true,
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
        ],
    ],
];
```

## Troubleshooting

### Common Issues

1. Queue Worker Not Processing Jobs
```bash
# Check queue status
php artisan queue:monitor

# Restart queue worker
php artisan queue:restart
```

2. Health Checks Failing
```bash
# Check health check logs
tail -f storage/logs/health-checks.log

# Test health check manually
php artisan health:check test-service
```

3. API Authentication Issues
```bash
# Verify API key
php artisan api-key:verify your-api-key

# List active API keys
php artisan api-key:list
```

### Debug Mode
Enable debug mode in `.env`:
```env
APP_DEBUG=true
```

## Security Considerations

1. API Key Management
- Rotate API keys regularly
- Use different API keys for different environments
- Monitor API key usage

2. Rate Limiting
- Monitor rate limit violations
- Adjust limits based on usage patterns
- Implement IP-based rate limiting if needed

3. Data Protection
- Encrypt sensitive data
- Implement proper access controls
- Regular security audits

## Support

For additional support:
- Documentation: `/docs`
- API Reference: `/docs/api`
- Issue Tracker: [GitHub Issues]
- Support Email: support@example.com 