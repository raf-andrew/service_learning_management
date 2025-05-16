<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Configure test database
$dbConfig = [
    'driver' => getenv('DB_DRIVER') ?: 'sqlite',
    'database' => getenv('DB_DATABASE') ?: ':memory:',
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
    'prefix' => getenv('DB_PREFIX') ?: ''
];

// Initialize logger
$logger = new \MCP\Core\Logger\Logger('mcp_test', sys_get_temp_dir() . '/mcp_logs_test');
$logger->setLogLevel(\Psr\Log\LogLevel::DEBUG);

// Set test environment
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');
putenv('DB_CONNECTION=mysql');
putenv('DB_HOST=mysql');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=service_learning_test');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=root');
putenv('CACHE_DRIVER=array');
putenv('SESSION_DRIVER=array');
putenv('QUEUE_CONNECTION=sync');
putenv('MAIL_MAILER=array');

// Load test configuration
$config = new \MCP\Core\Config\Config(__DIR__ . '/../config/test', $logger);
$config->set('database', $dbConfig);

// Initialize test helpers
require_once __DIR__ . '/helpers/TestCase.php';
require_once __DIR__ . '/helpers/DatabaseTestCase.php';
require_once __DIR__ . '/helpers/MockFactory.php';

// Create test database
$connection = config('database.default');
$database = config("database.connections.{$connection}.database");

config(["database.connections.{$connection}.database" => 'service_learning_test']);

\DB::statement("CREATE DATABASE IF NOT EXISTS service_learning_test");
\DB::statement("USE service_learning_test");

config(["database.connections.{$connection}.database" => $database]);

// Create test storage disk
$path = storage_path('app/test');
if (!File::exists($path)) {
    File::makeDirectory($path, 0755, true);
}

config(['filesystems.disks.test' => [
    'driver' => 'local',
    'root' => $path,
]]);

// Create test cache store
config(['cache.stores.test' => [
    'driver' => 'array',
    'serialize' => false,
]]);

// Create test queue connection
config(['queue.connections.test' => [
    'driver' => 'sync',
]]);

// Create test mailer
config(['mail.mailers.test' => [
    'transport' => 'array',
]]);

// Create test session driver
config(['session.driver' => 'array']);

// Create test auth guard
config(['auth.guards.test' => [
    'driver' => 'session',
    'provider' => 'users',
]]);

// Create test auth provider
config(['auth.providers.test' => [
    'driver' => 'eloquent',
    'model' => \App\Models\User::class,
]]);

// Register test event listeners
Event::listen('*', function ($event, $payload) {
    // Log all events for testing
    Log::info('Event fired: ' . get_class($event), $payload);
});

// Register test job listeners
Queue::before(function (JobProcessing $event) {
    // Log all jobs for testing
    Log::info('Job processing: ' . get_class($event->job->resolveName()), [
        'job' => $event->job->resolveName(),
        'data' => $event->job->payload(),
    ]);
});

// Register test notification listeners
Notification::beforeSending(function ($notifiable, $notification) {
    // Log all notifications for testing
    Log::info('Notification sending: ' . get_class($notification), [
        'notifiable' => get_class($notifiable),
        'notification' => get_class($notification),
    ]);
});

// Configure remote Codespaces services
putenv('MCP_SERVICE_URL=https://codespaces.service-learning.edu');
putenv('MCP_API_KEY=' . getenv('MCP_API_KEY'));
putenv('MCP_ENVIRONMENT=production');

// Configure remote database
putenv('DB_CONNECTION=mysql');
putenv('DB_HOST=' . getenv('CODESPACES_DB_HOST'));
putenv('DB_PORT=3306');
putenv('DB_DATABASE=service_learning');
putenv('DB_USERNAME=' . getenv('CODESPACES_DB_USERNAME'));
putenv('DB_PASSWORD=' . getenv('CODESPACES_DB_PASSWORD'));

// Configure remote cache
putenv('CACHE_DRIVER=redis');
putenv('REDIS_HOST=' . getenv('CODESPACES_REDIS_HOST'));
putenv('REDIS_PASSWORD=' . getenv('CODESPACES_REDIS_PASSWORD'));
putenv('REDIS_PORT=6379');

// Configure remote queue
putenv('QUEUE_CONNECTION=redis');
putenv('QUEUE_RETRY_AFTER=90');
putenv('QUEUE_TIMEOUT=60');

// Configure remote mail
putenv('MAIL_MAILER=smtp');
putenv('MAIL_HOST=' . getenv('CODESPACES_MAIL_HOST'));
putenv('MAIL_PORT=587');
putenv('MAIL_USERNAME=' . getenv('CODESPACES_MAIL_USERNAME'));
putenv('MAIL_PASSWORD=' . getenv('CODESPACES_MAIL_PASSWORD'));
putenv('MAIL_ENCRYPTION=tls'); 