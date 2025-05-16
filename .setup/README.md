# Service Learning Management Setup

This is the setup script for the Service Learning Management System. It handles database setup, migrations, seeding, testing, and service management.

## Requirements

- PHP 8.1 or higher
- PDO extension
- JSON extension
- Composer

## Installation

1. Clone the repository:
```bash
git clone https://github.com/your-org/service-learning-management.git
cd service-learning-management
```

2. Install dependencies:
```bash
composer install
```

## Usage

### Basic Setup

Run the setup script with default options:
```bash
composer setup
```

### Advanced Options

The setup script supports various command-line options:

```bash
php setup.php [options]
```

Options:
- `--config=<file>` - Path to configuration file
- `--log=<file>` - Path to log file
- `--log-level=<level>` - Log level (debug, info, warning, error, critical)
- `--no-console` - Disable console output
- `--help` - Show help message

Example:
```bash
php setup.php --config=config/production.php --log=logs/setup.log --log-level=debug
```

### Configuration

The setup script uses a configuration file to manage various settings. By default, it looks for `config/config.php` in the project root.

Example configuration:
```php
<?php

return [
    'app' => [
        'name' => 'Service Learning Management',
        'env' => 'production',
        'debug' => false,
        'url' => 'https://example.com',
        'timezone' => 'UTC'
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'service_learning',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => ''
    ],
    // ... other configuration options
];
```

### Testing

Run tests:
```bash
composer test
```

Generate test coverage report:
```bash
composer test:coverage
```

## Directory Structure

```
.setup/
├── src/
│   ├── Utils/
│   │   ├── ConfigManager.php
│   │   ├── DatabaseManager.php
│   │   ├── Logger.php
│   │   ├── ServiceManager.php
│   │   └── TestManager.php
│   └── Setup.php
├── tests/
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── logs/
├── composer.json
├── setup.php
└── README.md
```

## Development

### Adding Migrations

Create a new migration file in `database/migrations/`:
```php
<?php

namespace Database\Migrations;

class CreateUsersTable {
    public function up(\PDO $connection): void {
        $connection->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(\PDO $connection): void {
        $connection->exec("DROP TABLE IF EXISTS users");
    }
}
```

### Adding Seeds

Create a new seed file in `database/seeds/`:
```php
<?php

namespace Database\Seeds;

class UsersTableSeeder {
    public function run(\PDO $connection): void {
        $stmt = $connection->prepare("
            INSERT INTO users (name, email, password)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            'Admin User',
            'admin@example.com',
            password_hash('secret', PASSWORD_DEFAULT)
        ]);
    }
}
```

## License

This project is licensed under the MIT License - see the LICENSE file for details. 