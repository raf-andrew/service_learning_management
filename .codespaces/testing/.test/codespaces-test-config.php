<?php

return [
    'test_suites' => [
        'feature' => [
            'Authentication' => [
                'User Login',
                'User Registration',
                'Password Reset'
            ],
            'User Management' => [
                'Profile Update',
                'Role Management',
                'User Search'
            ],
            'Service Learning' => [
                'Project Creation',
                'Project Management',
                'Hours Tracking'
            ],
            'API' => [
                'Authentication',
                'Rate Limiting',
                'Concurrent Requests'
            ],
            'File Operations' => [
                'File Upload',
                'File Download',
                'File Management'
            ],
            'Notifications' => [
                'Mail Sending',
                'Queue Processing'
            ]
        ]
    ],
    'checklist_items' => [
        'Authentication' => [
            'description' => 'Authentication and authorization features',
            'tests' => ['User Login', 'User Registration', 'Password Reset']
        ],
        'User Management' => [
            'description' => 'User profile and management features',
            'tests' => ['Profile Update', 'Role Management', 'User Search']
        ],
        'Service Learning' => [
            'description' => 'Core service learning functionality',
            'tests' => ['Project Creation', 'Project Management', 'Hours Tracking']
        ],
        'API' => [
            'description' => 'API functionality and security',
            'tests' => ['Authentication', 'Rate Limiting', 'Concurrent Requests']
        ],
        'File Operations' => [
            'description' => 'File handling and management',
            'tests' => ['File Upload', 'File Download', 'File Management']
        ],
        'Notifications' => [
            'description' => 'Email and queue processing',
            'tests' => ['Mail Sending', 'Queue Processing']
        ]
    ],
    'reporting' => [
        'log_directory' => '.codespaces/log',
        'report_directory' => '.codespaces/testing/.test/results',
        'tracking_directory' => '.codespaces/testing/.test/tracking',
        'formats' => ['markdown', 'json', 'html']
    ],
    'services' => [
        'database' => [
            'required' => true,
            'health_check' => true
        ],
        'redis' => [
            'required' => true,
            'health_check' => true
        ],
        'mail' => [
            'required' => true,
            'health_check' => true
        ],
        'queue' => [
            'required' => true,
            'health_check' => true
        ]
    ]
]; 