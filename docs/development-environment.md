# Development Environment Setup

## Prerequisites

- Docker and Docker Compose
- Git
- Composer (optional, as it's included in the Docker container)

## Getting Started

1. Clone the repository:
   ```bash
   git clone [repository-url]
   cd service_learning_management
   ```

2. Start the development environment:
   ```bash
   docker-compose up -d
   ```

3. Install dependencies:
   ```bash
   docker-compose exec app composer install
   ```

4. Set up the database:
   ```bash
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan db:seed
   ```

5. Access the application:
   - Web application: http://localhost:8000
   - MailHog (email testing): http://localhost:8025

## Development Tools

### Database
- Host: localhost
- Port: 3306
- Database: service_learning
- Username: root
- Password: secret

### Redis
- Host: localhost
- Port: 6379

### Email Testing
- SMTP Server: mailhog
- SMTP Port: 1025
- Web Interface: http://localhost:8025

## Common Commands

### Running Tests
```bash
docker-compose exec app php artisan test
```

### Database Commands
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Seed database
docker-compose exec app php artisan db:seed
```

### Composer Commands
```bash
# Install dependencies
docker-compose exec app composer install

# Update dependencies
docker-compose exec app composer update

# Add new package
docker-compose exec app composer require package-name
```

### Artisan Commands
```bash
# Clear cache
docker-compose exec app php artisan cache:clear

# Generate application key
docker-compose exec app php artisan key:generate

# List all routes
docker-compose exec app php artisan route:list
```

## Troubleshooting

### Container Issues
1. Check container status:
   ```bash
   docker-compose ps
   ```

2. View container logs:
   ```bash
   docker-compose logs app
   ```

3. Restart containers:
   ```bash
   docker-compose restart
   ```

### Permission Issues
If you encounter permission issues:
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Issues
1. Check if the database container is running:
   ```bash
   docker-compose ps db
   ```

2. Check database logs:
   ```bash
   docker-compose logs db
   ```

## Development Workflow

1. Create a new branch for your feature
2. Make your changes
3. Run tests
4. Submit a pull request

## Best Practices

1. Always run tests before committing
2. Keep your Docker containers up to date
3. Use meaningful commit messages
4. Document any new environment requirements
5. Keep the development environment documentation updated 