<?php return array (
  'app' => 
  array (
    'name' => 'ServiceLearningManagement',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => 'base64:awWvIVhjGl6lJXzcZuHmx8U5VT1vs/bRlajzi257Vus=',
    'cipher' => 'AES-256-CBC',
    'log' => 'single',
    'log_level' => 'debug',
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Cookie\\CookieServiceProvider',
      6 => 'Illuminate\\Database\\DatabaseServiceProvider',
      7 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      8 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      9 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      10 => 'Illuminate\\Hashing\\HashServiceProvider',
      11 => 'Illuminate\\Mail\\MailServiceProvider',
      12 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      13 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      14 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      15 => 'Illuminate\\Queue\\QueueServiceProvider',
      16 => 'Illuminate\\Redis\\RedisServiceProvider',
      17 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      18 => 'Illuminate\\Session\\SessionServiceProvider',
      19 => 'Illuminate\\Translation\\TranslationServiceProvider',
      20 => 'Illuminate\\Validation\\ValidationServiceProvider',
      21 => 'Illuminate\\View\\ViewServiceProvider',
      22 => 'App\\Providers\\AppServiceProvider',
      23 => 'App\\Providers\\AuthServiceProvider',
      24 => 'App\\Providers\\BroadcastServiceProvider',
      25 => 'App\\Providers\\EventServiceProvider',
      26 => 'App\\Providers\\RouteServiceProvider',
      27 => 'App\\Providers\\UnifiedServiceProvider',
      28 => 'App\\Providers\\SniffingServiceProvider',
      29 => 'App\\Providers\\CodespacesServiceProvider',
      30 => 'App\\Providers\\ConfigServiceProvider',
      31 => 'App\\Providers\\DatabaseServiceProvider',
      32 => 'App\\Providers\\ModelServiceProvider',
      33 => 'App\\Providers\\CommandServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Redis' => 'Illuminate\\Support\\Facades\\Redis',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
    ),
  ),
  'cache' => 
  array (
    'default' => 'file',
    'stores' => 
    array (
      'apc' => 
      array (
        'driver' => 'apc',
      ),
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'database' => 
      array (
        'driver' => 'database',
        'table' => 'cache',
        'connection' => NULL,
        'lock_connection' => NULL,
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\framework/cache/data',
        'lock_path' => 'C:\\laragon\\www\\service_learning_management\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'persistent_id' => NULL,
        'sasl' => 
        array (
          0 => NULL,
          1 => NULL,
        ),
        'options' => 
        array (
        ),
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => NULL,
        'secret' => NULL,
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
    ),
    'prefix' => 'servicelearningmanagement_cache',
  ),
  'codespaces' => 
  array (
    'enabled' => false,
    'github_token' => NULL,
    'repository' => NULL,
    'health_check_interval' => 300,
    'max_codespaces' => 10,
    'timeout' => 300,
    'webhook_secret' => NULL,
    'default_environment' => 'development',
    'default_size' => 'Standard-2x4',
    'retry_attempts' => 3,
    'services' => 
    array (
      'database' => 
      array (
        'enabled' => true,
        'type' => 'mysql',
        'version' => '8.0',
      ),
      'cache' => 
      array (
        'enabled' => true,
        'type' => 'redis',
        'version' => '6.0',
      ),
      'queue' => 
      array (
        'enabled' => true,
        'type' => 'redis',
      ),
    ),
    'health_checks' => 
    array (
      'enabled' => true,
      'interval' => 60,
      'timeout' => 30,
    ),
    'logging' => 
    array (
      'enabled' => true,
      'level' => 'info',
      'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/codespaces.log',
    ),
    'testing' => 
    array (
      'enabled' => true,
      'services' => 
      array (
        'database' => 
        array (
          'host' => 'mysql',
          'port' => 3306,
          'database' => 'service_learning_test',
          'username' => 'root',
          'password' => 'root',
        ),
        'redis' => 
        array (
          'host' => 'redis',
          'port' => 6379,
          'password' => NULL,
        ),
        'mail' => 
        array (
          'host' => 'mailhog',
          'port' => 1025,
          'username' => NULL,
          'password' => NULL,
          'encryption' => NULL,
        ),
      ),
      'logging' => 
      array (
        'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/codespaces/testing',
        'level' => 'debug',
      ),
      'testing' => 
      array (
        'report_path' => '.codespaces/testing/.test/results',
        'log_path' => '.codespaces/log',
        'complete_path' => '.codespaces/testing/.test/.complete',
        'failures_path' => '.codespaces/testing/.test/.failures',
      ),
    ),
  ),
  'database' => 
  array (
    'default' => 'sqlite',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'C:\\laragon\\www\\service_learning_management\\storage\\database/sniffing.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
      'testing' => 
      array (
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_keys' => true,
      ),
      'soc2_sqlite' => 
      array (
        'driver' => 'sqlite',
        'database' => 'C:\\laragon\\www\\service_learning_management\\storage\\.soc2/database/soc2.sqlite',
        'prefix' => '',
        'foreign_key_constraints' => true,
      ),
    ),
    'migrations' => 'migrations',
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => 'redis',
        'prefix' => '',
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
      ),
    ),
  ),
  'docker' => 
  array (
    'enabled' => false,
    'host' => 'unix:///var/run/docker.sock',
    'api_version' => '1.41',
    'timeout' => 30,
    'containers' => 
    array (
      'prefix' => 'slm_',
      'network' => 'slm_network',
    ),
    'volumes' => 
    array (
      'prefix' => 'slm_',
      'base_path' => 'C:\\laragon\\www\\service_learning_management\\storage\\docker/volumes',
    ),
    'images' => 
    array (
      'base' => 'php:8.2-fpm',
      'nginx' => 'nginx:alpine',
      'mysql' => 'mysql:8.0',
      'redis' => 'redis:alpine',
    ),
    'ports' => 
    array (
      'web' => 8080,
      'mysql' => 3306,
      'redis' => 6379,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\service_learning_management\\storage\\app',
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\service_learning_management\\storage\\app/public',
        'url' => 'http://localhost/storage',
        'visibility' => 'public',
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => NULL,
        'secret' => NULL,
        'region' => NULL,
        'bucket' => NULL,
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
      ),
      'soc2' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\laragon\\www\\service_learning_management\\storage\\.soc2/storage',
        'visibility' => 'private',
      ),
    ),
    'links' => 
    array (
      'C:\\laragon\\www\\service_learning_management\\public\\storage' => 'C:\\laragon\\www\\service_learning_management\\storage\\app/public',
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/laravel.log',
        'level' => 'debug',
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'debug',
      ),
      'codespaces' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/codespaces.log',
        'level' => 'debug',
      ),
    ),
  ),
  'mcp' => 
  array (
    'database' => 
    array (
      'driver' => 'mysql',
      'host' => 'localhost',
      'database' => 'service_learning',
      'username' => 'root',
      'password' => '',
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
    ),
    'logging' => 
    array (
      'name' => 'mcp-framework',
      'path' => 'C:\\laragon\\www\\service_learning_management\\config/../logs/mcp.log',
      'level' => 100,
    ),
    'testing' => 
    array (
      'database' => 
      array (
        'driver' => 'sqlite',
        'database' => ':memory:',
      ),
      'logging' => 
      array (
        'path' => 'C:\\laragon\\www\\service_learning_management\\config/../logs/test.log',
        'level' => 200,
      ),
    ),
    'rollback' => 
    array (
      'enabled' => true,
      'triggers' => 
      array (
        'health_check_failure' => 
        array (
          'enabled' => true,
          'threshold' => 3,
          'interval' => 60,
        ),
        'error_rate_threshold' => 
        array (
          'enabled' => true,
          'threshold' => 5,
          'window' => 300,
        ),
        'response_time_threshold' => 
        array (
          'enabled' => true,
          'threshold' => 2000,
          'window' => 60,
        ),
        'manual_trigger' => 
        array (
          'enabled' => true,
          'require_confirmation' => true,
          'allowed_roles' => 
          array (
            0 => 'admin',
            1 => 'super_admin',
          ),
        ),
      ),
      'procedures' => 
      array (
        'database' => 
        array (
          'enabled' => true,
          'backup_before_rollback' => true,
          'verify_after_rollback' => true,
          'max_retries' => 3,
          'timeout' => 300,
        ),
        'files' => 
        array (
          'enabled' => true,
          'backup_before_rollback' => true,
          'verify_after_rollback' => true,
          'exclude_patterns' => 
          array (
            0 => '*.log',
            1 => '*.cache',
            2 => '*.tmp',
          ),
        ),
        'configuration' => 
        array (
          'enabled' => true,
          'backup_before_rollback' => true,
          'verify_after_rollback' => true,
          'include_env' => true,
        ),
        'dependencies' => 
        array (
          'enabled' => true,
          'backup_before_rollback' => true,
          'verify_after_rollback' => true,
          'composer_lock' => true,
          'package_json' => true,
        ),
      ),
      'notifications' => 
      array (
        'channels' => 
        array (
          'email' => 
          array (
            'enabled' => true,
            'template' => 'rollback.notification',
          ),
          'slack' => 
          array (
            'enabled' => true,
            'template' => 'rollback.slack',
          ),
          'pagerduty' => 
          array (
            'enabled' => true,
            'severity' => 'critical',
          ),
        ),
        'recipients' => 
        array (
          'developers' => 
          array (
            0 => '',
          ),
          'operations' => 
          array (
            0 => '',
          ),
          'management' => 
          array (
            0 => '',
          ),
        ),
        'templates' => 
        array (
          'rollback.notification' => 
          array (
            'subject' => 'Rollback Executed: {deployment_id}',
            'body' => 'A rollback has been executed for deployment {deployment_id}.',
          ),
          'rollback.slack' => 
          array (
            'channel' => '#deployments',
            'message' => 'Rollback executed for deployment {deployment_id}',
          ),
        ),
      ),
      'logging' => 
      array (
        'enabled' => true,
        'channel' => 'rollback',
        'level' => 'info',
        'retention' => 
        array (
          'days' => 30,
          'max_size' => '100M',
        ),
      ),
      'audit' => 
      array (
        'enabled' => true,
        'log_all_actions' => true,
        'log_all_users' => true,
        'retention' => 
        array (
          'days' => 90,
          'max_size' => '1G',
        ),
      ),
      'recovery' => 
      array (
        'enabled' => true,
        'max_attempts' => 3,
        'timeout' => 600,
        'notify_on_failure' => true,
      ),
    ),
  ),
  'modules' => 
  array (
    'enabled' => true,
    'discovery' => 
    array (
      'scan_paths' => 
      array (
        0 => 'C:\\laragon\\www\\service_learning_management\\modules',
      ),
      'exclude_patterns' => 
      array (
        0 => 'shared',
        1 => 'vendor',
        2 => 'node_modules',
        3 => '.git',
        4 => '.tmp',
      ),
      'auto_discover' => true,
    ),
    'autoload' => 
    array (
      'enabled' => true,
      'cache' => true,
      'optimize' => true,
      'psr4' => 
      array (
        'App\\Modules\\' => 'C:\\laragon\\www\\service_learning_management\\modules',
      ),
    ),
    'providers' => 
    array (
      'auto_discover' => true,
      'cache' => true,
      'base_provider' => 'App\\Providers\\BaseModuleServiceProvider',
      'discovery_service' => 'App\\Modules\\Shared\\ModuleDiscoveryService',
    ),
    'testing' => 
    array (
      'enabled' => true,
      'coverage' => true,
      'parallel' => false,
      'coverage_threshold' => 80,
    ),
    'performance' => 
    array (
      'caching' => 
      array (
        'enabled' => true,
        'driver' => 'redis',
        'ttl' => 3600,
        'prefix' => 'module_',
      ),
      'optimization' => 
      array (
        'autoload_optimization' => true,
        'route_caching' => true,
        'config_caching' => true,
        'view_caching' => true,
      ),
      'monitoring' => 
      array (
        'enabled' => true,
        'metrics' => true,
        'health_checks' => true,
      ),
    ),
    'security' => 
    array (
      'encryption' => 
      array (
        'default' => 'AES-256-GCM',
        'key_length' => 32,
        'algorithm' => 'AES-256-GCM',
      ),
      'audit' => 
      array (
        'enabled' => true,
        'log_level' => 'info',
        'retention_days' => 90,
      ),
      'validation' => 
      array (
        'strict_mode' => true,
        'sanitization' => true,
      ),
    ),
    'modules' => 
    array (
      'e2ee' => 
      array (
        'enabled' => true,
        'encryption_algorithm' => 'AES-256-GCM',
        'key_rotation_days' => 30,
        'audit_enabled' => true,
        'key_management' => 
        array (
          'auto_rotation' => true,
          'backup_enabled' => true,
          'storage_driver' => 'file',
        ),
        'performance' => 
        array (
          'caching_enabled' => true,
          'batch_size' => 100,
        ),
      ),
      'soc2' => 
      array (
        'enabled' => true,
        'compliance_level' => 'type2',
        'audit_enabled' => true,
        'reporting_enabled' => true,
        'controls' => 
        array (
          'access_control' => true,
          'data_protection' => true,
          'incident_response' => true,
        ),
        'reporting' => 
        array (
          'auto_generate' => true,
          'retention_period' => 365,
          'export_formats' => 
          array (
            0 => 'pdf',
            1 => 'csv',
            2 => 'json',
          ),
        ),
      ),
      'mcp' => 
      array (
        'enabled' => true,
        'agent_enabled' => true,
        'api_enabled' => true,
        'performance' => 
        array (
          'max_concurrent_agents' => 10,
          'timeout_seconds' => 30,
          'memory_limit' => '512M',
        ),
        'security' => 
        array (
          'agent_authentication' => true,
          'api_rate_limiting' => true,
        ),
      ),
      'web3' => 
      array (
        'enabled' => true,
        'network' => 'ethereum',
        'contract_enabled' => true,
        'providers' => 
        array (
          'ethereum' => 'https://mainnet.infura.io/v3/',
          'polygon' => 'https://polygon-rpc.com/',
        ),
        'security' => 
        array (
          'private_key_encryption' => true,
          'transaction_signing' => true,
        ),
      ),
      'auth' => 
      array (
        'enabled' => true,
        'rbac_enabled' => true,
        'audit_enabled' => true,
        'roles' => 
        array (
          'super_admin' => 'super_admin',
          'admin' => 'admin',
          'user' => 'user',
        ),
        'permissions' => 
        array (
          'module_specific' => true,
          'dynamic_permissions' => true,
        ),
      ),
      'api' => 
      array (
        'enabled' => true,
        'versioning_enabled' => true,
        'rate_limiting_enabled' => true,
        'versions' => 
        array (
          'current' => 'v1',
          'supported' => 
          array (
            0 => 'v1',
            1 => 'v2',
          ),
          'deprecated' => 
          array (
          ),
        ),
        'rate_limiting' => 
        array (
          'requests_per_minute' => 60,
          'burst_limit' => 100,
        ),
        'documentation' => 
        array (
          'auto_generate' => true,
          'swagger_enabled' => true,
        ),
      ),
      'shared' => 
      array (
        'enabled' => true,
        'utilities' => 
        array (
          'logging' => true,
          'caching' => true,
          'validation' => true,
        ),
      ),
    ),
    'dependencies' => 
    array (
      'e2ee' => 
      array (
        0 => 'shared',
      ),
      'soc2' => 
      array (
        0 => 'shared',
        1 => 'e2ee',
      ),
      'mcp' => 
      array (
        0 => 'shared',
      ),
      'web3' => 
      array (
        0 => 'shared',
      ),
      'auth' => 
      array (
        0 => 'shared',
      ),
      'api' => 
      array (
        0 => 'shared',
        1 => 'auth',
      ),
    ),
    'logging' => 
    array (
      'enabled' => true,
      'channels' => 
      array (
        'module_activity' => 
        array (
          'driver' => 'daily',
          'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/modules/activity.log',
          'level' => 'info',
          'days' => 30,
        ),
        'module_errors' => 
        array (
          'driver' => 'daily',
          'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/modules/errors.log',
          'level' => 'error',
          'days' => 90,
        ),
      ),
    ),
    'maintenance' => 
    array (
      'auto_cleanup' => 
      array (
        'enabled' => true,
        'schedule' => 'daily',
        'retention_days' => 30,
      ),
      'health_checks' => 
      array (
        'enabled' => true,
        'interval' => 300,
        'timeout' => 30,
      ),
    ),
    'api' => 
    array (
      'enabled' => true,
    ),
    'auth' => 
    array (
      'authentication' => 
      array (
        'password' => 
        array (
          'min_length' => 8,
          'require_uppercase' => true,
          'require_lowercase' => true,
          'require_numbers' => true,
          'require_special_chars' => true,
          'max_age_days' => 90,
          'history_count' => 5,
        ),
        'session' => 
        array (
          'lifetime' => 120,
          'inactivity_timeout' => 30,
          'concurrent_sessions' => 3,
          'regenerate_on_login' => true,
        ),
        'login' => 
        array (
          'max_attempts' => 5,
          'lockout_duration' => 15,
          'lockout_threshold' => 3,
          'require_captcha' => false,
          'remember_me_days' => 30,
        ),
        '2fa' => 
        array (
          'enabled' => true,
          'methods' => 
          array (
            0 => 'totp',
            1 => 'sms',
            2 => 'email',
          ),
          'backup_codes_count' => 10,
          'grace_period_days' => 7,
        ),
      ),
      'authorization' => 
      array (
        'default_roles' => 
        array (
          'super-admin' => 
          array (
            'name' => 'Super Administrator',
            'description' => 'Full system access with all permissions',
            'permissions' => 
            array (
              0 => '*',
            ),
          ),
          'admin' => 
          array (
            'name' => 'Administrator',
            'description' => 'System administrator with most permissions',
            'permissions' => 
            array (
              0 => 'users.manage',
              1 => 'roles.manage',
              2 => 'permissions.manage',
              3 => 'system.settings',
              4 => 'reports.view',
              5 => 'audit.view',
            ),
          ),
          'manager' => 
          array (
            'name' => 'Manager',
            'description' => 'Department or team manager',
            'permissions' => 
            array (
              0 => 'users.view',
              1 => 'reports.view',
              2 => 'projects.manage',
              3 => 'teams.manage',
            ),
          ),
          'user' => 
          array (
            'name' => 'User',
            'description' => 'Standard user with basic permissions',
            'permissions' => 
            array (
              0 => 'profile.view',
              1 => 'profile.edit',
              2 => 'projects.view',
              3 => 'projects.participate',
            ),
          ),
          'guest' => 
          array (
            'name' => 'Guest',
            'description' => 'Limited access user',
            'permissions' => 
            array (
              0 => 'public.content.view',
            ),
          ),
        ),
        'default_permissions' => 
        array (
          'users.view' => 'View user profiles',
          'users.create' => 'Create new users',
          'users.edit' => 'Edit user profiles',
          'users.delete' => 'Delete users',
          'users.manage' => 'Manage all users',
          'roles.view' => 'View roles',
          'roles.create' => 'Create new roles',
          'roles.edit' => 'Edit roles',
          'roles.delete' => 'Delete roles',
          'roles.manage' => 'Manage all roles',
          'permissions.view' => 'View permissions',
          'permissions.create' => 'Create new permissions',
          'permissions.edit' => 'Edit permissions',
          'permissions.delete' => 'Delete permissions',
          'permissions.manage' => 'Manage all permissions',
          'system.settings' => 'Manage system settings',
          'system.maintenance' => 'Access maintenance mode',
          'system.backup' => 'Create system backups',
          'content.view' => 'View content',
          'content.create' => 'Create content',
          'content.edit' => 'Edit content',
          'content.delete' => 'Delete content',
          'content.publish' => 'Publish content',
          'projects.view' => 'View projects',
          'projects.create' => 'Create projects',
          'projects.edit' => 'Edit projects',
          'projects.delete' => 'Delete projects',
          'projects.manage' => 'Manage all projects',
          'projects.participate' => 'Participate in projects',
          'teams.view' => 'View teams',
          'teams.create' => 'Create teams',
          'teams.edit' => 'Edit teams',
          'teams.delete' => 'Delete teams',
          'teams.manage' => 'Manage all teams',
          'reports.view' => 'View reports',
          'reports.create' => 'Create reports',
          'reports.export' => 'Export reports',
          'audit.view' => 'View audit logs',
          'audit.export' => 'Export audit logs',
          'compliance.view' => 'View compliance reports',
          'compliance.manage' => 'Manage compliance',
          'profile.view' => 'View own profile',
          'profile.edit' => 'Edit own profile',
          'profile.delete' => 'Delete own profile',
          'public.content.view' => 'View public content',
        ),
        'cache' => 
        array (
          'enabled' => true,
          'ttl' => 3600,
          'prefix' => 'auth_',
          'tags' => 
          array (
            0 => 'auth',
            1 => 'roles',
            2 => 'permissions',
          ),
        ),
        'inheritance' => 
        array (
          'enabled' => true,
          'max_depth' => 5,
        ),
      ),
      'security' => 
      array (
        'audit' => 
        array (
          'enabled' => true,
          'log_level' => 'info',
          'events' => 
          array (
            0 => 'login',
            1 => 'logout',
            2 => 'failed_login',
            3 => 'password_change',
            4 => 'role_assignment',
            5 => 'permission_assignment',
            6 => 'profile_update',
            7 => 'account_lockout',
          ),
          'retention_days' => 365,
        ),
        'ip_restrictions' => 
        array (
          'enabled' => false,
          'whitelist' => 
          array (
          ),
          'blacklist' => 
          array (
          ),
          'admin_override' => true,
        ),
        'headers' => 
        array (
          'x_frame_options' => 'DENY',
          'x_content_type_options' => 'nosniff',
          'x_xss_protection' => '1; mode=block',
          'referrer_policy' => 'strict-origin-when-cross-origin',
        ),
        'lockout' => 
        array (
          'enabled' => true,
          'max_attempts' => 5,
          'duration' => 15,
          'permanent_after' => 10,
        ),
      ),
      'notifications' => 
      array (
        'email' => 
        array (
          'enabled' => true,
          'from_address' => 'noreply@example.com',
          'from_name' => 'System Administrator',
          'templates' => 
          array (
            'welcome' => 'auth::emails.welcome',
            'password_reset' => 'auth::emails.password-reset',
            'account_locked' => 'auth::emails.account-locked',
            'login_alert' => 'auth::emails.login-alert',
            'role_assigned' => 'auth::emails.role-assigned',
            'permission_granted' => 'auth::emails.permission-granted',
          ),
        ),
        'sms' => 
        array (
          'enabled' => false,
          'provider' => 'twilio',
          'templates' => 
          array (
            '2fa_code' => 'Your 2FA code is: {code}',
            'login_alert' => 'Login detected from {location}',
          ),
        ),
        'push' => 
        array (
          'enabled' => false,
          'provider' => 'firebase',
        ),
      ),
      'integrations' => 
      array (
        'ldap' => 
        array (
          'enabled' => false,
          'host' => NULL,
          'port' => 389,
          'base_dn' => NULL,
          'username' => NULL,
          'password' => NULL,
          'ssl' => false,
          'tls' => false,
        ),
        'oauth' => 
        array (
          'enabled' => false,
          'providers' => 
          array (
            'google' => 
            array (
              'enabled' => false,
              'client_id' => NULL,
              'client_secret' => NULL,
              'redirect_uri' => NULL,
            ),
            'github' => 
            array (
              'enabled' => false,
              'client_id' => NULL,
              'client_secret' => NULL,
              'redirect_uri' => NULL,
            ),
            'microsoft' => 
            array (
              'enabled' => false,
              'client_id' => NULL,
              'client_secret' => NULL,
              'redirect_uri' => NULL,
            ),
          ),
        ),
        'saml' => 
        array (
          'enabled' => false,
          'idp_entity_id' => NULL,
          'idp_sso_url' => NULL,
          'idp_x509_cert' => NULL,
          'sp_entity_id' => NULL,
          'sp_acs_url' => NULL,
        ),
      ),
      'performance' => 
      array (
        'database' => 
        array (
          'eager_loading' => true,
          'query_cache' => true,
          'batch_size' => 1000,
        ),
        'memory' => 
        array (
          'cache_roles' => true,
          'cache_permissions' => true,
          'cache_user_roles' => true,
          'cache_user_permissions' => true,
        ),
        'limits' => 
        array (
          'max_roles_per_user' => 10,
          'max_permissions_per_role' => 100,
          'max_users_per_page' => 50,
          'max_roles_per_page' => 50,
        ),
      ),
      'development' => 
      array (
        'debug' => false,
        'log_queries' => false,
        'log_performance' => false,
        'test_mode' => false,
      ),
    ),
  ),
  'production' => 
  array (
    'mcp' => 
    array (
      'app' => 
      array (
        'env' => 'production',
        'debug' => false,
        'mcp' => 
        array (
          'enabled' => false,
          'environment' => 'production',
        ),
      ),
      'database' => 
      array (
        'production' => 
        array (
          'driver' => 'mysql',
          'host' => 'production-db',
          'port' => '3306',
          'database' => 'database/database.sqlite',
          'username' => 'mcp_production_user',
          'password' => '',
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
          'strict' => true,
          'engine' => NULL,
          'options' => 
          array (
            20 => false,
            3 => 2,
            19 => 2,
            1009 => NULL,
            1014 => true,
          ),
        ),
      ),
      'logging' => 
      array (
        'channel' => 'production',
        'production_enabled' => true,
        'channels' => 
        array (
          'production' => 
          array (
            'driver' => 'stack',
            'channels' => 
            array (
              0 => 'daily',
              1 => 'slack',
              2 => 'papertrail',
            ),
            'ignore_exceptions' => false,
          ),
          'daily' => 
          array (
            'driver' => 'daily',
            'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/production.log',
            'level' => 'error',
            'days' => 30,
          ),
          'slack' => 
          array (
            'driver' => 'slack',
            'url' => NULL,
            'username' => 'MCP Production Logger',
            'emoji' => ':warning:',
            'level' => 'critical',
          ),
          'papertrail' => 
          array (
            'driver' => 'syslog',
            'level' => 'error',
            'app_name' => 'mcp-production',
            'facility' => 8,
          ),
        ),
      ),
      'monitoring' => 
      array (
        'enabled' => true,
        'endpoints' => 
        array (
          'health' => '/health',
          'metrics' => '/metrics',
          'status' => '/status',
        ),
        'alerting_enabled' => true,
        'alert_channels' => 
        array (
          'email' => 
          array (
            'enabled' => true,
            'recipients' => 
            array (
              0 => '',
            ),
          ),
          'slack' => 
          array (
            'enabled' => true,
            'webhook_url' => NULL,
          ),
          'pagerduty' => 
          array (
            'enabled' => true,
            'service_key' => NULL,
          ),
        ),
        'metrics' => 
        array (
          'enabled' => true,
          'driver' => 'prometheus',
          'namespace' => 'mcp_production',
          'labels' => 
          array (
            'environment' => 'production',
            'application' => 'mcp',
          ),
        ),
      ),
      'backup' => 
      array (
        'enabled' => true,
        'schedule' => '0 0 * * *',
        'retention_policy' => 
        array (
          'daily' => 14,
          'weekly' => 8,
          'monthly' => 12,
          'yearly' => 3,
        ),
        'storage' => 
        array (
          'driver' => 's3',
          'bucket' => NULL,
          'path' => 'production/backups',
          'encryption' => 'AES256',
        ),
        'verification' => 
        array (
          'enabled' => true,
          'schedule' => '0 6 * * *',
          'notify_on_failure' => true,
        ),
      ),
      'security' => 
      array (
        'production_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => true,
        'ssl_required' => true,
        'headers_enabled' => true,
        'headers' => 
        array (
          'X-Frame-Options' => 'DENY',
          'X-XSS-Protection' => '1; mode=block',
          'X-Content-Type-Options' => 'nosniff',
          'Referrer-Policy' => 'strict-origin-when-cross-origin',
          'Content-Security-Policy' => 'default-src \'self\'; script-src \'self\'; style-src \'self\'; img-src \'self\' data:; font-src \'self\'; connect-src \'self\';',
          'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
          'X-Permitted-Cross-Domain-Policies' => 'none',
          'X-Download-Options' => 'noopen',
          'X-DNS-Prefetch-Control' => 'off',
        ),
        'session' => 
        array (
          'secure' => true,
          'httponly' => true,
          'samesite' => 'strict',
          'lifetime' => 120,
          'expire_on_close' => true,
        ),
        'cookies' => 
        array (
          'secure' => true,
          'httponly' => true,
          'samesite' => 'strict',
        ),
      ),
      'cache' => 
      array (
        'driver' => 'redis',
        'connection' => 'production',
        'prefix' => 'mcp_production:',
        'ttl' => 3600,
        'tags' => true,
      ),
      'queue' => 
      array (
        'driver' => 'redis',
        'connection' => 'production',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => true,
      ),
    ),
  ),
  'queue' => 1,
  'staging' => 
  array (
    'mcp' => 
    array (
      'app' => 
      array (
        'env' => 'staging',
        'debug' => false,
        'mcp' => 
        array (
          'enabled' => true,
          'environment' => 'staging',
        ),
      ),
      'database' => 
      array (
        'staging' => 
        array (
          'driver' => 'mysql',
          'host' => 'staging-db',
          'port' => '3306',
          'database' => 'database/database.sqlite',
          'username' => 'mcp_staging_user',
          'password' => '',
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
          'strict' => true,
          'engine' => NULL,
        ),
      ),
      'logging' => 
      array (
        'channel' => 'staging',
        'staging_enabled' => true,
        'channels' => 
        array (
          'staging' => 
          array (
            'driver' => 'stack',
            'channels' => 
            array (
              0 => 'daily',
              1 => 'slack',
            ),
            'ignore_exceptions' => false,
          ),
          'daily' => 
          array (
            'driver' => 'daily',
            'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/staging.log',
            'level' => 'debug',
            'days' => 14,
          ),
          'slack' => 
          array (
            'driver' => 'slack',
            'url' => NULL,
            'username' => 'MCP Staging Logger',
            'emoji' => ':boom:',
            'level' => 'critical',
          ),
        ),
      ),
      'monitoring' => 
      array (
        'enabled' => true,
        'endpoints' => 
        array (
          'health' => '/health',
          'metrics' => '/metrics',
          'status' => '/status',
        ),
        'alerting_enabled' => true,
        'alert_channels' => 
        array (
          'email' => 
          array (
            'enabled' => true,
            'recipients' => 
            array (
              0 => '',
            ),
          ),
          'slack' => 
          array (
            'enabled' => true,
            'webhook_url' => NULL,
          ),
        ),
      ),
      'backup' => 
      array (
        'enabled' => true,
        'schedule' => '0 0 * * *',
        'retention_policy' => 
        array (
          'daily' => 7,
          'weekly' => 4,
          'monthly' => 3,
        ),
        'storage' => 
        array (
          'driver' => 's3',
          'bucket' => NULL,
          'path' => 'staging/backups',
        ),
      ),
      'security' => 
      array (
        'staging_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => true,
        'ssl_required' => true,
        'headers_enabled' => true,
        'headers' => 
        array (
          'X-Frame-Options' => 'SAMEORIGIN',
          'X-XSS-Protection' => '1; mode=block',
          'X-Content-Type-Options' => 'nosniff',
          'Referrer-Policy' => 'strict-origin-when-cross-origin',
          'Content-Security-Policy' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';',
        ),
      ),
    ),
  ),
  'test' => 
  array (
    'mcp' => 
    array (
      'app' => 
      array (
        'env' => 'test',
        'debug' => true,
        'mcp' => 
        array (
          'enabled' => true,
          'environment' => 'test',
        ),
      ),
      'database' => 
      array (
        'test' => 
        array (
          'driver' => 'sqlite',
          'database' => ':memory:',
          'prefix' => '',
        ),
      ),
      'logging' => 
      array (
        'channel' => 'test',
        'test_enabled' => true,
        'channels' => 
        array (
          'test' => 
          array (
            'driver' => 'daily',
            'path' => 'C:\\laragon\\www\\service_learning_management\\storage\\logs/test.log',
            'level' => 'debug',
            'days' => 7,
          ),
        ),
      ),
      'security' => 
      array (
        'test_checks_enabled' => true,
        'audit_logging_enabled' => true,
        'rate_limiting_enabled' => false,
        'ssl_required' => false,
      ),
    ),
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\laragon\\www\\service_learning_management\\resources\\views',
      1 => 'C:\\laragon\\www\\service_learning_management\\resources/views',
    ),
    'compiled' => 'C:\\laragon\\www\\service_learning_management\\storage\\framework\\views',
  ),
  'soc2' => 
  array (
    'enabled' => true,
    'validation' => 
    array (
      'thresholds' => 
      array (
        'overall_compliance' => 80,
        'security_score' => 85,
        'availability_score' => 99.5,
        'processing_integrity_score' => 95,
        'confidentiality_score' => 90,
        'privacy_score' => 85,
        'control_compliance' => 80,
      ),
      'rules' => 
      array (
        'require_audit_logging' => true,
        'require_risk_assessment' => true,
        'require_control_assessment' => true,
        'require_compliance_report' => true,
      ),
    ),
    'audit' => 
    array (
      'enabled' => true,
      'retention_period' => 2555,
      'log_level' => 'info',
      'compliance_relevant_actions' => 
      array (
        0 => 'certification_created',
        1 => 'certification_updated',
        2 => 'certification_deleted',
        3 => 'control_assessment_created',
        4 => 'control_assessment_updated',
        5 => 'risk_assessment_created',
        6 => 'risk_assessment_updated',
        7 => 'compliance_report_created',
        8 => 'compliance_report_approved',
        9 => 'compliance_report_rejected',
        10 => 'data_exported',
        11 => 'data_deleted',
        12 => 'user_access_granted',
        13 => 'user_access_revoked',
      ),
      'sensitive_data_fields' => 
      array (
        0 => 'password',
        1 => 'token',
        2 => 'secret',
        3 => 'key',
        4 => 'credential',
        5 => 'ssn',
        6 => 'credit_card',
        7 => 'bank_account',
      ),
    ),
    'trust_service_criteria' => 
    array (
      'Security' => 
      array (
        'description' => 'Information and systems are protected against unauthorized access',
        'controls' => 
        array (
          0 => 'access_control',
          1 => 'authentication',
          2 => 'authorization',
          3 => 'encryption',
          4 => 'network_security',
          5 => 'physical_security',
        ),
      ),
      'Availability' => 
      array (
        'description' => 'Information and systems are available for operation and use',
        'controls' => 
        array (
          0 => 'backup_recovery',
          1 => 'disaster_recovery',
          2 => 'system_monitoring',
          3 => 'capacity_planning',
          4 => 'incident_response',
        ),
      ),
      'Processing Integrity' => 
      array (
        'description' => 'System processing is complete, accurate, timely, and authorized',
        'controls' => 
        array (
          0 => 'data_validation',
          1 => 'error_handling',
          2 => 'processing_monitoring',
          3 => 'change_management',
          4 => 'quality_assurance',
        ),
      ),
      'Confidentiality' => 
      array (
        'description' => 'Information designated as confidential is protected',
        'controls' => 
        array (
          0 => 'data_classification',
          1 => 'encryption',
          2 => 'access_controls',
          3 => 'data_handling',
          4 => 'disposal_procedures',
        ),
      ),
      'Privacy' => 
      array (
        'description' => 'Personal information is collected, used, retained, and disclosed in conformity with commitments',
        'controls' => 
        array (
          0 => 'consent_management',
          1 => 'data_minimization',
          2 => 'purpose_limitation',
          3 => 'data_subject_rights',
          4 => 'privacy_notices',
        ),
      ),
    ),
    'risk_assessment' => 
    array (
      'likelihood_scale' => 
      array (
        1 => 'Very Low',
        2 => 'Low',
        3 => 'Medium',
        4 => 'High',
        5 => 'Very High',
      ),
      'impact_scale' => 
      array (
        1 => 'Very Low',
        2 => 'Low',
        3 => 'Medium',
        4 => 'High',
        5 => 'Very High',
      ),
      'risk_levels' => 
      array (
        'low' => 
        array (
          'min_score' => 1,
          'max_score' => 5,
        ),
        'medium' => 
        array (
          'min_score' => 6,
          'max_score' => 9,
        ),
        'high' => 
        array (
          'min_score' => 10,
          'max_score' => 14,
        ),
        'critical' => 
        array (
          'min_score' => 15,
          'max_score' => 25,
        ),
      ),
      'review_frequency' => 
      array (
        'low' => 365,
        'medium' => 180,
        'high' => 90,
        'critical' => 30,
      ),
    ),
    'control_assessment' => 
    array (
      'statuses' => 
      array (
        'compliant' => 'Control is fully implemented and effective',
        'non_compliant' => 'Control is not implemented or ineffective',
        'partially_compliant' => 'Control is partially implemented',
        'not_applicable' => 'Control does not apply to this environment',
      ),
      'remediation_statuses' => 
      array (
        'not_started' => 'Remediation has not been initiated',
        'in_progress' => 'Remediation is currently being implemented',
        'completed' => 'Remediation has been successfully completed',
        'overdue' => 'Remediation is past the deadline',
      ),
      'evidence_requirements' => 
      array (
        'documentation' => true,
        'screenshots' => true,
        'logs' => true,
        'interviews' => true,
        'testing' => true,
      ),
    ),
    'reporting' => 
    array (
      'report_types' => 
      array (
        'initial' => 'Initial compliance assessment',
        'periodic' => 'Periodic compliance review',
        'final' => 'Final compliance report',
        'exception' => 'Exception or incident report',
      ),
      'statuses' => 
      array (
        'draft' => 'Report is in draft status',
        'in_review' => 'Report is under review',
        'approved' => 'Report has been approved',
        'rejected' => 'Report has been rejected',
        'published' => 'Report has been published',
      ),
      'retention_period' => 2555,
      'auto_approval' => false,
      'require_approval' => true,
    ),
    'notifications' => 
    array (
      'enabled' => true,
      'channels' => 
      array (
        'email' => true,
        'slack' => false,
        'webhook' => false,
      ),
      'events' => 
      array (
        'certification_expiring' => 
        array (
          'enabled' => true,
          'days_before' => 30,
        ),
        'control_assessment_overdue' => 
        array (
          'enabled' => true,
          'days_after_deadline' => 7,
        ),
        'risk_assessment_overdue' => 
        array (
          'enabled' => true,
          'days_after_deadline' => 7,
        ),
        'compliance_threshold_breach' => 
        array (
          'enabled' => true,
          'threshold' => 80,
        ),
        'critical_finding' => 
        array (
          'enabled' => true,
          'immediate' => true,
        ),
      ),
    ),
    'integrations' => 
    array (
      'e2ee' => 
      array (
        'enabled' => true,
        'encrypt_audit_logs' => true,
        'encrypt_sensitive_data' => true,
      ),
      'shared' => 
      array (
        'enabled' => true,
        'use_audit_service' => true,
        'use_configuration_service' => true,
      ),
      'auth' => 
      array (
        'enabled' => true,
        'require_authentication' => true,
        'require_authorization' => true,
      ),
    ),
    'performance' => 
    array (
      'cache_enabled' => true,
      'cache_ttl' => 3600,
      'batch_processing' => true,
      'batch_size' => 100,
      'async_processing' => true,
    ),
    'security' => 
    array (
      'encryption' => 
      array (
        'enabled' => true,
        'algorithm' => 'AES-256-GCM',
      ),
      'access_control' => 
      array (
        'require_authentication' => true,
        'require_authorization' => true,
        'session_timeout' => 3600,
      ),
      'data_protection' => 
      array (
        'mask_sensitive_data' => true,
        'anonymize_logs' => false,
        'data_retention' => true,
      ),
    ),
  ),
);
