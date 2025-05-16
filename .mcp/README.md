# Management Control Protocol (MCP) System

A comprehensive management and control system for interacting with platform services, data sources, and configurations.

## Directory Structure

```
.mcp/
├── README.md
├── init.ps1
├── check.ps1
├── run.ps1
├── stop.ps1
├── status.ps1
├── manage.ps1
├── tests/
│   ├── run-tests.ps1
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── utils/
│   ├── logger.ps1
│   ├── environment.ps1
│   ├── github.ps1
│   ├── mcp.ps1
│   ├── services.ps1
│   ├── data.ps1
│   ├── logs.ps1
│   └── config.ps1
└── logs/
    ├── general/
    ├── services/
    ├── data/
    ├── config/
    └── audit/
```

## Features

- Service Management
  - Health checking
  - Service control (start/stop/restart)
  - Service monitoring
  - Service configuration

- Data Source Management
  - Database connections
  - Data validation
  - Data migration
  - Backup/restore

- Log Management
  - Log collection
  - Log analysis
  - Log rotation
  - Log search

- Configuration Management
  - Configuration validation
  - Configuration deployment
  - Configuration backup
  - Configuration audit

- Developer Utilities
  - Log tailing
  - Service debugging
  - Performance monitoring
  - Audit trail

## Prerequisites

1. System Requirements
   - PowerShell 5.0 or later
   - .NET Framework 4.7.2 or later
   - Windows Management Framework 5.1 or later

2. Required Tools
   - PHP 8.0 or later
   - Composer 2.0 or later
   - Node.js 16.0 or later
   - npm 8.0 or later
   - jq 1.6 or later
   - GitHub CLI

3. Services
   - MySQL 8.0 or later
   - Redis 6.0 or later
   - Apache 2.4 or later

4. Network
   - Access to remote services
   - Valid SSL certificates
   - Required ports open

## Installation

1. Clone the repository
2. Run the initialization script:
   ```powershell
   .\.mcp\init.ps1
   ```

3. Verify the installation:
   ```powershell
   .\.mcp\check.ps1
   ```

## Usage

### Initializing the System

```powershell
# Initialize with default settings
.\.mcp\init.ps1

# Initialize for a specific environment
.\.mcp\init.ps1 -Environment "remote"

# Initialize with verification
.\.mcp\init.ps1 -Verify

# Initialize with auto-healing
.\.mcp\init.ps1 -AutoHeal

# Force initialization
.\.mcp\init.ps1 -Force
```

### Running the System

```powershell
# Run with default settings
.\.mcp\run.ps1

# Run in a specific environment
.\.mcp\run.ps1 -Environment "remote"

# Run with custom interval
.\.mcp\run.ps1 -Interval 300

# Run with auto-healing
.\.mcp\run.ps1 -AutoHeal

# Run in background
.\.mcp\run.ps1 -Background

# Run with verification
.\.mcp\run.ps1 -Verify

# Force run
.\.mcp\run.ps1 -Force
```

### Managing Services

```powershell
# Check service health
.\.mcp\manage.ps1 -Command check-services

# Start a service
.\.mcp\manage.ps1 -Command start-service -Service "mysql"

# Stop a service
.\.mcp\manage.ps1 -Command stop-service -Service "redis"

# Restart a service
.\.mcp\manage.ps1 -Command restart-service -Service "apache"

# View service logs
.\.mcp\manage.ps1 -Command logs -Service "mysql" -Follow
```

### Managing Data Sources

```powershell
# Check database connections
.\.mcp\manage.ps1 -Command check-databases

# Validate data
.\.mcp\manage.ps1 -Command validate-data -Source "users"

# Backup database
.\.mcp\manage.ps1 -Command backup-database -Database "main"

# Restore database
.\.mcp\manage.ps1 -Command restore-database -Database "main" -Backup "backup.sql"
```

### Managing Logs

```powershell
# View logs
.\.mcp\manage.ps1 -Command logs -Category "services"

# Search logs
.\.mcp\manage.ps1 -Command search-logs -Query "error" -StartDate "2024-01-01"

# Rotate logs
.\.mcp\manage.ps1 -Command rotate-logs -Category "services"

# Analyze logs
.\.mcp\manage.ps1 -Command analyze-logs -Category "services" -Period "1d"
```

### Managing Configurations

```powershell
# Validate configuration
.\.mcp\manage.ps1 -Command validate-config

# Deploy configuration
.\.mcp\manage.ps1 -Command deploy-config -Environment "production"

# Backup configuration
.\.mcp\manage.ps1 -Command backup-config

# View audit trail
.\.mcp\manage.ps1 -Command audit-trail -StartDate "2024-01-01"
```

### Developer Utilities

```powershell
# Tail logs
.\.mcp\manage.ps1 -Command tail -Service "mysql" -Lines 100

# Debug service
.\.mcp\manage.ps1 -Command debug -Service "redis"

# Monitor performance
.\.mcp\manage.ps1 -Command monitor -Service "apache" -Metrics "cpu,memory"

# View audit trail
.\.mcp\manage.ps1 -Command audit -StartDate "2024-01-01" -EndDate "2024-01-31"
```

## Parameters

### Common Parameters

- `-Environment`: The environment to use (local, remote)
- `-Verify`: Verify the operation
- `-AutoHeal`: Enable auto-healing
- `-Force`: Force the operation

### Service Parameters

- `-Service`: The service to manage
- `-Action`: The action to perform (start, stop, restart)
- `-Timeout`: Operation timeout in seconds
- `-Retry`: Number of retry attempts

### Data Parameters

- `-Database`: The database to manage
- `-Source`: The data source to use
- `-Backup`: The backup file to use
- `-Validate`: Enable data validation

### Log Parameters

- `-Category`: The log category to view
- `-Lines`: Number of log lines to display
- `-Level`: The log level to filter
- `-SearchString`: String to search in logs
- `-Follow`: Follow log output
- `-StartDate`: Start date for log range
- `-EndDate`: End date for log range

### Configuration Parameters

- `-Config`: The configuration to manage
- `-Deploy`: Enable configuration deployment
- `-Backup`: Enable configuration backup
- `-Validate`: Enable configuration validation

### Developer Parameters

- `-Debug`: Enable debug mode
- `-Metrics`: Metrics to monitor
- `-Period`: Time period for analysis
- `-Format`: Output format (text, json, csv)

## Testing

### Running Tests

```powershell
# Run all tests
.\.mcp\tests\run-tests.ps1

# Run specific test categories
.\.mcp\tests\run-tests.ps1 -Categories Unit,Integration

# Run tests with auto-healing
.\.mcp\tests\run-tests.ps1 -AutoHeal

# Run tests continuously
.\.mcp\tests\run-tests.ps1 -Continuous -Interval 300
```

### Test Categories

1. Unit Tests
   - Service tests
   - Data tests
   - Log tests
   - Config tests

2. Integration Tests
   - Service integration
   - Data integration
   - Log integration
   - Config integration

3. End-to-End Tests
   - Service workflows
   - Data workflows
   - Log workflows
   - Config workflows

## Logging

The system logs all activities with the following levels:

- INFO: General information
- SUCCESS: Successful operations
- WARNING: Warning messages
- ERROR: Error messages
- DEBUG: Debug information

## Environment Configuration

The system supports multiple environments:

1. Local Environment
   - Development
   - Testing
   - Staging

2. Remote Environment
   - Production
   - Backup
   - Disaster Recovery

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is licensed under the MIT License. 