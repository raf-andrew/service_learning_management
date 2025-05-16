<?php

namespace MCP\Agentic\Agents\Security;

use MCP\Agentic\Agents\BaseAgent;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\Alerting;

/**
 * Audit Agent
 * 
 * Manages system auditing operations including activity monitoring,
 * compliance validation, audit reporting, alert system, and investigation tools.
 * 
 * @package MCP\Agentic\Agents\Security
 */
class AuditAgent extends BaseAgent
{
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;
    protected Alerting $alerting;

    /**
     * Initialize the audit agent
     */
    public function __construct(
        AccessControl $accessControl,
        Logging $logging,
        Monitoring $monitoring,
        Reporting $reporting,
        Alerting $alerting
    ) {
        parent::__construct();
        
        $this->accessControl = $accessControl;
        $this->logging = $logging;
        $this->monitoring = $monitoring;
        $this->reporting = $reporting;
        $this->alerting = $alerting;
    }

    /**
     * Monitor system activity
     * 
     * @param array $filters Activity filters
     * @return array Monitoring results
     */
    public function monitorActivity(array $filters = []): array
    {
        $this->validateAccess('audit.monitor');
        
        $this->logging->info('Monitoring system activity', [
            'filters' => $filters,
        ]);
        
        try {
            $activities = $this->monitoring->trackActivities([
                'filters' => $filters,
                'include_metadata' => true,
                'include_context' => true,
            ]);
            
            // Check for suspicious activities
            $suspicious = $this->detectSuspiciousActivity($activities);
            if ($suspicious) {
                $this->alerting->alert('Suspicious activity detected', [
                    'activities' => $suspicious,
                ]);
            }
            
            $this->logging->info('System activity monitored', [
                'filters' => $filters,
                'activities' => $activities,
            ]);
            
            return $activities;
            
        } catch (\Exception $e) {
            $this->logging->error('Activity monitoring failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Validate compliance
     * 
     * @param array $requirements Compliance requirements
     * @return array Validation results
     */
    public function validateCompliance(array $requirements): array
    {
        $this->validateAccess('audit.compliance');
        
        $this->logging->info('Validating compliance', [
            'requirements' => $requirements,
        ]);
        
        try {
            $results = [];
            foreach ($requirements as $requirement) {
                $result = $this->monitoring->validateCompliance([
                    'requirement' => $requirement,
                    'include_evidence' => true,
                    'include_recommendations' => true,
                ]);
                
                $results[] = $result;
                
                // Alert on compliance violations
                if (!$result['compliant']) {
                    $this->alerting->alert('Compliance violation detected', [
                        'requirement' => $requirement,
                        'violation' => $result['violation'],
                    ]);
                }
            }
            
            $this->logging->info('Compliance validation completed', [
                'requirements' => $requirements,
                'results' => $results,
            ]);
            
            return $results;
            
        } catch (\Exception $e) {
            $this->logging->error('Compliance validation failed', [
                'requirements' => $requirements,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate audit report
     * 
     * @param array $filters Report filters
     * @return array Audit report
     */
    public function generateAuditReport(array $filters = []): array
    {
        $this->validateAccess('audit.report');
        
        $this->logging->info('Generating audit report', [
            'filters' => $filters,
        ]);
        
        try {
            $report = $this->reporting->generateAuditReport([
                'filters' => $filters,
                'include_activities' => true,
                'include_compliance' => true,
                'include_alerts' => true,
                'include_metrics' => true,
            ]);
            
            $this->logging->info('Audit report generated', [
                'filters' => $filters,
                'report' => $report,
            ]);
            
            return $report;
            
        } catch (\Exception $e) {
            $this->logging->error('Audit report generation failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Investigate activity
     * 
     * @param array $criteria Investigation criteria
     * @return array Investigation results
     */
    public function investigateActivity(array $criteria): array
    {
        $this->validateAccess('audit.investigate');
        
        $this->logging->info('Investigating activity', [
            'criteria' => $criteria,
        ]);
        
        try {
            $investigation = $this->monitoring->investigateActivity([
                'criteria' => $criteria,
                'include_timeline' => true,
                'include_evidence' => true,
                'include_analysis' => true,
            ]);
            
            // Generate investigation report
            $report = $this->reporting->generateInvestigationReport($investigation);
            
            $this->logging->info('Activity investigation completed', [
                'criteria' => $criteria,
                'investigation' => $investigation,
                'report' => $report,
            ]);
            
            return [
                'investigation' => $investigation,
                'report' => $report,
            ];
            
        } catch (\Exception $e) {
            $this->logging->error('Activity investigation failed', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Detect suspicious activity
     * 
     * @param array $activities System activities
     * @return array|null Suspicious activities
     */
    protected function detectSuspiciousActivity(array $activities): ?array
    {
        $suspicious = [];
        foreach ($activities as $activity) {
            if ($this->isSuspicious($activity)) {
                $suspicious[] = $activity;
            }
        }
        
        return !empty($suspicious) ? $suspicious : null;
    }

    /**
     * Check if activity is suspicious
     * 
     * @param array $activity System activity
     * @return bool Whether activity is suspicious
     */
    protected function isSuspicious(array $activity): bool
    {
        // Check for unusual patterns
        if ($this->hasUnusualPattern($activity)) {
            return true;
        }
        
        // Check for security violations
        if ($this->hasSecurityViolation($activity)) {
            return true;
        }
        
        // Check for compliance violations
        if ($this->hasComplianceViolation($activity)) {
            return true;
        }
        
        return false;
    }

    /**
     * Check for unusual patterns
     * 
     * @param array $activity System activity
     * @return bool Whether activity has unusual pattern
     */
    protected function hasUnusualPattern(array $activity): bool
    {
        return $this->monitoring->detectUnusualPattern($activity);
    }

    /**
     * Check for security violations
     * 
     * @param array $activity System activity
     * @return bool Whether activity has security violation
     */
    protected function hasSecurityViolation(array $activity): bool
    {
        return $this->monitoring->detectSecurityViolation($activity);
    }

    /**
     * Check for compliance violations
     * 
     * @param array $activity System activity
     * @return bool Whether activity has compliance violation
     */
    protected function hasComplianceViolation(array $activity): bool
    {
        return $this->monitoring->detectComplianceViolation($activity);
    }

    /**
     * Validate access permissions
     * 
     * @param string $permission Permission to check
     * @throws \Exception If access is denied
     */
    protected function validateAccess(string $permission): void
    {
        if (!$this->accessControl->hasPermission($permission)) {
            throw new \Exception("Access denied: {$permission}");
        }
    }
} 