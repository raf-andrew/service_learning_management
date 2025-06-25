# Deployment and Maintenance Guide

## Overview

This guide provides comprehensive instructions for deploying, configuring, and maintaining the Service Learning Management System in production environments.

---

## System Requirements

### Server Requirements
- **PHP**: 8.1 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Cache**: Redis 6.0+ or Memcached 1.6+
- **Queue**: Redis or Database
- **Storage**: Minimum 50GB available space
- **Memory**: Minimum 4GB RAM (8GB recommended)
- **CPU**: 2+ cores (4+ cores recommended)

### PHP Extensions
```bash
# Required extensions
php-bcmath
php-curl
php-dom
php-fileinfo
php-gd
php-json
php-mbstring
php-mysqlnd
php-opcache
php-pdo
php-xml
php-zip
php-redis
php-memcached
php-openssl
php-sodium
```

---

## Pre-Deployment Checklist

### 1. Environment Setup
- [ ] Server meets minimum requirements
- [ ] PHP extensions installed
- [ ] Database server configured
- [ ] Cache server configured
- [ ] SSL certificate obtained
- [ ] Domain configured
- [ ] Backup strategy planned

### 2. Security Preparation
- [ ] Firewall configured
- [ ] SSH key-based authentication
- [ ] Database credentials secured
- [ ] API keys generated
- [ ] Environment variables prepared
- [ ] Security headers configured

### 3. Monitoring Setup
- [ ] Application monitoring configured
- [ ] Database monitoring configured
- [ ] Server monitoring configured
- [ ] Alerting configured
- [ ] Log aggregation setup

---

## Deployment Process

### Step 1: Server Preparation

#### Update System
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

#### Install Required Software
```bash
# Install PHP and extensions
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-redis php8.1-memcached \
    php8.1-bcmath php8.1-curl php8.1-dom php8.1-fileinfo php8.1-gd \
    php8.1-json php8.1-mbstring php8.1-opcache php8.1-pdo php8.1-xml \
    php8.1-zip php8.1-openssl php8.1-sodium nginx mysql-server redis-server
```

#### Configure PHP
```bash
# Edit php.ini
sudo nano /etc/php/8.1/fpm/php.ini

# Recommended settings
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 64M
post_max_size = 64M
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
```

### Step 2: Database Setup

#### Create Database and User
```sql
CREATE DATABASE service_learning_management;
CREATE USER 'slm_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON service_learning_management.* TO 'slm_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Configure Database
```bash
# Edit MySQL configuration
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Recommended settings
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 128M
```

### Step 3: Application Deployment

#### Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/your-org/service-learning-management.git
sudo chown -R www-data:www-data service-learning-management
cd service-learning-management
```

#### Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
```

#### Configure Environment
```bash
cp .env.example .env
nano .env

# Essential environment variables
APP_NAME="Service Learning Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=service_learning_management
DB_USERNAME=slm_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Security
APP_KEY=base64:your-32-character-key
E2EE_ENABLED=true
SOC2_ENABLED=true
```

#### Generate Application Key
```bash
php artisan key:generate
```

#### Run Migrations
```bash
php artisan migrate --force
```

#### Optimize Application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### Step 4: Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    
    root /var/www/service-learning-management/public;
    index index.php;
    
    # Security headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';" always;
    
    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    limit_req_zone $binary_remote_addr zone=web:10m rate=120r/m;
    
    location / {
        limit_req zone=web burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location /api/ {
        limit_req zone=api burst=10 nodelay;
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

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/service-learning-management/public
    
    <Directory /var/www/service-learning-management/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "DENY"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    ErrorLog ${APACHE_LOG_DIR}/service-learning-error.log
    CustomLog ${APACHE_LOG_DIR}/service-learning-access.log combined
</VirtualHost>
```

### Step 5: Queue Worker Setup

#### Create Systemd Service
```bash
sudo nano /etc/systemd/system/service-learning-queue.service
```

```ini
[Unit]
Description=Service Learning Management Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/service-learning-management/artisan queue:work --sleep=3 --tries=3 --max-time=3600
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

#### Enable and Start Queue Worker
```bash
sudo systemctl enable service-learning-queue
sudo systemctl start service-learning-queue
```

### Step 6: Monitoring Setup

#### Install Monitoring Tools
```bash
# Install monitoring tools
sudo apt install htop iotop nethogs
```

#### Configure Log Rotation
```bash
sudo nano /etc/logrotate.d/service-learning
```

```
/var/www/service-learning-management/storage/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.1-fpm
    endscript
}
```

---

## Post-Deployment Verification

### 1. Health Checks
```bash
# Test application health
curl -H "X-API-Key: your-api-key" https://your-domain.com/api/health

# Test database connection
php artisan tinker
DB::connection()->getPdo();

# Test cache connection
php artisan tinker
Cache::put('test', 'value', 60);
Cache::get('test');
```

### 2. Security Verification
```bash
# Check security headers
curl -I https://your-domain.com

# Test rate limiting
for i in {1..70}; do curl -H "X-API-Key: your-api-key" https://your-domain.com/api/health; done

# Verify SSL configuration
openssl s_client -connect your-domain.com:443 -servername your-domain.com
```

### 3. Performance Testing
```bash
# Test response times
ab -n 1000 -c 10 https://your-domain.com/api/health

# Monitor resource usage
htop
iotop
```

---

## Maintenance Procedures

### Daily Tasks
- [ ] Check application logs for errors
- [ ] Monitor system resources
- [ ] Verify backup completion
- [ ] Check queue worker status

### Weekly Tasks
- [ ] Review performance metrics
- [ ] Check disk space usage
- [ ] Update security patches
- [ ] Review error logs

### Monthly Tasks
- [ ] Update application dependencies
- [ ] Review and rotate logs
- [ ] Performance optimization review
- [ ] Security audit

### Quarterly Tasks
- [ ] Full system backup test
- [ ] Disaster recovery drill
- [ ] Performance benchmarking
- [ ] Security penetration testing

---

## Backup and Recovery

### Automated Backups
```bash
#!/bin/bash
# /usr/local/bin/backup-service-learning.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/service-learning"
DB_NAME="service_learning_management"

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u slm_user -p'secure_password' $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Application files backup
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /var/www/service-learning-management

# Keep only last 30 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### Recovery Procedures

#### Database Recovery
```bash
# Restore database
mysql -u slm_user -p'secure_password' service_learning_management < backup_file.sql
```

#### Application Recovery
```bash
# Restore application files
tar -xzf backup_file.tar.gz -C /
chown -R www-data:www-data /var/www/service-learning-management
```

---

## Troubleshooting

### Common Issues

#### High Memory Usage
```bash
# Check memory usage
free -h
ps aux --sort=-%mem | head

# Optimize PHP settings
sudo nano /etc/php/8.1/fpm/php.ini
# Reduce memory_limit if needed
```

#### Slow Database Queries
```bash
# Enable slow query log
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Analyze slow queries
mysqldumpslow /var/log/mysql/slow.log
```

#### Queue Worker Issues
```bash
# Check queue worker status
sudo systemctl status service-learning-queue

# Restart queue worker
sudo systemctl restart service-learning-queue

# Check queue size
php artisan queue:size
```

#### SSL/TLS Issues
```bash
# Test SSL configuration
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Check certificate expiration
openssl x509 -in /path/to/certificate.crt -text -noout | grep -i "not after"
```

---

## Security Hardening

### Firewall Configuration
```bash
# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### File Permissions
```bash
# Set correct file permissions
sudo chown -R www-data:www-data /var/www/service-learning-management
sudo chmod -R 755 /var/www/service-learning-management
sudo chmod -R 775 /var/www/service-learning-management/storage
sudo chmod -R 775 /var/www/service-learning-management/bootstrap/cache
```

### Database Security
```sql
-- Remove default users
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;

-- Create application-specific user with limited privileges
CREATE USER 'slm_app'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON service_learning_management.* TO 'slm_app'@'localhost';
FLUSH PRIVILEGES;
```

---

## Performance Optimization

### PHP OPcache
```ini
; /etc/php/8.1/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1
```

### Redis Optimization
```conf
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### Database Optimization
```sql
-- Optimize tables
OPTIMIZE TABLE users, encryption_transactions, soc2_certifications;

-- Analyze table statistics
ANALYZE TABLE users, encryption_transactions, soc2_certifications;
```

---

## Support and Resources

### Documentation
- [API Documentation](docs/API_DOCUMENTATION.md)
- [Development Guide](docs/DEVELOPMENT_GUIDE.md)
- [Troubleshooting Guide](docs/TROUBLESHOOTING.md)

### Monitoring Tools
- Application monitoring: Built-in monitoring service
- Server monitoring: htop, iotop, nethogs
- Database monitoring: MySQL Workbench, phpMyAdmin
- Log monitoring: tail, grep, logrotate

### Support Channels
- **Email**: support@service-learning.com
- **Documentation**: https://docs.service-learning.com
- **Status Page**: https://status.service-learning.com
- **GitHub Issues**: https://github.com/your-org/service-learning-management/issues

---

## Version History

### Version 1.0.0 (2024-06-23)
- Initial production release
- E2EE encryption module
- SOC2 compliance module
- Security middleware
- Performance optimization
- Comprehensive monitoring 