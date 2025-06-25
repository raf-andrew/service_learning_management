# Installation Guide

## Overview

This guide provides detailed instructions for installing and setting up the Service Learning Management System in various environments. The system supports multiple deployment options and can be configured for development, testing, staging, and production environments.

## Prerequisites

### System Requirements

#### Minimum Requirements
- **PHP**: 8.1 or higher
- **Composer**: 2.0 or higher
- **Node.js**: 18.0 or higher
- **npm**: 8.0 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Redis**: 6.0+ (for caching)
- **Memory**: 2GB RAM minimum
- **Storage**: 10GB available space

#### Recommended Requirements
- **PHP**: 8.2 or higher
- **Composer**: 2.4 or higher
- **Node.js**: 20.0 or higher
- **npm**: 10.0 or higher
- **Database**: MySQL 8.0+ or PostgreSQL 15+
- **Redis**: 7.0+ (for caching)
- **Memory**: 4GB RAM or higher
- **Storage**: 20GB available space
- **CPU**: Multi-core processor

### Required PHP Extensions

```bash
# Required extensions
php-bcmath
php-curl
php-dom
php-fileinfo
php-gd
php-json
php-mbstring
php-mysql
php-opcache
php-pdo
php-sqlite3
php-xml
php-zip
php-redis
php-intl
php-soap
php-xmlrpc
php-ldap
php-imap
php-ftp
php-sockets
php-zlib
php-bz2
php-calendar
php-exif
php-gettext
php-iconv
php-mysqli
php-odbc
php-pdo_mysql
php-pdo_pgsql
php-pdo_sqlite
php-pgsql
php-shmop
php-snmp
php-sysvmsg
php-sysvsem
php-sysvshm
php-wddx
php-xsl
```

### Development Tools

#### Required Tools
- **Git**: Version control system
- **Composer**: PHP dependency manager
- **npm**: Node.js package manager
- **Docker**: Containerization (optional but recommended)

#### Recommended Tools
- **Laragon**: All-in-one development environment (Windows)
- **XAMPP**: Apache, MySQL, PHP stack (cross-platform)
- **MAMP**: macOS, Apache, MySQL, PHP stack
- **WAMP**: Windows, Apache, MySQL, PHP stack
- **VS Code**: Code editor with PHP and Vue.js extensions
- **PHPStorm**: Advanced PHP IDE
- **TablePlus**: Database management tool

## Installation Methods

### Method 1: Standard Installation

#### Step 1: Clone the Repository

```bash
# Clone the repository
git clone <repository-url>
cd service_learning_management

# Or if using SSH
git clone git@github.com:your-org/service-learning-management.git
cd service_learning_management
```

#### Step 2: Install PHP Dependencies

```bash
# Install Composer dependencies
composer install

# For production, use --no-dev flag
composer install --no-dev --optimize-autoloader
```

#### Step 3: Install Node.js Dependencies

```bash
# Install npm dependencies
npm install

# For production, use --production flag
npm install --production
```

#### Step 4: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database connection in .env file
# Edit .env file with your database credentials
```

#### Step 5: Database Setup

```bash
# Run database migrations
php artisan migrate

# Seed the database with initial data
php artisan db:seed

# For production, you might want to skip seeding
# php artisan migrate --force
```

#### Step 6: Storage and Permissions

```bash
# Create storage links
php artisan storage:link

# Set proper permissions (Linux/macOS)
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# For Windows, ensure proper write permissions
```

#### Step 7: Build Frontend Assets

```bash
# Build frontend assets for development
npm run dev

# Build frontend assets for production
npm run build
```

#### Step 8: Start Development Servers

```bash
# Terminal 1: Start Laravel development server
php artisan serve

# Terminal 2: Start frontend development server
npm run dev
```

### Method 2: Docker Installation

#### Prerequisites
- Docker Desktop installed
- Docker Compose available

#### Step 1: Clone and Setup

```bash
# Clone the repository
git clone <repository-url>
cd service_learning_management

# Copy Docker environment file
cp .env.docker.example .env
```

#### Step 2: Start Docker Containers

```bash
# Build and start containers
docker-compose up -d

# Or build without cache
docker-compose up -d --build
```

#### Step 3: Install Dependencies

```bash
# Install PHP dependencies
docker-compose exec app composer install

# Install Node.js dependencies
docker-compose exec app npm install
```

#### Step 4: Application Setup

```bash
# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Create storage link
docker-compose exec app php artisan storage:link

# Build frontend assets
docker-compose exec app npm run build
```

#### Step 5: Access the Application

- **Web Application**: http://localhost:8000
- **Database**: localhost:3306 (MySQL) or localhost:5432 (PostgreSQL)
- **Redis**: localhost:6379

### Method 3: Laragon Installation (Windows)

#### Prerequisites
- Laragon installed and configured
- Git for Windows installed

#### Step 1: Clone to Laragon Directory

```bash
# Navigate to Laragon www directory
cd C:\laragon\www

# Clone the repository
git clone <repository-url> service_learning_management
cd service_learning_management
```

#### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### Step 3: Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with database credentials
# Use Laragon's default MySQL credentials
```

#### Step 4: Database Setup

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Create storage link
php artisan storage:link
```

#### Step 5: Build Assets

```bash
# Build frontend assets
npm run build
```

#### Step 6: Access via Laragon

- Start Laragon
- The application will be available at: http://service_learning_management.test

## Environment Configuration

### Environment Variables

#### Essential Configuration

```env
# Application
APP_NAME="Service Learning Management"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service_learning_management
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# GitHub Integration
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback

# Web3 Configuration
WEB3_PROVIDER_URL=your-web3-provider-url
WEB3_CONTRACT_ADDRESS=your-contract-address
WEB3_PRIVATE_KEY=your-private-key

# Security
JWT_SECRET=your-jwt-secret
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

#### Production Configuration

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (Production)
DB_HOST=your-production-db-host
DB_DATABASE=your-production-db-name
DB_USERNAME=your-production-db-user
DB_PASSWORD=your-production-db-password

# Cache (Production)
REDIS_HOST=your-production-redis-host
REDIS_PASSWORD=your-production-redis-password

# Mail (Production)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls

# Security (Production)
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

### Database Configuration

#### MySQL Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service_learning_management
DB_USERNAME=root
DB_PASSWORD=your-password
```

#### PostgreSQL Configuration

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=service_learning_management
DB_USERNAME=postgres
DB_PASSWORD=your-password
```

#### SQLite Configuration (Development)

```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
```

### Cache Configuration

#### Redis Configuration

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

#### File Cache Configuration

```env
CACHE_DRIVER=file
```

## Post-Installation Setup

### Initial Configuration

#### Step 1: Create Admin User

```bash
# Create admin user via artisan command
php artisan make:admin

# Or use the seeder
php artisan db:seed --class=AdminUserSeeder
```

#### Step 2: Configure Services

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 3: Set Up Cron Jobs

```bash
# Add to crontab (Linux/macOS)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

# For Windows, use Task Scheduler
```

#### Step 4: Configure Web Server

##### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/service_learning_management/public
    
    <Directory /path/to/service_learning_management/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/service_learning_management_error.log
    CustomLog ${APACHE_LOG_DIR}/service_learning_management_access.log combined
</VirtualHost>
```

##### Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/service_learning_management/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Verification Steps

#### Step 1: Check Application Status

```bash
# Check Laravel application status
php artisan about

# Check system requirements
php artisan system:check
```

#### Step 2: Test Database Connection

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

#### Step 3: Test Cache

```bash
# Test cache functionality
php artisan cache:test
```

#### Step 4: Test Queue System

```bash
# Test queue system
php artisan queue:work --once
```

#### Step 5: Run Health Checks

```bash
# Run health checks
php artisan health:check
```

## Troubleshooting

### Common Issues

#### Issue 1: Composer Installation Fails

```bash
# Clear Composer cache
composer clear-cache

# Update Composer
composer self-update

# Install with verbose output
composer install -vvv
```

#### Issue 2: Node.js Dependencies Fail

```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and reinstall
rm -rf node_modules package-lock.json
npm install
```

#### Issue 3: Database Connection Issues

```bash
# Check database service
sudo systemctl status mysql
sudo systemctl status postgresql

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### Issue 4: Permission Issues

```bash
# Set proper permissions (Linux/macOS)
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### Issue 5: Storage Link Issues

```bash
# Remove existing link
rm public/storage

# Create new link
php artisan storage:link
```

### Performance Optimization

#### Step 1: Optimize Composer Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

#### Step 2: Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 3: Optimize Frontend Assets

```bash
npm run build
```

#### Step 4: Configure OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

## Security Considerations

### Production Security

#### Step 1: Environment Security

```bash
# Set proper file permissions
chmod 644 .env
chmod 755 storage bootstrap/cache

# Use strong passwords
# Enable HTTPS
# Configure firewall
```

#### Step 2: Database Security

```bash
# Create dedicated database user
# Grant minimal required permissions
# Enable SSL connections
# Regular backups
```

#### Step 3: Application Security

```bash
# Disable debug mode
APP_DEBUG=false

# Use strong encryption
# Enable CSRF protection
# Configure session security
```

## Maintenance

### Regular Maintenance Tasks

#### Daily Tasks

```bash
# Check application health
php artisan health:check

# Monitor logs
tail -f storage/logs/laravel.log
```

#### Weekly Tasks

```bash
# Update dependencies
composer update
npm update

# Run tests
php artisan test
npm run test
```

#### Monthly Tasks

```bash
# Database maintenance
php artisan db:backup

# Clear old logs
php artisan log:clear

# Update application
git pull origin main
composer install
php artisan migrate
```

## Conclusion

This installation guide provides comprehensive instructions for setting up the Service Learning Management System in various environments. Follow the appropriate method based on your requirements and environment.

For additional support:
- Check the [Troubleshooting Guide](docs/TROUBLESHOOTING/README.md)
- Review the [Development Guidelines](docs/DEVELOPMENT/DEVELOPMENT_GUIDELINES.md)
- Consult the [System Architecture](docs/ARCHITECTURE/SYSTEM_ARCHITECTURE.md)

The system is now ready for development, testing, or production deployment!
