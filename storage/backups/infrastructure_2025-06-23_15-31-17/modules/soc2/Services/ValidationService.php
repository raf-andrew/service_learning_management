<?php

namespace App\Modules\Soc2\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Modules\Soc2\Models\Certification;
use App\Modules\Soc2\Models\ControlAssessment;
use App\Modules\Soc2\Models\RiskAssessment;
use App\Modules\Soc2\Models\AuditLog;

/**
 * SOC2 Validation Service
 * 
 * Provides comprehensive system validation, compliance checking, and security assessment
 * for SOC2 Type I and Type II certifications.
 */
class ValidationService
{
    /**
     * Validate the entire SOC2 system
     */
    public function validateSystem(): array
    {
        $results = [
            'overall_status' => 'pass',
            'score' => 0,
            'checks' => [],
            'issues' => [],
            'recommendations' => [],
        ];

        // Database validation
        $dbValidation = $this->validateDatabase();
        $results['checks'][] = $dbValidation;

        // Storage validation
        $storageValidation = $this->validateStorage();
        $results['checks'][] = $storageValidation;

        // Configuration validation
        $configValidation = $this->validateConfiguration();
        $results['checks'][] = $configValidation;

        // Security validation
        $securityValidation = $this->validateSecurity();
        $results['checks'][] = $securityValidation;

        // Compliance validation
        $complianceValidation = $this->validateCompliance();
        $results['checks'][] = $complianceValidation;

        // Calculate overall score
        $passCount = count(array_filter($results['checks'], fn($check) => $check['status'] === 'pass'));
        $totalCount = count($results['checks']);
        $results['score'] = $totalCount > 0 ? round(($passCount / $totalCount) * 100, 2) : 0;

        // Determine overall status
        $failCount = count(array_filter($results['checks'], fn($check) => $check['status'] === 'fail'));
        if ($failCount > 0) {
            $results['overall_status'] = 'fail';
        } elseif (count(array_filter($results['checks'], fn($check) => $check['status'] === 'warn')) > 0) {
            $results['overall_status'] = 'warn';
        }

        return $results;
    }

    /**
     * Validate database connectivity and structure
     */
    private function validateDatabase(): array
    {
        $check = [
            'name' => 'Database',
            'status' => 'pass',
            'details' => [],
            'issues' => [],
        ];

        try {
            // Test connection
            DB::connection('soc2_sqlite')->getPdo();
            $check['details'][] = '✅ Database connection successful';

            // Check required tables
            $requiredTables = [
                'soc2_certifications',
                'soc2_control_assessments',
                'soc2_audit_logs',
                'soc2_compliance_reports',
                'soc2_risk_assessments',
                'soc2_evidence',
            ];

            foreach ($requiredTables as $table) {
                if (DB::connection('soc2_sqlite')->getSchemaBuilder()->hasTable($table)) {
                    $check['details'][] = "✅ Table '{$table}' exists";
                } else {
                    $check['details'][] = "❌ Table '{$table}' missing";
                    $check['issues'][] = "Missing table: {$table}";
                    $check['status'] = 'fail';
                }
            }

            // Check data integrity
            $this->validateDataIntegrity($check);

        } catch (\Exception $e) {
            $check['details'][] = '❌ Database connection failed: ' . $e->getMessage();
            $check['issues'][] = 'Database connection failed';
            $check['status'] = 'fail';
        }

        return $check;
    }

    /**
     * Validate storage directories and permissions
     */
    private function validateStorage(): array
    {
        $check = [
            'name' => 'Storage',
            'status' => 'pass',
            'details' => [],
            'issues' => [],
        ];

        $directories = [
            'modules/soc2/storage/evidence',
            'modules/soc2/storage/reports',
            'modules/soc2/storage/logs',
            'modules/soc2/storage/backups',
            'modules/soc2/storage/temp',
        ];

        foreach ($directories as $directory) {
            $path = storage_path($directory);
            
            if (!is_dir($path)) {
                $check['details'][] = "❌ Directory '{$directory}' does not exist";
                $check['issues'][] = "Missing directory: {$directory}";
                $check['status'] = 'fail';
            } elseif (!is_writable($path)) {
                $check['details'][] = "❌ Directory '{$directory}' is not writable";
                $check['issues'][] = "Directory not writable: {$directory}";
                $check['status'] = 'fail';
            } else {
                $check['details'][] = "✅ Directory '{$directory}' exists and writable";
            }
        }

        // Check disk space
        $this->validateDiskSpace($check);

        return $check;
    }

    /**
     * Validate configuration settings
     */
    private function validateConfiguration(): array
    {
        $check = [
            'name' => 'Configuration',
            'status' => 'pass',
            'details' => [],
            'issues' => [],
        ];

        // Check required config values
        $requiredConfigs = [
            'modules.modules.soc2.enabled' => 'SOC2 system enabled',
            'modules.modules.soc2.database.connection' => 'Database connection configured',
            'modules.modules.soc2.database.database' => 'Database path configured',
            'modules.modules.soc2.audit.enabled' => 'Audit logging enabled',
        ];

        foreach ($requiredConfigs as $config => $description) {
            if (config($config) !== null) {
                $check['details'][] = "✅ {$description}";
            } else {
                $check['details'][] = "❌ {$description} missing";
                $check['issues'][] = "Missing configuration: {$config}";
                $check['status'] = 'fail';
            }
        }

        // Check threshold values
        $thresholds = [
            'modules.modules.soc2.validation.thresholds.compliance_score' => 90,
            'modules.modules.soc2.validation.thresholds.security_score' => 85,
            'modules.modules.soc2.validation.thresholds.availability_score' => 99.5,
        ];

        foreach ($thresholds as $threshold => $minValue) {
            $value = config($threshold);
            if ($value === null) {
                $check['details'][] = "⚠️  Threshold '{$threshold}' not configured";
                $check['issues'][] = "Missing threshold: {$threshold}";
                $check['status'] = 'warn';
            } elseif ($value < $minValue) {
                $check['details'][] = "⚠️  Threshold '{$threshold}' below recommended ({$value} < {$minValue})";
                $check['issues'][] = "Low threshold: {$threshold}";
                $check['status'] = 'warn';
            } else {
                $check['details'][] = "✅ Threshold '{$threshold}' properly configured ({$value})";
            }
        }

        return $check;
    }

    /**
     * Validate security settings
     */
    private function validateSecurity(): array
    {
        $check = [
            'name' => 'Security',
            'status' => 'pass',
            'details' => [],
            'issues' => [],
        ];

        // Check file permissions
        $criticalFiles = [
            storage_path('modules/soc2/database/soc2.sqlite'),
            config_path('modules.php'),
        ];

        foreach ($criticalFiles as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file) & 0777;
                if ($perms > 0640) {
                    $check['details'][] = "⚠️  File '{$file}' has loose permissions (" . decoct($perms) . ")";
                    $check['issues'][] = "Loose file permissions: {$file}";
                    $check['status'] = 'warn';
                } else {
                    $check['details'][] = "✅ File '{$file}' has secure permissions";
                }
            }
        }

        // Check for sensitive data exposure
        $sensitivePatterns = [
            'password' => '/password\s*=\s*["\'][^"\']+["\']/i',
            'secret' => '/secret\s*=\s*["\'][^"\']+["\']/i',
            'key' => '/key\s*=\s*["\'][^"\']+["\']/i',
        ];

        $configFiles = [
            config_path('modules.php'),
            base_path('.env'),
        ];

        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                foreach ($sensitivePatterns as $type => $pattern) {
                    if (preg_match($pattern, $content)) {
                        $check['details'][] = "⚠️  Potential {$type} exposure in {$file}";
                        $check['issues'][] = "Sensitive data exposure: {$type} in {$file}";
                        $check['status'] = 'warn';
                    }
                }
            }
        }

        return $check;
    }

    /**
     * Validate compliance requirements
     */
    private function validateCompliance(): array
    {
        $check = [
            'name' => 'Compliance',
            'status' => 'pass',
            'details' => [],
            'issues' => [],
        ];

        // Check for active certifications
        $activeCertifications = Certification::where('status', 'active')->count();
        if ($activeCertifications === 0) {
            $check['details'][] = "⚠️  No active certifications found";
            $check['issues'][] = "No active certifications";
            $check['status'] = 'warn';
        } else {
            $check['details'][] = "✅ {$activeCertifications} active certification(s) found";
        }

        // Check for overdue assessments
        $overdueAssessments = ControlAssessment::where('assessment_due_date', '<', now())
            ->where('status', 'pending')
            ->count();

        if ($overdueAssessments > 0) {
            $check['details'][] = "❌ {$overdueAssessments} overdue control assessment(s)";
            $check['issues'][] = "Overdue assessments: {$overdueAssessments}";
            $check['status'] = 'fail';
        } else {
            $check['details'][] = "✅ No overdue control assessments";
        }

        // Check for high-risk items
        $highRiskItems = RiskAssessment::where('risk_level', 'high')
            ->orWhere('risk_level', 'critical')
            ->where('mitigation_status', '!=', 'mitigated')
            ->count();

        if ($highRiskItems > 0) {
            $check['details'][] = "⚠️  {$highRiskItems} unmitigated high/critical risk(s)";
            $check['issues'][] = "Unmitigated high risks: {$highRiskItems}";
            $check['status'] = 'warn';
        } else {
            $check['details'][] = "✅ No unmitigated high/critical risks";
        }

        // Check audit log retention
        $oldestLog = AuditLog::orderBy('created_at', 'asc')->first();
        if ($oldestLog) {
            $retentionDays = now()->diffInDays($oldestLog->created_at);
            $requiredRetention = config('modules.modules.soc2.audit.retention_days', 2555); // 7 years
            
            if ($retentionDays < $requiredRetention) {
                $check['details'][] = "✅ Audit logs retained for {$retentionDays} days (min: {$requiredRetention})";
            } else {
                $check['details'][] = "⚠️  Audit logs older than retention period ({$retentionDays} days)";
                $check['issues'][] = "Audit log retention exceeded";
                $check['status'] = 'warn';
            }
        }

        return $check;
    }

    /**
     * Validate data integrity
     */
    private function validateDataIntegrity(array &$check): void
    {
        try {
            // Check for orphaned records
            $orphanedAssessments = ControlAssessment::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('soc2_certifications')
                    ->whereRaw('soc2_certifications.id = soc2_control_assessments.certification_id');
            })->count();

            if ($orphanedAssessments > 0) {
                $check['details'][] = "❌ {$orphanedAssessments} orphaned control assessment(s)";
                $check['issues'][] = "Orphaned control assessments: {$orphanedAssessments}";
                $check['status'] = 'fail';
            } else {
                $check['details'][] = "✅ No orphaned control assessments";
            }

            // Check for data consistency
            $inconsistentAssessments = ControlAssessment::where('compliance_score', '<', 0)
                ->orWhere('compliance_score', '>', 100)
                ->count();

            if ($inconsistentAssessments > 0) {
                $check['details'][] = "❌ {$inconsistentAssessments} assessment(s) with invalid scores";
                $check['issues'][] = "Invalid assessment scores: {$inconsistentAssessments}";
                $check['status'] = 'fail';
            } else {
                $check['details'][] = "✅ All assessment scores are valid";
            }

        } catch (\Exception $e) {
            $check['details'][] = "❌ Data integrity check failed: " . $e->getMessage();
            $check['issues'][] = "Data integrity check failed";
            $check['status'] = 'fail';
        }
    }

    /**
     * Validate disk space
     */
    private function validateDiskSpace(array &$check): void
    {
        try {
            $diskFree = disk_free_space(storage_path());
            $diskTotal = disk_total_space(storage_path());
            $diskUsed = $diskTotal - $diskFree;
            $diskUsagePercent = ($diskUsed / $diskTotal) * 100;

            if ($diskUsagePercent > 90) {
                $check['details'][] = "❌ Disk usage critical: " . round($diskUsagePercent, 2) . "%";
                $check['issues'][] = "Critical disk usage: " . round($diskUsagePercent, 2) . "%";
                $check['status'] = 'fail';
            } elseif ($diskUsagePercent > 80) {
                $check['details'][] = "⚠️  Disk usage high: " . round($diskUsagePercent, 2) . "%";
                $check['issues'][] = "High disk usage: " . round($diskUsagePercent, 2) . "%";
                $check['status'] = 'warn';
            } else {
                $check['details'][] = "✅ Disk usage acceptable: " . round($diskUsagePercent, 2) . "%";
            }

        } catch (\Exception $e) {
            $check['details'][] = "⚠️  Could not check disk space: " . $e->getMessage();
        }
    }

    /**
     * Validate specific certification
     */
    public function validateCertification(Certification $certification): array
    {
        $results = [
            'certification_id' => $certification->id,
            'certification_type' => $certification->certification_type,
            'overall_status' => 'pass',
            'score' => 0,
            'checks' => [],
            'issues' => [],
            'recommendations' => [],
        ];

        // Check certification status
        if (!$certification->isCurrentlyValid()) {
            $results['checks'][] = [
                'name' => 'Certification Status',
                'status' => 'fail',
                'details' => ['❌ Certification is not currently valid'],
                'issues' => ['Certification not valid'],
            ];
            $results['overall_status'] = 'fail';
        } else {
            $results['checks'][] = [
                'name' => 'Certification Status',
                'status' => 'pass',
                'details' => ['✅ Certification is currently valid'],
            ];
        }

        // Check control assessments
        $controlAssessments = $certification->controlAssessments;
        $nonCompliantControls = $controlAssessments->filter(fn($c) => !$c->isCompliant());
        
        if ($nonCompliantControls->count() > 0) {
            $results['checks'][] = [
                'name' => 'Control Compliance',
                'status' => 'fail',
                'details' => ["❌ {$nonCompliantControls->count()} non-compliant control(s)"],
                'issues' => ['Non-compliant controls'],
            ];
            $results['overall_status'] = 'fail';
        } else {
            $results['checks'][] = [
                'name' => 'Control Compliance',
                'status' => 'pass',
                'details' => ['✅ All controls are compliant'],
            ];
        }

        // Check risk assessments
        $riskAssessments = $certification->riskAssessments;
        $unmitigatedRisks = $riskAssessments->filter(fn($r) => !$r->isMitigated() && $r->risk_level !== 'low');
        
        if ($unmitigatedRisks->count() > 0) {
            $results['checks'][] = [
                'name' => 'Risk Mitigation',
                'status' => 'warn',
                'details' => ["⚠️  {$unmitigatedRisks->count()} unmitigated risk(s)"],
                'issues' => ['Unmitigated risks'],
            ];
            if ($results['overall_status'] !== 'fail') {
                $results['overall_status'] = 'warn';
            }
        } else {
            $results['checks'][] = [
                'name' => 'Risk Mitigation',
                'status' => 'pass',
                'details' => ['✅ All risks are mitigated'],
            ];
        }

        // Calculate overall score
        $passCount = count(array_filter($results['checks'], fn($check) => $check['status'] === 'pass'));
        $totalCount = count($results['checks']);
        $results['score'] = $totalCount > 0 ? round(($passCount / $totalCount) * 100, 2) : 0;

        return $results;
    }
} 