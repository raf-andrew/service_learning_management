<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for the authentication system
    | including password policies, session management, and security settings.
    |
    */

    'authentication' => [
        // Password policy settings
        'password' => [
            'min_length' => env('AUTH_PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => env('AUTH_PASSWORD_REQUIRE_UPPERCASE', true),
            'require_lowercase' => env('AUTH_PASSWORD_REQUIRE_LOWERCASE', true),
            'require_numbers' => env('AUTH_PASSWORD_REQUIRE_NUMBERS', true),
            'require_special_chars' => env('AUTH_PASSWORD_REQUIRE_SPECIAL', true),
            'max_age_days' => env('AUTH_PASSWORD_MAX_AGE_DAYS', 90),
            'history_count' => env('AUTH_PASSWORD_HISTORY_COUNT', 5),
        ],

        // Session management
        'session' => [
            'lifetime' => env('AUTH_SESSION_LIFETIME', 120), // minutes
            'inactivity_timeout' => env('AUTH_SESSION_INACTIVITY_TIMEOUT', 30), // minutes
            'concurrent_sessions' => env('AUTH_CONCURRENT_SESSIONS', 3),
            'regenerate_on_login' => env('AUTH_REGENERATE_ON_LOGIN', true),
        ],

        // Login security
        'login' => [
            'max_attempts' => env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
            'lockout_duration' => env('AUTH_LOCKOUT_DURATION', 15), // minutes
            'lockout_threshold' => env('AUTH_LOCKOUT_THRESHOLD', 3),
            'require_captcha' => env('AUTH_REQUIRE_CAPTCHA', false),
            'remember_me_days' => env('AUTH_REMEMBER_ME_DAYS', 30),
        ],

        // Two-factor authentication
        '2fa' => [
            'enabled' => env('AUTH_2FA_ENABLED', true),
            'methods' => ['totp', 'sms', 'email'],
            'backup_codes_count' => env('AUTH_2FA_BACKUP_CODES_COUNT', 10),
            'grace_period_days' => env('AUTH_2FA_GRACE_PERIOD_DAYS', 7),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains configuration for the role-based access control
    | system including default roles, permissions, and caching settings.
    |
    */

    'authorization' => [
        // Default roles
        'default_roles' => [
            'super-admin' => [
                'name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => ['*'],
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'System administrator with most permissions',
                'permissions' => [
                    'users.manage',
                    'roles.manage',
                    'permissions.manage',
                    'system.settings',
                    'reports.view',
                    'audit.view',
                ],
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Department or team manager',
                'permissions' => [
                    'users.view',
                    'reports.view',
                    'projects.manage',
                    'teams.manage',
                ],
            ],
            'user' => [
                'name' => 'User',
                'description' => 'Standard user with basic permissions',
                'permissions' => [
                    'profile.view',
                    'profile.edit',
                    'projects.view',
                    'projects.participate',
                ],
            ],
            'guest' => [
                'name' => 'Guest',
                'description' => 'Limited access user',
                'permissions' => [
                    'public.content.view',
                ],
            ],
        ],

        // Default permissions
        'default_permissions' => [
            // User management
            'users.view' => 'View user profiles',
            'users.create' => 'Create new users',
            'users.edit' => 'Edit user profiles',
            'users.delete' => 'Delete users',
            'users.manage' => 'Manage all users',

            // Role management
            'roles.view' => 'View roles',
            'roles.create' => 'Create new roles',
            'roles.edit' => 'Edit roles',
            'roles.delete' => 'Delete roles',
            'roles.manage' => 'Manage all roles',

            // Permission management
            'permissions.view' => 'View permissions',
            'permissions.create' => 'Create new permissions',
            'permissions.edit' => 'Edit permissions',
            'permissions.delete' => 'Delete permissions',
            'permissions.manage' => 'Manage all permissions',

            // System management
            'system.settings' => 'Manage system settings',
            'system.maintenance' => 'Access maintenance mode',
            'system.backup' => 'Create system backups',

            // Content management
            'content.view' => 'View content',
            'content.create' => 'Create content',
            'content.edit' => 'Edit content',
            'content.delete' => 'Delete content',
            'content.publish' => 'Publish content',

            // Project management
            'projects.view' => 'View projects',
            'projects.create' => 'Create projects',
            'projects.edit' => 'Edit projects',
            'projects.delete' => 'Delete projects',
            'projects.manage' => 'Manage all projects',
            'projects.participate' => 'Participate in projects',

            // Team management
            'teams.view' => 'View teams',
            'teams.create' => 'Create teams',
            'teams.edit' => 'Edit teams',
            'teams.delete' => 'Delete teams',
            'teams.manage' => 'Manage all teams',

            // Reporting
            'reports.view' => 'View reports',
            'reports.create' => 'Create reports',
            'reports.export' => 'Export reports',

            // Audit and compliance
            'audit.view' => 'View audit logs',
            'audit.export' => 'Export audit logs',
            'compliance.view' => 'View compliance reports',
            'compliance.manage' => 'Manage compliance',

            // Profile management
            'profile.view' => 'View own profile',
            'profile.edit' => 'Edit own profile',
            'profile.delete' => 'Delete own profile',

            // Public content
            'public.content.view' => 'View public content',
        ],

        // Caching settings
        'cache' => [
            'enabled' => env('AUTH_CACHE_ENABLED', true),
            'ttl' => env('AUTH_CACHE_TTL', 3600), // seconds
            'prefix' => env('AUTH_CACHE_PREFIX', 'auth_'),
            'tags' => ['auth', 'roles', 'permissions'],
        ],

        // Permission inheritance
        'inheritance' => [
            'enabled' => env('AUTH_INHERITANCE_ENABLED', true),
            'max_depth' => env('AUTH_INHERITANCE_MAX_DEPTH', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains security-related settings including audit logging,
    | IP restrictions, and security headers.
    |
    */

    'security' => [
        // Audit logging
        'audit' => [
            'enabled' => env('AUTH_AUDIT_ENABLED', true),
            'log_level' => env('AUTH_AUDIT_LOG_LEVEL', 'info'),
            'events' => [
                'login',
                'logout',
                'failed_login',
                'password_change',
                'role_assignment',
                'permission_assignment',
                'profile_update',
                'account_lockout',
            ],
            'retention_days' => env('AUTH_AUDIT_RETENTION_DAYS', 365),
        ],

        // IP restrictions
        'ip_restrictions' => [
            'enabled' => env('AUTH_IP_RESTRICTIONS_ENABLED', false),
            'whitelist' => env('AUTH_IP_WHITELIST', []),
            'blacklist' => env('AUTH_IP_BLACKLIST', []),
            'admin_override' => env('AUTH_IP_ADMIN_OVERRIDE', true),
        ],

        // Security headers
        'headers' => [
            'x_frame_options' => env('AUTH_X_FRAME_OPTIONS', 'DENY'),
            'x_content_type_options' => env('AUTH_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
            'x_xss_protection' => env('AUTH_X_XSS_PROTECTION', '1; mode=block'),
            'referrer_policy' => env('AUTH_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        ],

        // Account lockout
        'lockout' => [
            'enabled' => env('AUTH_LOCKOUT_ENABLED', true),
            'max_attempts' => env('AUTH_LOCKOUT_MAX_ATTEMPTS', 5),
            'duration' => env('AUTH_LOCKOUT_DURATION', 15), // minutes
            'permanent_after' => env('AUTH_LOCKOUT_PERMANENT_AFTER', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains settings for authentication-related notifications
    | including email templates and notification channels.
    |
    */

    'notifications' => [
        // Email notifications
        'email' => [
            'enabled' => env('AUTH_EMAIL_NOTIFICATIONS', true),
            'from_address' => env('AUTH_EMAIL_FROM', 'noreply@example.com'),
            'from_name' => env('AUTH_EMAIL_FROM_NAME', 'System Administrator'),
            'templates' => [
                'welcome' => 'auth::emails.welcome',
                'password_reset' => 'auth::emails.password-reset',
                'account_locked' => 'auth::emails.account-locked',
                'login_alert' => 'auth::emails.login-alert',
                'role_assigned' => 'auth::emails.role-assigned',
                'permission_granted' => 'auth::emails.permission-granted',
            ],
        ],

        // SMS notifications
        'sms' => [
            'enabled' => env('AUTH_SMS_NOTIFICATIONS', false),
            'provider' => env('AUTH_SMS_PROVIDER', 'twilio'),
            'templates' => [
                '2fa_code' => 'Your 2FA code is: {code}',
                'login_alert' => 'Login detected from {location}',
            ],
        ],

        // Push notifications
        'push' => [
            'enabled' => env('AUTH_PUSH_NOTIFICATIONS', false),
            'provider' => env('AUTH_PUSH_PROVIDER', 'firebase'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains settings for external integrations including
    | LDAP, OAuth, and third-party authentication providers.
    |
    */

    'integrations' => [
        // LDAP integration
        'ldap' => [
            'enabled' => env('AUTH_LDAP_ENABLED', false),
            'host' => env('AUTH_LDAP_HOST'),
            'port' => env('AUTH_LDAP_PORT', 389),
            'base_dn' => env('AUTH_LDAP_BASE_DN'),
            'username' => env('AUTH_LDAP_USERNAME'),
            'password' => env('AUTH_LDAP_PASSWORD'),
            'ssl' => env('AUTH_LDAP_SSL', false),
            'tls' => env('AUTH_LDAP_TLS', false),
        ],

        // OAuth providers
        'oauth' => [
            'enabled' => env('AUTH_OAUTH_ENABLED', false),
            'providers' => [
                'google' => [
                    'enabled' => env('AUTH_OAUTH_GOOGLE_ENABLED', false),
                    'client_id' => env('AUTH_OAUTH_GOOGLE_CLIENT_ID'),
                    'client_secret' => env('AUTH_OAUTH_GOOGLE_CLIENT_SECRET'),
                    'redirect_uri' => env('AUTH_OAUTH_GOOGLE_REDIRECT_URI'),
                ],
                'github' => [
                    'enabled' => env('AUTH_OAUTH_GITHUB_ENABLED', false),
                    'client_id' => env('AUTH_OAUTH_GITHUB_CLIENT_ID'),
                    'client_secret' => env('AUTH_OAUTH_GITHUB_CLIENT_SECRET'),
                    'redirect_uri' => env('AUTH_OAUTH_GITHUB_REDIRECT_URI'),
                ],
                'microsoft' => [
                    'enabled' => env('AUTH_OAUTH_MICROSOFT_ENABLED', false),
                    'client_id' => env('AUTH_OAUTH_MICROSOFT_CLIENT_ID'),
                    'client_secret' => env('AUTH_OAUTH_MICROSOFT_CLIENT_SECRET'),
                    'redirect_uri' => env('AUTH_OAUTH_MICROSOFT_REDIRECT_URI'),
                ],
            ],
        ],

        // SAML integration
        'saml' => [
            'enabled' => env('AUTH_SAML_ENABLED', false),
            'idp_entity_id' => env('AUTH_SAML_IDP_ENTITY_ID'),
            'idp_sso_url' => env('AUTH_SAML_IDP_SSO_URL'),
            'idp_x509_cert' => env('AUTH_SAML_IDP_X509_CERT'),
            'sp_entity_id' => env('AUTH_SAML_SP_ENTITY_ID'),
            'sp_acs_url' => env('AUTH_SAML_SP_ACS_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains performance-related settings including caching,
    | database optimization, and query limits.
    |
    */

    'performance' => [
        // Database optimization
        'database' => [
            'eager_loading' => env('AUTH_EAGER_LOADING', true),
            'query_cache' => env('AUTH_QUERY_CACHE', true),
            'batch_size' => env('AUTH_BATCH_SIZE', 1000),
        ],

        // Memory optimization
        'memory' => [
            'cache_roles' => env('AUTH_CACHE_ROLES', true),
            'cache_permissions' => env('AUTH_CACHE_PERMISSIONS', true),
            'cache_user_roles' => env('AUTH_CACHE_USER_ROLES', true),
            'cache_user_permissions' => env('AUTH_CACHE_USER_PERMISSIONS', true),
        ],

        // Query limits
        'limits' => [
            'max_roles_per_user' => env('AUTH_MAX_ROLES_PER_USER', 10),
            'max_permissions_per_role' => env('AUTH_MAX_PERMISSIONS_PER_ROLE', 100),
            'max_users_per_page' => env('AUTH_MAX_USERS_PER_PAGE', 50),
            'max_roles_per_page' => env('AUTH_MAX_ROLES_PER_PAGE', 50),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains development and debugging settings.
    |
    */

    'development' => [
        'debug' => env('AUTH_DEBUG', false),
        'log_queries' => env('AUTH_LOG_QUERIES', false),
        'log_performance' => env('AUTH_LOG_PERFORMANCE', false),
        'test_mode' => env('AUTH_TEST_MODE', false),
    ],
]; 