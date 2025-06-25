<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SOC2 Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the SOC2 compliance module.
    | It includes settings for validation thresholds, audit logging,
    | and compliance management.
    |
    */

    'enabled' => env('SOC2_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Thresholds and validation rules for SOC2 compliance.
    |
    */
    'validation' => [
        'thresholds' => [
            'overall_compliance' => env('SOC2_OVERALL_COMPLIANCE_THRESHOLD', 80),
            'security_score' => env('SOC2_SECURITY_SCORE_THRESHOLD', 85),
            'availability_score' => env('SOC2_AVAILABILITY_SCORE_THRESHOLD', 99.5),
            'processing_integrity_score' => env('SOC2_PROCESSING_INTEGRITY_SCORE_THRESHOLD', 95),
            'confidentiality_score' => env('SOC2_CONFIDENTIALITY_SCORE_THRESHOLD', 90),
            'privacy_score' => env('SOC2_PRIVACY_SCORE_THRESHOLD', 85),
            'control_compliance' => env('SOC2_CONTROL_COMPLIANCE_THRESHOLD', 80),
        ],

        'rules' => [
            'require_audit_logging' => env('SOC2_REQUIRE_AUDIT_LOGGING', true),
            'require_risk_assessment' => env('SOC2_REQUIRE_RISK_ASSESSMENT', true),
            'require_control_assessment' => env('SOC2_REQUIRE_CONTROL_ASSESSMENT', true),
            'require_compliance_report' => env('SOC2_REQUIRE_COMPLIANCE_REPORT', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for audit logging and retention.
    |
    */
    'audit' => [
        'enabled' => env('SOC2_AUDIT_LOGGING_ENABLED', true),
        'retention_period' => env('SOC2_AUDIT_RETENTION_DAYS', 2555), // 7 years
        'log_level' => env('SOC2_AUDIT_LOG_LEVEL', 'info'),
        'compliance_relevant_actions' => [
            'certification_created',
            'certification_updated',
            'certification_deleted',
            'control_assessment_created',
            'control_assessment_updated',
            'risk_assessment_created',
            'risk_assessment_updated',
            'compliance_report_created',
            'compliance_report_approved',
            'compliance_report_rejected',
            'data_exported',
            'data_deleted',
            'user_access_granted',
            'user_access_revoked',
        ],
        'sensitive_data_fields' => [
            'password',
            'token',
            'secret',
            'key',
            'credential',
            'ssn',
            'credit_card',
            'bank_account',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Trust Service Criteria
    |--------------------------------------------------------------------------
    |
    | Available trust service criteria for SOC2 compliance.
    |
    */
    'trust_service_criteria' => [
        'Security' => [
            'description' => 'Information and systems are protected against unauthorized access',
            'controls' => [
                'access_control',
                'authentication',
                'authorization',
                'encryption',
                'network_security',
                'physical_security',
            ],
        ],
        'Availability' => [
            'description' => 'Information and systems are available for operation and use',
            'controls' => [
                'backup_recovery',
                'disaster_recovery',
                'system_monitoring',
                'capacity_planning',
                'incident_response',
            ],
        ],
        'Processing Integrity' => [
            'description' => 'System processing is complete, accurate, timely, and authorized',
            'controls' => [
                'data_validation',
                'error_handling',
                'processing_monitoring',
                'change_management',
                'quality_assurance',
            ],
        ],
        'Confidentiality' => [
            'description' => 'Information designated as confidential is protected',
            'controls' => [
                'data_classification',
                'encryption',
                'access_controls',
                'data_handling',
                'disposal_procedures',
            ],
        ],
        'Privacy' => [
            'description' => 'Personal information is collected, used, retained, and disclosed in conformity with commitments',
            'controls' => [
                'consent_management',
                'data_minimization',
                'purpose_limitation',
                'data_subject_rights',
                'privacy_notices',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Assessment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for risk assessment and management.
    |
    */
    'risk_assessment' => [
        'likelihood_scale' => [
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
        ],
        'impact_scale' => [
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
        ],
        'risk_levels' => [
            'low' => ['min_score' => 1, 'max_score' => 5],
            'medium' => ['min_score' => 6, 'max_score' => 9],
            'high' => ['min_score' => 10, 'max_score' => 14],
            'critical' => ['min_score' => 15, 'max_score' => 25],
        ],
        'review_frequency' => [
            'low' => 365, // days
            'medium' => 180,
            'high' => 90,
            'critical' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Control Assessment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for control assessment and evaluation.
    |
    */
    'control_assessment' => [
        'statuses' => [
            'compliant' => 'Control is fully implemented and effective',
            'non_compliant' => 'Control is not implemented or ineffective',
            'partially_compliant' => 'Control is partially implemented',
            'not_applicable' => 'Control does not apply to this environment',
        ],
        'remediation_statuses' => [
            'not_started' => 'Remediation has not been initiated',
            'in_progress' => 'Remediation is currently being implemented',
            'completed' => 'Remediation has been successfully completed',
            'overdue' => 'Remediation is past the deadline',
        ],
        'evidence_requirements' => [
            'documentation' => true,
            'screenshots' => true,
            'logs' => true,
            'interviews' => true,
            'testing' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Reporting Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for compliance reporting and documentation.
    |
    */
    'reporting' => [
        'report_types' => [
            'initial' => 'Initial compliance assessment',
            'periodic' => 'Periodic compliance review',
            'final' => 'Final compliance report',
            'exception' => 'Exception or incident report',
        ],
        'statuses' => [
            'draft' => 'Report is in draft status',
            'in_review' => 'Report is under review',
            'approved' => 'Report has been approved',
            'rejected' => 'Report has been rejected',
            'published' => 'Report has been published',
        ],
        'retention_period' => env('SOC2_REPORT_RETENTION_DAYS', 2555), // 7 years
        'auto_approval' => env('SOC2_AUTO_APPROVAL', false),
        'require_approval' => env('SOC2_REQUIRE_APPROVAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for notifications and alerts.
    |
    */
    'notifications' => [
        'enabled' => env('SOC2_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'email' => env('SOC2_EMAIL_NOTIFICATIONS', true),
            'slack' => env('SOC2_SLACK_NOTIFICATIONS', false),
            'webhook' => env('SOC2_WEBHOOK_NOTIFICATIONS', false),
        ],
        'events' => [
            'certification_expiring' => [
                'enabled' => true,
                'days_before' => 30,
            ],
            'control_assessment_overdue' => [
                'enabled' => true,
                'days_after_deadline' => 7,
            ],
            'risk_assessment_overdue' => [
                'enabled' => true,
                'days_after_deadline' => 7,
            ],
            'compliance_threshold_breach' => [
                'enabled' => true,
                'threshold' => 80,
            ],
            'critical_finding' => [
                'enabled' => true,
                'immediate' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for integration with other modules and systems.
    |
    */
    'integrations' => [
        'e2ee' => [
            'enabled' => env('SOC2_E2EE_INTEGRATION', true),
            'encrypt_audit_logs' => true,
            'encrypt_sensitive_data' => true,
        ],
        'shared' => [
            'enabled' => env('SOC2_SHARED_INTEGRATION', true),
            'use_audit_service' => true,
            'use_configuration_service' => true,
        ],
        'auth' => [
            'enabled' => env('SOC2_AUTH_INTEGRATION', true),
            'require_authentication' => true,
            'require_authorization' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization.
    |
    */
    'performance' => [
        'cache_enabled' => env('SOC2_CACHE_ENABLED', true),
        'cache_ttl' => env('SOC2_CACHE_TTL', 3600), // 1 hour
        'batch_processing' => env('SOC2_BATCH_PROCESSING', true),
        'batch_size' => env('SOC2_BATCH_SIZE', 100),
        'async_processing' => env('SOC2_ASYNC_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for security measures.
    |
    */
    'security' => [
        'encryption' => [
            'enabled' => env('SOC2_ENCRYPTION_ENABLED', true),
            'algorithm' => env('SOC2_ENCRYPTION_ALGORITHM', 'AES-256-GCM'),
        ],
        'access_control' => [
            'require_authentication' => true,
            'require_authorization' => true,
            'session_timeout' => env('SOC2_SESSION_TIMEOUT', 3600), // 1 hour
        ],
        'data_protection' => [
            'mask_sensitive_data' => true,
            'anonymize_logs' => false,
            'data_retention' => true,
        ],
    ],
]; 