<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateDocsCommand extends Command
{
    protected $signature = 'docs:generate';
    protected $description = 'Generate project documentation';

    public function handle()
    {
        $this->info('Generating documentation...');

        // Create docs directory if it doesn't exist
        if (!File::exists(base_path('docs'))) {
            File::makeDirectory(base_path('docs'));
        }

        // Generate main README
        $this->generateMainReadme();

        // Generate Codespaces documentation
        $this->generateCodespacesDocs();

        // Generate Docker documentation
        $this->generateDockerDocs();

        // Generate API documentation
        $this->generateApiDocs();

        // Generate feature documentation
        $this->generateFeatureDocs();

        // Generate setup guide
        $this->generateSetupGuide();

        // Generate contribution guide
        $this->generateContributionGuide();

        $this->info('Documentation generated successfully!');
    }

    protected function generateMainReadme()
    {
        $content = <<<MARKDOWN
# Service Learning Management System

A comprehensive service learning management system built with Laravel and Docker, designed to be deployed and managed through GitHub Codespaces.

## Features

- Automated Codespace setup and deployment
- Docker-based development environment
- GitHub Pages documentation
- Comprehensive testing suite
- CI/CD workflows

## Quick Start

1. Clone the repository
2. Run \`php artisan codespace:create\` to create a new Codespace
3. Access your Codespace through the GitHub web interface or CLI

## Documentation

- [Codespaces Guide](docs/codespaces.md)
- [Docker Setup](docs/docker.md)
- [API Documentation](docs/api.md)

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
MARKDOWN;

        File::put(base_path('docs/README.md'), $content);
    }

    protected function generateCodespacesDocs()
    {
        $content = <<<MARKDOWN
# GitHub Codespaces Guide

## Overview

This project uses GitHub Codespaces for development and deployment. Codespaces provide a consistent development environment that can be accessed from anywhere.

## Configuration

The Codespaces configuration is stored in \`.codespaces/config/codespaces.json\`. This file contains:

- Environment settings
- Docker configurations
- GitHub integration settings
- Documentation paths
- Testing configurations

## Available Commands

- \`php artisan codespace:create\` - Create a new Codespace
- \`php artisan codespace:delete\` - Delete a Codespace
- \`php artisan codespace:rebuild\` - Rebuild a Codespace
- \`php artisan codespace:list\` - List all Codespaces
- \`php artisan codespace:connect\` - Connect to a Codespace

## Environment Management

The system supports multiple environments:

- Development
- Staging
- Production

Each environment has its own configuration and can be managed independently.

## State Management

Codespace state is tracked in \`.codespaces/state/codespaces.json\`. This file maintains:

- Environment status
- Service states
- Network configurations
- Volume information
- GitHub authentication status

## Best Practices

1. Always use the provided commands for Codespace management
2. Keep the configuration files up to date
3. Document any changes to the Codespace setup
4. Test changes in development before deploying to production
MARKDOWN;

        File::put(base_path('docs/codespaces.md'), $content);
    }

    protected function generateDockerDocs()
    {
        $content = <<<MARKDOWN
# Docker Setup Guide

## Overview

This project uses Docker for containerization and service management. The setup includes:

- Application container
- MySQL database
- Redis cache
- Nginx web server

## Configuration

Docker configuration is managed through:

- \`docker-compose.yml\` - Main service configuration
- \`Dockerfile\` - Application container definition
- \`docker/nginx/conf.d/default.conf\` - Nginx configuration
- \`docker/php/local.ini\` - PHP configuration

## Services

### Application

- PHP 8.2
- Laravel framework
- Composer dependencies

### MySQL

- Version 8.0
- Persistent volume storage
- Custom configuration

### Redis

- Version 7.0
- Persistent volume storage
- Cache configuration

### Nginx

- Latest stable version
- Custom configuration
- SSL support

## Volume Management

Volumes are used for:

- MySQL data persistence
- Redis data persistence
- Application storage

## Network Configuration

The setup uses a custom network for service communication:

- Internal service discovery
- Isolated network environment
- Port mapping for external access

## Best Practices

1. Use the provided Docker commands
2. Keep images up to date
3. Monitor resource usage
4. Backup volumes regularly
5. Use environment variables for configuration
MARKDOWN;

        File::put(base_path('docs/docker.md'), $content);
    }

    protected function generateApiDocs()
    {
        $content = <<<MARKDOWN
# API Documentation

## Overview

The Service Learning Management System provides a RESTful API for managing service learning activities.

## Authentication

All API endpoints require authentication using Laravel Sanctum.

### Headers

\`\`\`
Authorization: Bearer {token}
Accept: application/json
\`\`\`

## Endpoints

### Users

#### List Users
\`\`\`
GET /api/users
\`\`\`

#### Get User
\`\`\`
GET /api/users/{id}
\`\`\`

#### Create User
\`\`\`
POST /api/users
\`\`\`

#### Update User
\`\`\`
PUT /api/users/{id}
\`\`\`

#### Delete User
\`\`\`
DELETE /api/users/{id}
\`\`\`

### Service Learning Activities

#### List Activities
\`\`\`
GET /api/activities
\`\`\`

#### Get Activity
\`\`\`
GET /api/activities/{id}
\`\`\`

#### Create Activity
\`\`\`
POST /api/activities
\`\`\`

#### Update Activity
\`\`\`
PUT /api/activities/{id}
\`\`\`

#### Delete Activity
\`\`\`
DELETE /api/activities/{id}
\`\`\`

## Error Handling

The API uses standard HTTP status codes and returns errors in the following format:

\`\`\`json
{
    "error": {
        "message": "Error message",
        "code": "ERROR_CODE"
    }
}
\`\`\`

## Rate Limiting

API requests are rate limited to 60 requests per minute per IP address.

## Versioning

The API is versioned through the URL path. The current version is v1.

## Best Practices

1. Always include authentication headers
2. Handle rate limiting
3. Implement proper error handling
4. Use appropriate HTTP methods
5. Follow RESTful conventions
MARKDOWN;

        File::put(base_path('docs/api.md'), $content);
    }

    protected function generateFeatureDocs()
    {
        $this->info('Generating feature documentation...');

        $featureDocs = [
            'title' => 'Features',
            'description' => 'Project features and functionality',
            'features' => [
                'codespaces' => $this->getCodespaceDocs(),
                'developer-credentials' => $this->getDeveloperCredentialDocs(),
                'task-management' => $this->getTaskManagementDocs(),
                'code-quality' => $this->getCodeQualityDocs(),
            ],
        ];

        File::put(
            base_path('docs/features.md'),
            $this->formatMarkdown($featureDocs)
        );
    }

    protected function generateSetupGuide()
    {
        $this->info('Generating setup guide...');

        $setupGuide = [
            'title' => 'Setup Guide',
            'description' => 'Project setup and configuration guide',
            'requirements' => $this->getRequirements(),
            'installation' => $this->getInstallationSteps(),
            'configuration' => $this->getConfigurationSteps(),
            'development' => $this->getDevelopmentSetup(),
        ];

        File::put(
            base_path('docs/setup.md'),
            $this->formatMarkdown($setupGuide)
        );
    }

    protected function generateContributionGuide()
    {
        $this->info('Generating contribution guide...');

        $contributionGuide = [
            'title' => 'Contribution Guide',
            'description' => 'Guide for contributing to the project',
            'workflow' => $this->getWorkflowSteps(),
            'code-style' => $this->getCodeStyleGuide(),
            'testing' => $this->getTestingGuide(),
            'documentation' => $this->getDocumentationGuide(),
        ];

        File::put(
            base_path('docs/contributing.md'),
            $this->formatMarkdown($contributionGuide)
        );
    }

    protected function getApiEndpoints()
    {
        return [
            'codespaces' => [
                'GET /api/codespaces' => 'List all codespaces',
                'POST /api/codespaces' => 'Create a new codespace',
                'DELETE /api/codespaces/{name}' => 'Delete a codespace',
                'POST /api/codespaces/{name}/rebuild' => 'Rebuild a codespace',
                'GET /api/codespaces/{name}/status' => 'Get codespace status',
                'POST /api/codespaces/{name}/connect' => 'Connect to a codespace',
            ],
            'developer-credentials' => [
                'GET /api/developer-credentials' => 'List developer credentials',
                'POST /api/developer-credentials' => 'Create developer credential',
                'PUT /api/developer-credentials/{id}' => 'Update developer credential',
                'DELETE /api/developer-credentials/{id}' => 'Delete developer credential',
                'POST /api/developer-credentials/{id}/activate' => 'Activate credential',
                'POST /api/developer-credentials/{id}/deactivate' => 'Deactivate credential',
            ],
        ];
    }

    protected function getAuthenticationDocs()
    {
        return [
            'description' => 'Authentication is handled using Laravel Sanctum',
            'steps' => [
                'Obtain a personal access token',
                'Include the token in the Authorization header',
                'Format: Bearer {token}',
            ],
        ];
    }

    protected function getErrorHandlingDocs()
    {
        return [
            'description' => 'Error responses follow a consistent format',
            'format' => [
                'success' => 'boolean',
                'message' => 'string',
                'errors' => 'array (optional)',
            ],
            'status-codes' => [
                '200' => 'Success',
                '400' => 'Bad Request',
                '401' => 'Unauthorized',
                '403' => 'Forbidden',
                '404' => 'Not Found',
                '422' => 'Validation Error',
                '500' => 'Server Error',
            ],
        ];
    }

    protected function getCodespaceDocs()
    {
        return [
            'description' => 'GitHub Codespaces integration',
            'features' => [
                'Automated setup and deployment',
                'Docker container configuration',
                'Development environment management',
                'CLI interaction through Artisan commands',
            ],
            'usage' => [
                'php artisan codespace:list' => 'List all codespaces',
                'php artisan codespace:create' => 'Create a new codespace',
                'php artisan codespace:delete' => 'Delete a codespace',
                'php artisan codespace:rebuild' => 'Rebuild a codespace',
                'php artisan codespace:status' => 'Check codespace status',
                'php artisan codespace:connect' => 'Connect to a codespace',
            ],
        ];
    }

    protected function getDeveloperCredentialDocs()
    {
        return [
            'description' => 'Developer credential management',
            'features' => [
                'Secure token storage',
                'Permission management',
                'Token expiration',
                'Activity tracking',
            ],
            'security' => [
                'Token encryption',
                'Permission-based access control',
                'Automatic token rotation',
                'Audit logging',
            ],
        ];
    }

    protected function getTaskManagementDocs()
    {
        return [
            'description' => 'Task and project management',
            'features' => [
                'GitHub Issues integration',
                'Project boards',
                'Milestone tracking',
                'Automated workflows',
            ],
        ];
    }

    protected function getCodeQualityDocs()
    {
        return [
            'description' => 'Code quality and testing',
            'features' => [
                'Automated testing',
                'Code style enforcement',
                'Static analysis',
                'Coverage reporting',
            ],
            'tools' => [
                'PHPUnit' => 'Unit and feature testing',
                'PHPStan' => 'Static analysis',
                'PHPCS' => 'Code style checking',
                'Codecov' => 'Coverage reporting',
            ],
        ];
    }

    protected function getRequirements()
    {
        return [
            'PHP 8.2 or higher',
            'Composer',
            'Node.js and NPM',
            'Docker and Docker Compose',
            'GitHub account with Codespaces access',
        ];
    }

    protected function getInstallationSteps()
    {
        return [
            'Clone the repository',
            'Install PHP dependencies: composer install',
            'Install Node.js dependencies: npm install',
            'Copy .env.example to .env',
            'Generate application key: php artisan key:generate',
            'Run migrations: php artisan migrate',
            'Start the development server: php artisan serve',
        ];
    }

    protected function getConfigurationSteps()
    {
        return [
            'Configure GitHub credentials',
            'Set up Codespaces configuration',
            'Configure database connection',
            'Set up environment variables',
            'Configure mail settings',
        ];
    }

    protected function getDevelopmentSetup()
    {
        return [
            'description' => 'Development environment setup',
            'steps' => [
                'Install development tools',
                'Configure IDE settings',
                'Set up debugging environment',
                'Configure testing environment',
            ],
        ];
    }

    protected function getWorkflowSteps()
    {
        return [
            'description' => 'Development workflow',
            'steps' => [
                'Create a new branch',
                'Make changes',
                'Write tests',
                'Run tests',
                'Submit pull request',
                'Code review',
                'Merge and deploy',
            ],
        ];
    }

    protected function getCodeStyleGuide()
    {
        return [
            'description' => 'Code style and standards',
            'rules' => [
                'Follow PSR-12',
                'Use type hints',
                'Write meaningful comments',
                'Follow Laravel conventions',
            ],
        ];
    }

    protected function getTestingGuide()
    {
        return [
            'description' => 'Testing guidelines',
            'rules' => [
                'Write unit tests',
                'Write feature tests',
                'Maintain test coverage',
                'Follow testing best practices',
            ],
        ];
    }

    protected function getDocumentationGuide()
    {
        return [
            'description' => 'Documentation guidelines',
            'rules' => [
                'Keep documentation up to date',
                'Use clear and concise language',
                'Include code examples',
                'Follow markdown conventions',
            ],
        ];
    }

    protected function formatMarkdown($data)
    {
        $markdown = "# {$data['title']}\n\n";
        $markdown .= "{$data['description']}\n\n";

        foreach ($data as $key => $value) {
            if (in_array($key, ['title', 'description'])) {
                continue;
            }

            $markdown .= "## " . Str::title(str_replace('-', ' ', $key)) . "\n\n";

            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_array($subValue)) {
                        $markdown .= "### " . Str::title(str_replace('-', ' ', $subKey)) . "\n\n";
                        foreach ($subValue as $itemKey => $itemValue) {
                            if (is_array($itemValue)) {
                                $markdown .= "#### " . Str::title(str_replace('-', ' ', $itemKey)) . "\n\n";
                                foreach ($itemValue as $k => $v) {
                                    $markdown .= "- **{$k}**: {$v}\n";
                                }
                            } else {
                                $markdown .= "- **{$itemKey}**: {$itemValue}\n";
                            }
                        }
                    } else {
                        $markdown .= "- **{$subKey}**: {$subValue}\n";
                    }
                    $markdown .= "\n";
                }
            } else {
                $markdown .= "{$value}\n\n";
            }
        }

        return $markdown;
    }
} 