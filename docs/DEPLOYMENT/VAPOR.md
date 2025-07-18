# Laravel Vapor Integration Guide

## 🎯 Overview

This document provides comprehensive guidance for deploying and managing the Service Learning Management System platform using Laravel Vapor. Vapor provides serverless deployment capabilities on AWS Lambda, enabling auto-scaling, global edge deployment, and pay-per-use pricing.

## 🏗️ Architecture

### Serverless Architecture
- **AWS Lambda**: Serverless compute for PHP applications
- **API Gateway**: RESTful API management
- **CloudFront**: Global content delivery network
- **S3**: Object storage for files and assets
- **RDS**: Managed database service
- **ElastiCache**: Managed Redis caching
- **Secrets Manager**: Secure credential management

### Vapor Components
```
vapor/
├── app/                    # Vapor application configuration
│   ├── vapor.yml          # Main Vapor configuration
│   └── vapor.json         # Vapor metadata
├── environments/           # Environment-specific configurations
│   ├── production/        # Production environment
│   ├── staging/           # Staging environment
│   └── testing/           # Testing environment
├── functions/             # Lambda function configurations
│   ├── web/              # Web application function
│   ├── api/              # API function
│   └── queue/            # Queue worker function
├── databases/             # Database configurations
│   ├── mysql/            # MySQL database config
│   └── redis/            # Redis cache config
├── caches/               # Cache configurations
│   └── redis/            # Redis cache setup
├── storage/              # Storage configurations
│   ├── s3/               # S3 bucket configuration
│   └── cdn/              # CloudFront CDN setup
└── domains/              # Domain configurations
    ├── api/              # API domain setup
    └── web/              # Web domain setup
```

## 🚀 Getting Started

### Prerequisites
1. **AWS Account**: Active AWS account with appropriate permissions
2. **Vapor CLI**: Install Vapor CLI globally
3. **Laravel Vapor Package**: Install Laravel Vapor package
4. **AWS Credentials**: Configure AWS credentials

### Installation
```bash
# Install Vapor CLI globally
composer global require laravel/vapor-cli

# Install Laravel Vapor package
composer require laravel/vapor

# Publish Vapor configuration
php artisan vapor:install
```

### Initial Setup
```bash
# Configure Vapor
vapor configure

# Create Vapor project
vapor project create service-learning-management

# Link local project to Vapor
vapor link service-learning-management
```

## 📁 Configuration Files

### Main Vapor Configuration (`vapor.yml`)
```yaml
id: service-learning-management
name: Service Learning Management System
environments:
    production:
        memory: 1024
        cli-memory: 512
        runtime: 'php-8.2'
        build:
            - 'composer install --no-dev'
            - 'php artisan event:cache'
            - 'npm ci && npm run build'
        deploy:
            - 'php artisan migrate --force'
            - 'php artisan config:cache'
            - 'php artisan route:cache'
            - 'php artisan view:cache'
        variables:
            APP_ENV: production
            APP_DEBUG: false
        databases:
            - service-learning-db
        caches:
            - service-learning-cache
        storage:
            - service-learning-storage
        domains:
            - service-learning.com
            - api.service-learning.com
        functions:
            web:
                memory: 1024
                timeout: 30
                runtime: 'php-8.2'
                handler: 'public/index.php'
            api:
                memory: 1024
                timeout: 30
                runtime: 'php-8.2'
                handler: 'public/index.php'
            queue:
                memory: 512
                timeout: 60
                runtime: 'php-8.2'
                handler: 'vapor/queue.php'
    staging:
        memory: 512
        cli-memory: 256
        runtime: 'php-8.2'
        build:
            - 'composer install --no-dev'
            - 'php artisan event:cache'
            - 'npm ci && npm run build'
        deploy:
            - 'php artisan migrate --force'
            - 'php artisan config:cache'
            - 'php artisan route:cache'
            - 'php artisan view:cache'
        variables:
            APP_ENV: staging
            APP_DEBUG: false
        databases:
            - service-learning-staging-db
        caches:
            - service-learning-staging-cache
        storage:
            - service-learning-staging-storage
        domains:
            - staging.service-learning.com
            - api-staging.service-learning.com
```

### Environment Configuration
```php
// config/vapor.php
<?php

return [
    'environments' => [
        'production' => [
            'database' => [
                'connection' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
            ],
            'cache' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
            'storage' => [
                'driver' => 's3',
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION'),
            ],
        ],
        'staging' => [
            'database' => [
                'connection' => 'mysql',
                'host' => env('DB_HOST'),
                'port' => env('DB_PORT', 3306),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
            ],
            'cache' => [
                'driver' => 'redis',
                'connection' => 'default',
            ],
            'storage' => [
                'driver' => 's3',
                'bucket' => env('AWS_BUCKET'),
                'region' => env('AWS_DEFAULT_REGION'),
            ],
        ],
    ],
];
```

## 🔧 Deployment Commands

### Core Deployment Commands
```bash
# Deploy to production
vapor deploy production

# Deploy to staging
vapor deploy staging

# Deploy specific environment
vapor deploy <environment>

# List deployments
vapor list

# View deployment logs
vapor logs

# Check deployment status
vapor status

# Rollback deployment
vapor rollback <environment>

# Open deployment in browser
vapor open <environment>
```

### Database Commands
```bash
# Run migrations
vapor run production php artisan migrate

# Run seeders
vapor run production php artisan db:seed

# Create database backup
vapor run production php artisan backup:run

# Restore database
vapor run production php artisan backup:restore
```

### Cache Commands
```bash
# Clear application cache
vapor run production php artisan cache:clear

# Clear config cache
vapor run production php artisan config:clear

# Clear route cache
vapor run production php artisan route:clear

# Clear view cache
vapor run production php artisan view:clear

# Cache application
vapor run production php artisan config:cache
vapor run production php artisan route:cache
vapor run production php artisan view:cache
```

### Queue Commands
```bash
# Process queue
vapor run production php artisan queue:work

# Monitor queue
vapor run production php artisan queue:monitor

# Clear failed jobs
vapor run production php artisan queue:flush

# Retry failed jobs
vapor run production php artisan queue:retry all
```

## 🔒 Security Configuration

### IAM Policies
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "lambda:InvokeFunction",
                "lambda:GetFunction",
                "lambda:UpdateFunctionCode",
                "lambda:UpdateFunctionConfiguration"
            ],
            "Resource": "arn:aws:lambda:*:*:function:service-learning-*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::service-learning-storage",
                "arn:aws:s3:::service-learning-storage/*"
            ]
        },
        {
            "Effect": "Allow",
            "Action": [
                "rds:DescribeDBInstances",
                "rds:DescribeDBClusters",
                "rds:CreateDBInstance",
                "rds:ModifyDBInstance"
            ],
            "Resource": "*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "elasticache:DescribeCacheClusters",
                "elasticache:CreateCacheCluster",
                "elasticache:ModifyCacheCluster"
            ],
            "Resource": "*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "secretsmanager:GetSecretValue",
                "secretsmanager:CreateSecret",
                "secretsmanager:UpdateSecret"
            ],
            "Resource": "arn:aws:secretsmanager:*:*:secret:service-learning-*"
        }
    ]
}
```

### Environment Variables
```bash
# Production Environment Variables
APP_ENV=production
APP_DEBUG=false
APP_URL=https://service-learning.com
APP_ASSET_URL=https://cdn.service-learning.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

# Cache Configuration
CACHE_DRIVER=redis
REDIS_HOST=${REDIS_HOST}
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

# Storage Configuration
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=service-learning-storage
AWS_USE_PATH_STYLE_ENDPOINT=false

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database-uuids

# Security Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Monitoring Configuration
LOG_CHANNEL=stack
LOG_LEVEL=info
SENTRY_LARAVEL_DSN=${SENTRY_LARAVEL_DSN}
SENTRY_TRACES_SAMPLE_RATE=0.1
```

## 📊 Monitoring & Observability

### CloudWatch Integration
```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'cloudwatch'],
    ],
    'cloudwatch' => [
        'driver' => 'cloudwatch',
        'name' => env('CLOUDWATCH_GROUP_NAME', 'service-learning'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'version' => 'latest',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ],
],
```

### Performance Monitoring
```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Monitor database queries
        DB::listen(function ($query) {
            Log::info('Database Query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        });

        // Monitor memory usage
        if (app()->environment('production')) {
            register_shutdown_function(function () {
                $memory = memory_get_peak_usage(true);
                Log::info('Memory Usage', ['peak_memory' => $memory]);
            });
        }
    }
}
```

### Health Checks
```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'environment' => app()->environment(),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
    ]);
});

Route::get('/health/database', function () {
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'healthy']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'unhealthy', 'error' => $e->getMessage()], 500);
    }
});

Route::get('/health/cache', function () {
    try {
        Cache::store()->has('health_check');
        return response()->json(['status' => 'healthy']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'unhealthy', 'error' => $e->getMessage()], 500);
    }
});
```

## 🔄 CI/CD Integration

### GitHub Actions Workflow
```yaml
# .github/workflows/vapor-deploy.yml
name: Deploy to Vapor

on:
  push:
    branches: [main, staging]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, redis, zip
        tools: composer:v2
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install PHP dependencies
      run: composer install --no-dev --optimize-autoloader
    
    - name: Install Node.js dependencies
      run: npm ci
    
    - name: Build assets
      run: npm run build
    
    - name: Setup Vapor
      uses: laravel/vapor-action@v1
      with:
        api-token: ${{ secrets.VAPOR_API_TOKEN }}
    
    - name: Deploy to Vapor
      run: vapor deploy ${{ github.ref == 'refs/heads/main' && 'production' || 'staging' }}
```

### Environment-Specific Deployments
```bash
# Production deployment
vapor deploy production --commit="$(git rev-parse HEAD)"

# Staging deployment
vapor deploy staging --commit="$(git rev-parse HEAD)"

# Testing deployment
vapor deploy testing --commit="$(git rev-parse HEAD)"
```

## 🚨 Troubleshooting

### Common Issues

#### Cold Start Performance
```php
// Optimize cold starts
// config/app.php
'providers' => [
    // Remove unused service providers
    // Keep only essential providers
],

// Use lazy loading for heavy services
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(HeavyService::class, function ($app) {
        return new HeavyService();
    });
}
```

#### Memory Issues
```yaml
# vapor.yml
environments:
    production:
        memory: 2048  # Increase memory if needed
        timeout: 60   # Increase timeout if needed
```

#### Database Connection Issues
```php
// config/database.php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
        'pool' => [
            'min' => 1,
            'max' => 10,
        ],
    ],
],
```

### Debugging Commands
```bash
# View function logs
vapor logs production --function=web

# View recent errors
vapor logs production --filter="ERROR"

# Test function locally
vapor test production

# Check function configuration
vapor function show production web

# Monitor function metrics
vapor metrics production
```

## 📈 Performance Optimization

### Cold Start Optimization
```php
// Optimize autoloader
composer install --optimize-autoloader --no-dev

// Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Database Optimization
```php
// Use connection pooling
'connections' => [
    'mysql' => [
        'pool' => [
            'min' => 1,
            'max' => 10,
        ],
    ],
],

// Optimize queries
DB::connection()->enableQueryLog();
// ... your queries
$queries = DB::getQueryLog();
```

### Cache Optimization
```php
// Use Redis for caching
'cache' => [
    'default' => 'redis',
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],
],

// Cache frequently accessed data
Cache::remember('key', 3600, function () {
    return ExpensiveOperation::execute();
});
```

## 🔧 Maintenance

### Regular Maintenance Tasks
```bash
# Daily tasks
vapor run production php artisan schedule:run

# Weekly tasks
vapor run production php artisan backup:run
vapor run production php artisan cache:clear

# Monthly tasks
vapor run production php artisan migrate
vapor run production composer update --no-dev
```

### Monitoring and Alerting
```php
// Set up monitoring
// config/monitoring.php
return [
    'health_checks' => [
        'database' => true,
        'cache' => true,
        'storage' => true,
        'queue' => true,
    ],
    'alerts' => [
        'email' => env('ALERT_EMAIL'),
        'slack' => env('SLACK_WEBHOOK_URL'),
    ],
];
```

## 📚 Additional Resources

### Documentation
- [Laravel Vapor Documentation](https://vapor.laravel.com/docs)
- [AWS Lambda Documentation](https://docs.aws.amazon.com/lambda/)
- [AWS CloudFormation Documentation](https://docs.aws.amazon.com/cloudformation/)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [AWS CloudFront Documentation](https://docs.aws.amazon.com/cloudfront/)
- [AWS RDS Documentation](https://docs.aws.amazon.com/rds/)
- [AWS ElastiCache Documentation](https://docs.aws.amazon.com/elasticache/)

### Best Practices
- [Serverless Best Practices](https://docs.aws.amazon.com/lambda/latest/dg/best-practices.html)
- [Laravel Performance Optimization](https://laravel.com/docs/optimization)
- [AWS Well-Architected Framework](https://aws.amazon.com/architecture/well-architected/)

### Tools and Utilities
- [Vapor CLI](https://vapor.laravel.com/docs/1.0/cli.html)
- [AWS CLI](https://docs.aws.amazon.com/cli/)
- [CloudWatch Logs](https://docs.aws.amazon.com/AmazonCloudWatch/latest/logs/)
- [AWS X-Ray](https://docs.aws.amazon.com/xray/)

This comprehensive Vapor integration guide provides all the necessary information for deploying and managing the Service Learning Management System platform on Laravel Vapor. 