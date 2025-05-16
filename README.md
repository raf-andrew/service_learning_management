# Service Learning Management System

A comprehensive service learning management system with GitHub Codespaces integration, automated deployment, and developer tools.

## Features

- **GitHub Codespaces Integration**
  - Automated setup and deployment
  - Docker container configuration
  - Development environment management
  - CLI interaction through Artisan commands

- **Developer Credentials Management**
  - Secure token storage
  - Permission management
  - Token expiration
  - Activity tracking

- **Task Management**
  - GitHub Issues integration
  - Project boards
  - Milestone tracking
  - Automated workflows

- **Code Quality**
  - Automated testing
  - Code style enforcement
  - Static analysis
  - Coverage reporting

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js and NPM
- Docker and Docker Compose
- GitHub account with Codespaces access

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/service-learning-management.git
   cd service-learning-management
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
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

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## GitHub Codespaces Setup

1. Create a new Codespace:
   ```bash
   php artisan codespace:create --name=my-codespace
   ```

2. List available Codespaces:
   ```bash
   php artisan codespace:list
   ```

3. Connect to a Codespace:
   ```bash
   php artisan codespace:connect --name=my-codespace
   ```

4. Rebuild a Codespace:
   ```bash
   php artisan codespace:rebuild --name=my-codespace
   ```

5. Delete a Codespace:
   ```bash
   php artisan codespace:delete --name=my-codespace
   ```

## Developer Credentials

1. Create a new credential:
   ```bash
   curl -X POST http://localhost:8000/api/developer-credentials \
     -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{
       "github_token": "your_github_token",
       "github_username": "your_username",
       "permissions": {
         "codespaces": true,
         "repositories": true,
         "workflows": true
       }
     }'
   ```

2. List credentials:
   ```bash
   curl -X GET http://localhost:8000/api/developer-credentials \
     -H "Authorization: Bearer {token}"
   ```

3. Update credential:
   ```bash
   curl -X PUT http://localhost:8000/api/developer-credentials/{id} \
     -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{
       "permissions": {
         "codespaces": true,
         "repositories": true,
         "workflows": false
       }
     }'
   ```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test --filter=CodespaceTest
php artisan test --filter=DeveloperCredentialTest
```

## Documentation

Generate documentation:
```bash
php artisan docs:generate
```

View documentation:
- API Documentation: `/docs/api.md`
- Features: `/docs/features.md`
- Setup Guide: `/docs/setup.md`
- Contributing Guide: `/docs/contributing.md`

## GitHub Integration

- **GitHub Pages**: Documentation is automatically deployed to GitHub Pages
- **GitHub Actions**: Automated testing, deployment, and code quality checks
- **GitHub Issues**: Task management and bug tracking
- **GitHub Projects**: Project management and organization
- **GitHub Security**: Security scanning and vulnerability detection

## Development Workflow

1. Create a new branch
2. Make changes
3. Write tests
4. Run tests
5. Submit pull request
6. Code review
7. Merge and deploy

## Code Style

- Follow PSR-12
- Use type hints
- Write meaningful comments
- Follow Laravel conventions

## Security

- Token encryption
- Permission-based access control
- Automatic token rotation
- Audit logging
- Two-factor authentication
- Security scanning

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the development team.

## Acknowledgments

- Laravel Framework
- GitHub Codespaces
- Docker
- All contributors and maintainers 