# 🚀 Development Quick Reference

## 🎯 Daily Development Commands

### Environment Setup
```bash
# Start development environment
docker-compose up -d

# Install dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

### Code Quality
```bash
# Run linting
scripts/quality/run-linting.sh

# Run tests
scripts/testing/run-tests.sh

# Check code coverage
scripts/testing/run-coverage.sh

# Run security scan
scripts/security/security-scan.sh
```

### Development Workflow
```bash
# Create new feature branch
git checkout -b feature/your-feature-name

# Run quality checks before commit
scripts/quality/run-code-quality.sh
scripts/testing/run-tests.sh

# Commit with conventional commit message
git commit -m "feat: add new feature"

# Push and create pull request
git push origin feature/your-feature-name
```

## 📁 Directory Structure Quick Reference

### App Directory
```
app/
├── Console/Commands/     # Artisan commands
│   ├── Core/            # Core system commands
│   ├── Development/     # Development commands
│   ├── Infrastructure/  # Infrastructure commands
│   ├── Monitoring/      # Monitoring commands
│   ├── Security/        # Security commands
│   ├── Testing/         # Testing commands
│   ├── Web3/            # Web3 commands
│   ├── Codespaces/      # Codespaces commands
│   ├── Environment/     # Environment commands
│   ├── Sniffing/        # Code quality commands
│   └── Setup/           # Setup commands
├── Http/Controllers/    # HTTP controllers
│   ├── Api/             # API controllers
│   ├── Web/             # Web controllers
│   ├── Admin/           # Admin controllers
│   ├── Search/          # Search controllers
│   ├── GitHub/          # GitHub controllers
│   ├── Sniffing/        # Code quality controllers
│   └── Traits/          # Controller traits
├── Models/              # Eloquent models
│   ├── Core/            # Core models
│   ├── Monitoring/      # Monitoring models
│   ├── Development/     # Development models
│   ├── Sniffing/        # Code quality models
│   └── Infrastructure/  # Infrastructure models
└── Services/            # Business logic services
    ├── Core/            # Core services
    ├── Auth/            # Authentication services
    ├── Monitoring/      # Monitoring services
    ├── Development/     # Development services
    ├── Infrastructure/  # Infrastructure services
    ├── Web3/            # Web3 services
    ├── Codespaces/      # Codespaces services
    ├── Sniffing/        # Code quality services
    ├── Configuration/   # Configuration services
    ├── Caching/         # Caching services
    └── Misc/            # Miscellaneous services
```

### Configuration
```
config/
├── environments/        # Environment-specific configs
│   ├── local.php
│   ├── testing.php
│   ├── staging.php
│   └── production.php
├── modules/             # Module-specific configs
│   ├── mcp.php
│   └── modules.php
├── base/                # Base configurations
│   └── config.base.php
└── shared/              # Shared configurations
    ├── codespaces.php
    └── codespaces.testing.php
```

### Modules
```
modules/
├── web3/                # Web3 integration
├── soc2/                # SOC2 compliance
├── shared/              # Shared utilities
├── mcp/                 # MCP protocol
├── e2ee/                # End-to-end encryption
├── auth/                # Authentication
└── api/                 # API module
```

### Tests
```
tests/
├── Unit/                # Unit tests
├── Feature/             # Feature tests
├── Integration/         # Integration tests
├── E2E/                 # End-to-end tests
├── Performance/         # Performance tests
├── Security/            # Security tests
├── Frontend/            # Frontend tests
├── AI/                  # AI tests
├── MCP/                 # MCP tests
├── Chaos/               # Chaos tests
├── Sanity/              # Sanity tests
├── Functional/          # Functional tests
├── Tenant/              # Tenant tests
├── Sniffing/            # Sniffing tests
├── Infrastructure/      # Infrastructure tests
├── config/              # Config tests
├── Traits/              # Test traits
├── helpers/             # Test helpers
├── scripts/             # Test scripts
├── stubs/               # Test stubs
└── reports/             # Test reports
```

## 🛠️ Common Development Tasks

### Creating New Commands
```bash
# Create command in appropriate category
php artisan make:command Core/YourCommandName

# Example: Create monitoring command
php artisan make:command Monitoring/HealthCheckCommand
```

### Creating New Models
```bash
# Create model in appropriate category
php artisan make:model Models/Core/YourModel

# Example: Create monitoring model
php artisan make:model Models/Monitoring/HealthMetric
```

### Creating New Controllers
```bash
# Create controller in appropriate category
php artisan make:controller Api/YourController

# Example: Create API controller
php artisan make:controller Api/UserController
```

### Creating New Services
```bash
# Create service in appropriate category
# Example: Create monitoring service
mkdir -p app/Services/Monitoring
touch app/Services/Monitoring/HealthService.php
```

### Creating New Tests
```bash
# Create unit test
php artisan make:test Unit/YourTest

# Create feature test
php artisan make:test Feature/YourFeatureTest

# Create test in appropriate category
# Example: Create monitoring test
php artisan make:test Unit/Monitoring/HealthTest
```

## 🔧 Configuration Management

### Environment Variables
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database
php artisan config:cache
```

### Module Configuration
```bash
# Publish module configs
php artisan vendor:publish --tag=module-config

# Clear configuration cache
php artisan config:clear
```

## 🧪 Testing Commands

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test category
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run specific test file
php artisan test tests/Unit/YourTest.php

# Run with coverage
php artisan test --coverage
```

### Test Categories
```bash
# Unit tests
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature

# Integration tests
php artisan test --testsuite=Integration

# E2E tests
php artisan test --testsuite=E2E

# Performance tests
php artisan test --testsuite=Performance

# Security tests
php artisan test --testsuite=Security
```

## 🚀 Deployment Commands

### Development Deployment
```bash
# Start development environment
docker-compose up -d

# Run development setup
scripts/development/setup-environment.sh
```

### Staging Deployment
```bash
# Deploy to staging
scripts/deployment/deploy-staging.sh

# Run staging tests
scripts/testing/run-staging-tests.sh
```

### Production Deployment
```bash
# Deploy to production
scripts/deployment/deploy-production.sh

# Monitor deployment
scripts/monitoring/monitor-production.sh
```

## 🔍 Debugging Commands

### Application Debugging
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check application status
php artisan about

# List all routes
php artisan route:list

# Check queue status
php artisan queue:work
```

### Database Debugging
```bash
# Check database connection
php artisan tinker
DB::connection()->getPdo();

# Run migrations with verbose output
php artisan migrate --verbose

# Check migration status
php artisan migrate:status
```

### Log Analysis
```bash
# View application logs
tail -f storage/logs/laravel.log

# View error logs
tail -f storage/logs/error.log

# View access logs
tail -f storage/logs/access.log
```

## 📊 Monitoring Commands

### Health Checks
```bash
# Run health check
php artisan health:check

# Check system status
php artisan system:status

# Monitor performance
php artisan performance:monitor
```

### Security Monitoring
```bash
# Run security audit
php artisan security:audit

# Check vulnerabilities
php artisan security:scan

# Monitor access logs
php artisan security:monitor
```

## 🔒 Security Commands

### Authentication
```bash
# Generate API key
php artisan auth:generate-key

# Check user permissions
php artisan auth:check-permissions

# Audit user access
php artisan auth:audit-access
```

### Compliance
```bash
# Run SOC2 compliance check
php artisan compliance:soc2-check

# Generate compliance report
php artisan compliance:generate-report

# Audit data protection
php artisan compliance:audit-data
```

## 📚 Documentation

### Key Documentation Files
- **Development Guidelines**: `docs/DEVELOPMENT/DEVELOPMENT_GUIDELINES.md`
- **Onboarding Guide**: `docs/DEVELOPMENT/ONBOARDING_GUIDE.md`
- **Installation Guide**: `docs/DEVELOPMENT/INSTALLATION.md`
- **API Documentation**: `docs/API/`
- **Architecture Documentation**: `docs/ARCHITECTURE/`
- **Troubleshooting**: `docs/TROUBLESHOOTING/`

### Generating Documentation
```bash
# Generate API documentation
php artisan docs:generate-api

# Generate architecture documentation
php artisan docs:generate-architecture

# Update documentation
php artisan docs:update
```

## 🚨 Emergency Commands

### Rollback
```bash
# Emergency rollback
scripts/deployment/emergency-rollback.sh

# Rollback migrations
php artisan migrate:rollback

# Clear all caches
php artisan cache:clear && php artisan config:clear
```

### Monitoring
```bash
# Emergency monitoring
scripts/monitoring/emergency-monitoring.sh

# Check system health
php artisan health:emergency-check

# Monitor critical services
php artisan monitoring:critical-services
```

## 📞 Support

### Getting Help
- **Development Guidelines**: Check `docs/DEVELOPMENT/DEVELOPMENT_GUIDELINES.md`
- **Troubleshooting**: Check `docs/TROUBLESHOOTING/`
- **Common Issues**: Check `docs/TROUBLESHOOTING/COMMON_ISSUES.md`
- **Team Resources**: Check team documentation

### External Resources
- **Laravel Documentation**: https://laravel.com/docs
- **Vue.js Documentation**: https://vuejs.org/guide/
- **Docker Documentation**: https://docs.docker.com/

---

**Remember**: Always run quality checks before committing code and keep documentation updated! 🚀 