# Codespaces Setup Guide

## Prerequisites

- GitHub account with Codespaces access
- GitHub CLI installed
- Docker installed (for local development)
- PHP 8.2 or higher
- Composer
- Node.js and NPM

## Initial Setup

1. Clone the repository:
```bash
git clone https://github.com/your-username/service-learning-management.git
cd service-learning-management
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure GitHub credentials:
```bash
gh auth login
```

## Creating a Codespace

1. Using the CLI:
```bash
php artisan codespace:create --name=my-codespace
```

2. Using the script:
```bash
.codespaces/scripts/codespace.sh create my-codespace
```

## Managing Codespaces

### List Codespaces
```bash
php artisan codespace:list
# or
.codespaces/scripts/codespace.sh list
```

### Connect to a Codespace
```bash
php artisan codespace:connect --name=my-codespace
# or
.codespaces/scripts/codespace.sh connect my-codespace
```

### Rebuild a Codespace
```bash
php artisan codespace:rebuild --name=my-codespace
# or
.codespaces/scripts/codespace.sh rebuild my-codespace
```

### Delete a Codespace
```bash
php artisan codespace:delete --name=my-codespace
# or
.codespaces/scripts/codespace.sh delete my-codespace
```

## Development Workflow

1. Create a new branch:
```bash
git checkout -b feature/my-feature
```

2. Make your changes

3. Run tests:
```bash
php artisan test
```

4. Commit and push:
```bash
git add .
git commit -m "Add my feature"
git push origin feature/my-feature
```

5. Create a pull request:
```bash
gh pr create
```

## Security

- All Codespaces are created with secure defaults
- GitHub authentication is required
- Developer credentials are encrypted
- Regular security scans are performed
- Access is logged and audited

## Troubleshooting

### Common Issues

1. Authentication Failed
```bash
gh auth login
```

2. Codespace Creation Failed
- Check your GitHub token permissions
- Verify your repository access
- Ensure you have sufficient quota

3. Connection Issues
- Check your network connection
- Verify the Codespace is running
- Check your GitHub CLI installation

### Getting Help

- Check the [GitHub Codespaces documentation](https://docs.github.com/en/codespaces)
- Review the [troubleshooting guide](docs/troubleshooting.md)
- Open an issue in the repository 