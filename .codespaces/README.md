# GitHub Codespaces Configuration

This directory contains all configuration and documentation related to GitHub Codespaces integration.

## Directory Structure

- `config/`: Configuration files for Codespaces
- `scripts/`: Automation scripts
- `docs/`: Detailed documentation
- `templates/`: Template files for Codespaces
- `workflows/`: GitHub Actions workflows

## Quick Start

1. Install the Codespaces CLI:
```bash
npm install -g @github/codespaces-cli
```

2. Authenticate with GitHub:
```bash
gh auth login
```

3. Create a new Codespace:
```bash
php artisan codespace:create
```

## Features

- Automated deployment
- Docker integration
- Security scanning
- Documentation generation
- Task management
- Code quality monitoring
- GitHub Pages integration
- Workflow automation

## Documentation

- [Setup Guide](docs/setup.md)
- [Security Guide](docs/security.md)
- [Development Guide](docs/development.md)
- [Deployment Guide](docs/deployment.md)
- [Testing Guide](docs/testing.md)

## Setup

1. Install the GitHub CLI:
   ```bash
   # Windows (PowerShell)
   winget install GitHub.cli
   
   # macOS
   brew install gh
   
   # Linux
   sudo apt install gh
   ```

2. Authenticate with GitHub:
   ```bash
   gh auth login
   ```

3. Use the Codespace Manager script:
   ```bash
   ./scripts/codespace-manager.sh
   ```

## Available Commands

- `create`: Create a new Codespace
- `list`: List existing Codespaces
- `rebuild <codespace>`: Rebuild a specific Codespace
- `test <codespace>`: Run tests in a specific Codespace

## Development Environment

The Codespace includes:

- PHP 8.2
- Composer
- Docker and Docker Compose
- MySQL 8.0
- Node.js and npm
- Git
- VS Code extensions for PHP development

## Testing

Tests are automatically run:
- On every push to main
- When manually triggered through the GitHub Actions workflow
- Using the Codespace Manager script

## Security

- All sensitive data is stored in GitHub Secrets
- Authentication is handled through GitHub's OAuth flow
- Docker containers run with minimal privileges
- Regular security updates are applied automatically

## Troubleshooting

1. If you encounter authentication issues:
   ```bash
   gh auth logout
   gh auth login
   ```

2. If the Codespace fails to build:
   ```bash
   ./scripts/codespace-manager.sh rebuild <codespace-name>
   ```

3. For test failures:
   ```bash
   ./scripts/codespace-manager.sh test <codespace-name>
   ```

## Contributing

1. Create a new branch for your changes
2. Make your changes
3. Run tests locally
4. Submit a pull request

## CI/CD Pipeline

The GitHub Actions workflow automatically:
1. Creates/updates Codespaces
2. Runs tests
3. Deploys the application

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review the GitHub Actions logs
3. Contact the development team

# Codespaces Infrastructure

This directory contains the infrastructure for managing and deploying services in GitHub Codespaces. The system provides a comprehensive framework for service deployment, monitoring, and management.

## Features

- **Service Management**
  - Automatic service provisioning
  - Dependency resolution
  - Health monitoring
  - Configuration management

- **Deployment Tracking**
  - Git-based deployment history
  - Deployment validation
  - Rollback capabilities
  - State management

- **Monitoring & Self-Healing**
  - Health checks
  - Metrics collection
  - Automatic recovery
  - Alert processing

- **Environment Support**
  - Local development
  - Codespaces deployment
  - Environment-specific configurations
  - Easy environment switching

## Directory Structure

```
.codespaces/
├── config/             # Configuration files
├── deployments/        # Deployment management
├── monitoring/         # Monitoring system
├── services/          # Service implementations
├── tests/             # Test suites
├── state/             # Deployment state
├── logs/              # Log files
├── audit/             # Audit logs
├── composer.json      # PHP dependencies
├── deploy.php         # Deployment script
└── run-tests.php      # Test runner
```

## Installation

1. Install PHP dependencies:
```bash
cd .codespaces
composer install
```

2. Configure services:
- Copy `.codespaces/config/services.json.example` to `.codespaces/config/services.json`
- Update configuration values for your environment

3. Set up environment variables:
- Create `.codespaces/.env` file
- Add required environment variables

## Usage

### Deploying Services

Deploy all services:
```bash
php .codespaces/deploy.php --environment=codespaces
```

Deploy specific service:
```bash
php .codespaces/deploy.php --environment=codespaces --service=mcp
```

### Running Tests

Run all tests:
```bash
php .codespaces/run-tests.php
```

Run specific test category:
```bash
php .codespaces/run-tests.php --category=health
```

### Monitoring

The monitoring system automatically starts when services are deployed. You can view logs in:
- `.codespaces/logs/` - Application logs
- `.codespaces/audit/` - Audit logs
- `.codespaces/state/` - Current deployment state

## Service Types

The system supports the following service types:

1. **API Services**
   - RESTful APIs
   - GraphQL services
   - WebSocket servers

2. **Database Services**
   - PostgreSQL
   - MySQL
   - MongoDB

3. **Cache Services**
   - Redis
   - Memcached

4. **Queue Services**
   - RabbitMQ
   - Apache Kafka

5. **Mail Services**
   - SMTP servers
   - Mail delivery services

## Configuration

### Service Configuration

Each service is configured in `services.json`:

```json
{
    "services": {
        "service_name": {
            "type": "service_type",
            "ports": [port_numbers],
            "dependencies": ["dependency_services"],
            "environment": {
                "ENV_VAR": "value"
            }
        }
    }
}
```

### Environment Configuration

Environment-specific settings are managed through:
- Environment variables
- Configuration files
- Service-specific settings

## Development

### Adding New Services

1. Add service configuration to `services.json`
2. Implement service class in `services/`
3. Add service tests in `tests/`
4. Update deployment scripts if needed

### Running Tests

```bash
composer test
```

### Code Quality

```bash
composer analyze    # Static analysis
composer check-style # Code style check
composer fix-style  # Fix code style issues
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and checks
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 