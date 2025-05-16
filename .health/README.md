# Health Monitoring System

A comprehensive health monitoring system for managing and monitoring the health of your services.

## Directory Structure

```
.health/
├── README.md
├── init.ps1
├── deploy.ps1
├── run.ps1
├── stop.ps1
├── status.ps1
├── check.ps1
├── manage.ps1
└── utils/
    ├── logger.ps1
    ├── environment.ps1
    ├── github.ps1
    └── manage-env.ps1
```

## Features

- Health monitoring and status checking
- Service connectivity verification
- GitHub integration
- Environment management
- Logging and alerting
- Self-healing capabilities
- Deployment management

## Usage

### Initializing the System

```powershell
# Initialize with default settings
.\.health\init.ps1

# Initialize for a specific environment
.\.health\init.ps1 -Environment "remote"

# Initialize with verification
.\.health\init.ps1 -Verify

# Initialize with auto-healing
.\.health\init.ps1 -AutoHeal

# Force initialization
.\.health\init.ps1 -Force
```

### Deploying to GitHub

```powershell
# Deploy to production
.\.health\deploy.ps1

# Deploy to a specific environment
.\.health\deploy.ps1 -Environment "remote"

# Deploy with verification
.\.health\deploy.ps1 -Verify

# Deploy with auto-healing
.\.health\deploy.ps1 -AutoHeal

# Force deployment
.\.health\deploy.ps1 -Force
```

### Running the System

```powershell
# Run with default settings
.\.health\run.ps1

# Run in a specific environment
.\.health\run.ps1 -Environment "remote"

# Run with custom interval
.\.health\run.ps1 -Interval 300

# Run with auto-healing
.\.health\run.ps1 -AutoHeal

# Run in background
.\.health\run.ps1 -Background

# Run with verification
.\.health\run.ps1 -Verify

# Force run
.\.health\run.ps1 -Force
```

### Stopping the System

```powershell
# Stop monitoring
.\.health\stop.ps1

# Force stop
.\.health\stop.ps1 -Force
```

### Checking Status

```powershell
# Check basic status
.\.health\status.ps1

# Check detailed status
.\.health\status.ps1 -Detailed
```

### Checking Health

```powershell
# Check health with default settings
.\.health\check.ps1

# Check health in a specific environment
.\.health\check.ps1 -Environment "remote"

# Check health with auto-healing
.\.health\check.ps1 -AutoHeal

# Check health with detailed output
.\.health\check.ps1 -Detailed
```

### Managing the System

```powershell
# Start monitoring
.\.health\manage.ps1 -Command start -Interval 300 -AutoHeal -Background

# Stop monitoring
.\.health\manage.ps1 -Command stop

# Check status
.\.health\manage.ps1 -Command status

# Run health checks
.\.health\manage.ps1 -Command check -AutoHeal

# View logs
.\.health\manage.ps1 -Command logs -Category "HealthCheck" -Lines 100 -Level "ERROR" -Follow

# Switch environment
.\.health\manage.ps1 -Command switch-env -Environment "remote"
```

### Managing Environment

```powershell
# Switch to local environment
.\.health\utils\manage-env.ps1 -Environment "local"

# Switch to remote environment
.\.health\utils\manage-env.ps1 -Environment "remote"

# Switch with verification
.\.health\utils\manage-env.ps1 -Environment "remote" -Verify

# Switch with auto-healing
.\.health\utils\manage-env.ps1 -Environment "remote" -AutoHeal

# Switch with detailed output
.\.health\utils\manage-env.ps1 -Environment "remote" -Detailed
```

## Parameters

### Common Parameters

- `-Environment`: The environment to use (local, remote)
- `-Verify`: Verify the operation
- `-AutoHeal`: Enable auto-healing
- `-Force`: Force the operation

### init.ps1 Parameters

- `-Environment`: The environment to initialize (local, remote)
- `-Verify`: Verify initialization
- `-AutoHeal`: Enable auto-healing
- `-Force`: Force initialization

### deploy.ps1 Parameters

- `-Environment`: The environment to deploy to (local, remote)
- `-Branch`: The branch to deploy
- `-Verify`: Verify deployment
- `-AutoHeal`: Enable auto-healing
- `-Force`: Force deployment

### run.ps1 Parameters

- `-Environment`: The environment to run in (local, remote)
- `-Interval`: The interval between checks in seconds (default: 300)
- `-AutoHeal`: Enable auto-healing
- `-Background`: Run monitoring in the background
- `-Verify`: Verify monitoring
- `-Force`: Force monitoring

### stop.ps1 Parameters

- `-Force`: Force stop

### status.ps1 Parameters

- `-Detailed`: Show detailed status

### check.ps1 Parameters

- `-Environment`: The environment to check (local, remote)
- `-AutoHeal`: Enable auto-healing
- `-Detailed`: Show detailed health information

### manage.ps1 Parameters

- `-Command`: The command to execute (start, stop, status, check, logs, switch-env)
- `-Environment`: The environment to use (local, remote)
- `-Interval`: The interval between checks in seconds (default: 300)
- `-AutoHeal`: Enable auto-healing
- `-Background`: Run monitoring in the background
- `-Category`: The log category to view
- `-Lines`: Number of log lines to display
- `-Level`: The log level to filter
- `-SearchString`: String to search in logs
- `-Follow`: Follow log output

### manage-env.ps1 Parameters

- `-Environment`: The environment to switch to (local, remote)
- `-Verify`: Verify the environment
- `-AutoHeal`: Enable auto-healing
- `-Detailed`: Show detailed environment information

## Health Checks

The system performs the following health checks:

1. Service Connectivity
   - MySQL
   - Redis
   - Apache

2. GitHub Integration
   - Authentication
   - API Access
   - Actions Availability

3. Environment Configuration
   - Environment Variables
   - Service Configuration
   - Log Level

## Self-Healing

The system includes self-healing capabilities for:

1. Service Issues
   - Service Restart
   - Connection Recovery
   - Configuration Fix

2. GitHub Issues
   - Authentication Recovery
   - Token Refresh
   - API Access Recovery

3. Environment Issues
   - Variable Recovery
   - Configuration Recovery
   - Service Recovery

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

## GitHub Integration

The system integrates with GitHub for:

1. Deployment
   - Branch Management
   - Environment Management
   - Action Management

2. Monitoring
   - Status Checks
   - Action Monitoring
   - Issue Tracking

## Testing

The system includes comprehensive testing:

1. Unit Tests
   - Service Tests
   - Integration Tests
   - Environment Tests

2. Integration Tests
   - GitHub Tests
   - Service Tests
   - Environment Tests

3. End-to-End Tests
   - Deployment Tests
   - Monitoring Tests
   - Recovery Tests

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is licensed under the MIT License. 