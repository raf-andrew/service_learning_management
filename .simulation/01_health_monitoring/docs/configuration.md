# Health Monitoring Configuration Guide

## Overview
This guide details all configurable aspects of the health monitoring system, including health checks, notifications, metrics collection, and system behavior.

## Configuration Files

### 1. Health Check Configuration (`config/health.php`)
```php
return [
    // Default timeout for health checks in seconds
    'default_timeout' => 30,

    // Number of retry attempts for failed checks
    'default_retry_attempts' => 3,

    // Delay between retries in seconds
    'default_retry_delay' => 5,

    // Interval between health checks in seconds
    'check_interval' => 60,

    // Number of consecutive failures before alert
    'alert_threshold' => 3,

    // Health check types and their configurations
    'types' => [
        'http' => [
            'timeout' => 30,
            'retry_attempts' => 3,
            'valid_status_codes' => [200, 201, 202, 204],
        ],
        'tcp' => [
            'timeout' => 10,
            'retry_attempts' => 2,
        ],
        'command' => [
            'timeout' => 60,
            'retry_attempts' => 1,
        ],
    ],

    // Service groups for batch operations
    'groups' => [
        'critical' => [
            'check_interval' => 30,
            'alert_threshold' => 2,
        ],
        'non_critical' => [
            'check_interval' => 300,
            'alert_threshold' => 5,
        ],
    ],
];
```

### 2. Notification Configuration (`config/notifications.php`)
```php
return [
    // Notification channels
    'channels' => [
        'mail' => [
            'enabled' => true,
            'recipients' => ['admin@example.com'],
            'from' => 'health-monitor@example.com',
            'subject_prefix' => '[Health Alert]',
        ],
        'slack' => [
            'enabled' => true,
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => '#health-alerts',
            'username' => 'Health Monitor',
        ],
        'webhook' => [
            'enabled' => false,
            'url' => env('WEBHOOK_URL'),
            'secret' => env('WEBHOOK_SECRET'),
        ],
    ],

    // Alert levels and their configurations
    'levels' => [
        'critical' => [
            'channels' => ['mail', 'slack', 'webhook'],
            'throttle' => 300, // 5 minutes
        ],
        'warning' => [
            'channels' => ['slack'],
            'throttle' => 900, // 15 minutes
        ],
        'info' => [
            'channels' => ['slack'],
            'throttle' => 3600, // 1 hour
        ],
    ],
];
```

### 3. Metrics Configuration (`config/metrics.php`)
```php
return [
    // Metrics collection settings
    'collection' => [
        'interval' => 60, // seconds
        'retention' => 30, // days
    ],

    // Metrics to collect
    'metrics' => [
        'response_time' => [
            'enabled' => true,
            'percentiles' => [50, 95, 99],
        ],
        'error_rate' => [
            'enabled' => true,
            'window' => 300, // 5 minutes
        ],
        'uptime' => [
            'enabled' => true,
            'calculation_window' => 86400, // 24 hours
        ],
    ],

    // Storage configuration
    'storage' => [
        'driver' => 'redis',
        'prefix' => 'health_metrics:',
        'ttl' => 2592000, // 30 days
    ],
];
```

### 4. API Configuration (`config/api.php`)
```php
return [
    // Rate limiting
    'rate_limit' => [
        'requests_per_minute' => 100,
        'throttle_by' => 'api_key',
        'storage' => 'redis',
    ],

    // API key settings
    'api_key' => [
        'length' => 32,
        'prefix' => 'hk_',
        'expiry' => 30, // days
    ],

    // Response formatting
    'response' => [
        'include_timestamp' => true,
        'include_request_id' => true,
        'pretty_print' => false,
    ],
];
```

### 5. Queue Configuration (`config/queue.php`)
```php
return [
    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'health-checks',
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    'failed' => [
        'driver' => 'database',
        'database' => 'mysql',
        'table' => 'failed_jobs',
    ],
];
```

## Environment Variables
Configure these in your `.env` file:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=health_monitoring
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

# Notifications
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

SLACK_WEBHOOK_URL=your-webhook-url
WEBHOOK_URL=your-webhook-url
WEBHOOK_SECRET=your-webhook-secret

# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
```

## Customizing Health Checks

### HTTP Health Check
```php
// Example HTTP health check configuration
$config = [
    'type' => 'http',
    'target' => 'https://api.example.com/health',
    'method' => 'GET',
    'headers' => [
        'Authorization' => 'Bearer token',
    ],
    'timeout' => 30,
    'retry_attempts' => 3,
    'valid_status_codes' => [200, 201, 202],
    'body' => [
        'check' => 'database',
    ],
];
```

### TCP Health Check
```php
// Example TCP health check configuration
$config = [
    'type' => 'tcp',
    'target' => '127.0.0.1:3306',
    'timeout' => 10,
    'retry_attempts' => 2,
];
```

### Command Health Check
```php
// Example command health check configuration
$config = [
    'type' => 'command',
    'command' => 'php artisan queue:monitor',
    'timeout' => 60,
    'retry_attempts' => 1,
    'working_directory' => '/var/www/html',
];
```

## Best Practices

1. **Health Check Configuration**
   - Set appropriate timeouts based on service response times
   - Configure retry attempts based on service reliability
   - Group related services for efficient monitoring

2. **Notification Settings**
   - Use different channels for different alert levels
   - Configure throttling to prevent alert fatigue
   - Test notification channels regularly

3. **Metrics Collection**
   - Adjust collection intervals based on needs
   - Configure appropriate retention periods
   - Monitor storage usage

4. **API Security**
   - Rotate API keys regularly
   - Use appropriate rate limits
   - Monitor API usage patterns

5. **Queue Management**
   - Configure appropriate retry times
   - Monitor queue lengths
   - Set up queue monitoring

## Troubleshooting Configuration

1. **Configuration Cache**
```bash
# Clear configuration cache
php artisan config:clear

# Cache configuration for production
php artisan config:cache
```

2. **Environment Variables**
```bash
# Check environment variables
php artisan env

# Validate configuration
php artisan config:validate
```

3. **Queue Configuration**
```bash
# Check queue configuration
php artisan queue:monitor

# List failed jobs
php artisan queue:failed
```

4. **Notification Testing**
```bash
# Test mail configuration
php artisan notification:test mail

# Test Slack integration
php artisan notification:test slack
``` 