<?php

return [
    'enabled' => true,
    
    'triggers' => [
        'health_check_failure' => [
            'enabled' => true,
            'threshold' => 3, // Number of consecutive failures
            'interval' => 60, // Seconds between checks
        ],
        'error_rate_threshold' => [
            'enabled' => true,
            'threshold' => 5, // Percentage of errors
            'window' => 300, // 5 minutes
        ],
        'response_time_threshold' => [
            'enabled' => true,
            'threshold' => 2000, // Milliseconds
            'window' => 60, // 1 minute
        ],
        'manual_trigger' => [
            'enabled' => true,
            'require_confirmation' => true,
            'allowed_roles' => ['admin', 'super_admin'],
        ],
    ],
    
    'procedures' => [
        'database' => [
            'enabled' => true,
            'backup_before_rollback' => true,
            'verify_after_rollback' => true,
            'max_retries' => 3,
            'timeout' => 300, // 5 minutes
        ],
        'files' => [
            'enabled' => true,
            'backup_before_rollback' => true,
            'verify_after_rollback' => true,
            'exclude_patterns' => [
                '*.log',
                '*.cache',
                '*.tmp',
            ],
        ],
        'configuration' => [
            'enabled' => true,
            'backup_before_rollback' => true,
            'verify_after_rollback' => true,
            'include_env' => true,
        ],
        'dependencies' => [
            'enabled' => true,
            'backup_before_rollback' => true,
            'verify_after_rollback' => true,
            'composer_lock' => true,
            'package_json' => true,
        ],
    ],
    
    'notifications' => [
        'channels' => [
            'email' => [
                'enabled' => true,
                'template' => 'rollback.notification',
            ],
            'slack' => [
                'enabled' => true,
                'template' => 'rollback.slack',
            ],
            'pagerduty' => [
                'enabled' => true,
                'severity' => 'critical',
            ],
        ],
        'recipients' => [
            'developers' => explode(',', env('ROLLBACK_NOTIFY_DEVELOPERS', '')),
            'operations' => explode(',', env('ROLLBACK_NOTIFY_OPERATIONS', '')),
            'management' => explode(',', env('ROLLBACK_NOTIFY_MANAGEMENT', '')),
        ],
        'templates' => [
            'rollback.notification' => [
                'subject' => 'Rollback Executed: {deployment_id}',
                'body' => 'A rollback has been executed for deployment {deployment_id}.',
            ],
            'rollback.slack' => [
                'channel' => '#deployments',
                'message' => 'Rollback executed for deployment {deployment_id}',
            ],
        ],
    ],
    
    'logging' => [
        'enabled' => true,
        'channel' => 'rollback',
        'level' => 'info',
        'retention' => [
            'days' => 30,
            'max_size' => '100M',
        ],
    ],
    
    'audit' => [
        'enabled' => true,
        'log_all_actions' => true,
        'log_all_users' => true,
        'retention' => [
            'days' => 90,
            'max_size' => '1G',
        ],
    ],
    
    'recovery' => [
        'enabled' => true,
        'max_attempts' => 3,
        'timeout' => 600, // 10 minutes
        'notify_on_failure' => true,
    ],
]; 