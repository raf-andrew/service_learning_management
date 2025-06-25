# Developer Onboarding Guide

## Welcome to the Service Learning Management System!

This guide will help you get started with the reorganized codebase and understand the project structure, development workflow, and best practices.

## Quick Start

### Prerequisites
- PHP 8.1+ with required extensions
- Composer
- Node.js 18+ and npm
- Git
- Docker (optional, for containerized development)
- Laragon (recommended for Windows development)

### Initial Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd service_learning_management
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Start development server**
   ```bash
   php artisan serve
   npm run dev
   ```

## Project Structure Overview

### Key Directories

#### `app/` - Laravel Application Core
```
app/
├── Console/Commands/     # Artisan commands organized by domain
├── Http/Controllers/     # Controllers organized by type
├── Models/              # Eloquent models organized by domain
└── Services/            # Business logic services
```

#### `config/` - Configuration Files
```
config/
├── environments/        # Environment-specific configs
├── modules/            # Module-specific configs
├── base/               # Base configurations
└── shared/             # Shared configurations
```

#### `database/` - Database Files
```
database/
├── migrations/         # Database migrations by domain
├── seeders/           # Database seeders
└── factories/         # Model factories
```

#### `routes/` - Route Definitions
```
routes/
├── web/               # Web routes
├── api/               # API routes
├── console/           # Console routes
├── modules/           # Module routes
└── shared/            # Shared routes
```

#### `src/` - Frontend Source Code
```
src/
├── components/        # Vue components
├── pages/            # Page components
├── stores/           # Pinia stores
├── services/         # Frontend services
└── utils/            # Utility functions
```

#### `tests/` - Test Files
```
tests/
├── Unit/             # Unit tests
├── Feature/          # Feature tests
├── Integration/      # Integration tests
├── E2E/              # End-to-end tests
└── ...               # Other test types
```

## Development Workflow

### 1. Understanding the Domain Organization

The project is organized by domains rather than technical layers. This means:

- **Core**: User management, system operations
- **Monitoring**: Health checks, metrics, analytics
- **Development**: Development tools, environment management
- **Infrastructure**: Infrastructure management, deployment
- **Security**: Authentication, authorization, compliance
- **Web3**: Blockchain integration, smart contracts
- **Codespaces**: GitHub Codespaces integration

### 2. Adding New Features

#### Backend Development

1. **Create/Update Models** (`app/Models/[Domain]/`)
   ```php
   namespace App\Models\Core;
   
   class NewModel extends Model
   {
       // Model implementation
   }
   ```

2. **Create/Update Services** (`app/Services/[Domain]/`)
   ```php
   namespace App\Services\Core;
   
   class NewService
   {
       // Service implementation
   }
   ```

3. **Create/Update Controllers** (`app/Http/Controllers/[Type]/`)
   ```php
   namespace App\Http\Controllers\Api;
   
   class NewController extends Controller
   {
       // Controller implementation
   }
   ```

4. **Create/Update Commands** (`app/Console/Commands/[Domain]/`)
   ```php
   namespace App\Console\Commands\Core;
   
   class NewCommand extends Command
   {
       // Command implementation
   }
   ```

5. **Create/Update Migrations** (`database/migrations/[domain]/`)
   ```bash
   php artisan make:migration create_new_table --path=database/migrations/core
   ```

#### Frontend Development

1. **Create/Update Components** (`src/components/[type]/`)
   ```vue
   <!-- src/components/features/NewComponent.vue -->
   <template>
     <!-- Component template -->
   </template>
   
   <script setup lang="ts">
   // Component logic
   </script>
   ```

2. **Create/Update Pages** (`src/pages/[domain]/`)
   ```vue
   <!-- src/pages/core/NewPage.vue -->
   <template>
     <!-- Page template -->
   </template>
   ```

3. **Create/Update Stores** (`src/stores/[domain]/`)
   ```typescript
   // src/stores/core/newStore.ts
   export const useNewStore = defineStore('new', {
     // Store implementation
   })
   ```

### 3. Testing

#### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test types
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Integration

# Run frontend tests
npm run test
```

#### Writing Tests
- **Unit Tests**: Test individual classes and methods
- **Feature Tests**: Test complete features and API endpoints
- **Integration Tests**: Test component interactions and external services
- **E2E Tests**: Test complete user journeys

### 4. Code Quality

#### PHP Code Quality
```bash
# Run PHP_CodeSniffer
./vendor/bin/phpcs

# Run PHPStan for static analysis
./vendor/bin/phpstan analyse

# Run PHPUnit with coverage
./vendor/bin/phpunit --coverage-html coverage
```

#### Frontend Code Quality
```bash
# Run ESLint
npm run lint

# Run TypeScript type checking
npm run type-check

# Run Prettier
npm run format
```

## Common Development Tasks

### 1. Adding a New API Endpoint

1. **Create Controller** (if needed)
   ```bash
   php artisan make:controller Api/NewController
   ```

2. **Add Route** (`routes/api/v1.php`)
   ```php
   Route::get('/new-endpoint', [NewController::class, 'index']);
   ```

3. **Add Tests** (`tests/Feature/api/`)
   ```php
   public function test_new_endpoint_returns_data()
   {
       // Test implementation
   }
   ```

### 2. Adding a New Database Table

1. **Create Migration**
   ```bash
   php artisan make:migration create_new_table --path=database/migrations/core
   ```

2. **Create Model**
   ```bash
   php artisan make:model Core/NewModel
   ```

3. **Run Migration**
   ```bash
   php artisan migrate
   ```

### 3. Adding a New Frontend Page

1. **Create Page Component** (`src/pages/[domain]/NewPage.vue`)
2. **Add Route** (if using Vue Router)
3. **Add to Navigation** (if needed)
4. **Add Tests** (`tests/Frontend/pages/`)

### 4. Adding a New Artisan Command

1. **Create Command**
   ```bash
   php artisan make:command Core/NewCommand
   ```

2. **Register Command** (if needed)
3. **Add Tests** (`tests/Unit/commands/`)

## Configuration Management

### Environment Configuration
- **Local**: `config/environments/local.php`
- **Testing**: `config/environments/testing.php`
- **Staging**: `config/environments/staging.php`
- **Production**: `config/environments/production.php`

### Module Configuration
- **MCP**: `config/modules/mcp.php`
- **Modules**: `config/modules/modules.php`

## Debugging and Troubleshooting

### Common Issues

1. **Composer Autoload Issues**
   ```bash
   composer dump-autoload
   ```

2. **Configuration Cache Issues**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Database Issues**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Frontend Build Issues**
   ```bash
   npm run build
   npm run dev
   ```

### Debugging Tools

1. **Laravel Debugbar** (for backend debugging)
2. **Vue DevTools** (for frontend debugging)
3. **Laravel Telescope** (for request/response debugging)
4. **Logs** (`storage/logs/`)

## Best Practices

### Code Organization
- Follow the established domain-based organization
- Keep files in their appropriate directories
- Use descriptive names for files and classes
- Follow Laravel and Vue.js conventions

### Git Workflow
- Use feature branches for new development
- Write descriptive commit messages
- Use pull requests for code review
- Keep commits focused and atomic

### Documentation
- Document complex business logic
- Update API documentation when adding endpoints
- Keep README files up to date
- Document architectural decisions

### Testing
- Write tests for new features
- Maintain high test coverage
- Use descriptive test names
- Test both positive and negative scenarios

## Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)
- [Project Documentation](./)

### Tools
- [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper)
- [Vue.js DevTools](https://devtools.vuejs.org/)
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)

### Team Communication
- Use the project's issue tracking system
- Participate in code reviews
- Ask questions in team channels
- Share knowledge through documentation

## Getting Help

### Internal Resources
- Check existing documentation in `docs/`
- Review existing code for examples
- Ask team members for guidance
- Use the project's issue tracking system

### External Resources
- Laravel community forums
- Vue.js community forums
- Stack Overflow
- Official documentation

## Conclusion

This onboarding guide provides the foundation for working with the reorganized Service Learning Management System. As you become more familiar with the codebase, you'll develop your own workflows and best practices.

Remember:
- Follow the established patterns and conventions
- Ask questions when you're unsure
- Contribute to documentation improvements
- Share knowledge with the team

Welcome to the team! 🎉
