<?php

namespace MCP\Agentic\Agents\Security;

use MCP\Agentic\Agents\BaseAgent;
use MCP\Agentic\Core\Services\AccessControl;
use MCP\Agentic\Core\Services\Logging;
use MCP\Agentic\Core\Services\Monitoring;
use MCP\Agentic\Core\Services\Reporting;
use MCP\Agentic\Core\Services\Alerting;

/**
 * Access Control Agent
 * 
 * Manages access control operations including permission management,
 * access validation, pattern monitoring, access reporting, and violation detection.
 * 
 * @package MCP\Agentic\Agents\Security
 */
class AccessControlAgent extends BaseAgent
{
    protected AccessControl $accessControl;
    protected Logging $logging;
    protected Monitoring $monitoring;
    protected Reporting $reporting;
    protected Alerting $alerting;

    /**
     * Initialize the access control agent
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
     * Manage permissions
     * 
     * @param array $permissions Permissions to manage
     * @return array Management results
     */
    public function managePermissions(array $permissions): array
    {
        $this->validateAccess('access.manage');
        
        $results = [];
        foreach ($permissions as $permission) {
            $result = $this->accessControl->managePermission([
                'action' => $permission['action'],
                'permission' => $permission['permission'],
                'roles' => $permission['roles'] ?? [],
                'users' => $permission['users'] ?? [],
                'resources' => $permission['resources'] ?? [],
                'conditions' => $permission['conditions'] ?? [],
            ]);
            
            $results[] = $result;
            
            $this->logging->info('Permission managed', [
                'permission' => $permission,
                'result' => $result,
            ]);
        }
        
        return $results;
    }

    /**
     * Validate access request
     * 
     * @param array $request Access request to validate
     * @return array Validation results
     */
    public function validateAccess(array $request): array
    {
        $this->validateAccess('access.validate');
        
        $this->logging->info('Validating access request', [
            'request' => $request,
        ]);
        
        try {
            $result = $this->accessControl->validateAccess([
                'user' => $request['user'],
                'permission' => $request['permission'],
                'resource' => $request['resource'] ?? null,
                'context' => $request['context'] ?? [],
            ]);
            
            // Monitor access patterns
            $this->monitorAccessPattern($request, $result);
            
            // Check for violations
            if (!$result['allowed']) {
                $this->detectViolation($request, $result);
            }
            
            $this->logging->info('Access request validated', [
                'request' => $request,
                'result' => $result,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logging->error('Access validation failed', [
                'request' => $request,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Monitor access patterns
     * 
     * @param array $request Access request
     * @param array $result Validation result
     */
    protected function monitorAccessPattern(array $request, array $result): void
    {
        $pattern = [
            'user' => $request['user'],
            'permission' => $request['permission'],
            'resource' => $request['resource'] ?? null,
            'timestamp' => time(),
            'result' => $result['allowed'],
        ];
        
        $this->monitoring->trackAccessPattern($pattern);
        
        // Check for suspicious patterns
        $suspicious = $this->monitoring->detectSuspiciousPattern($pattern);
        if ($suspicious) {
            $this->alerting->alert('Suspicious access pattern detected', [
                'pattern' => $pattern,
                'suspicious' => $suspicious,
            ]);
        }
    }

    /**
     * Detect access violations
     * 
     * @param array $request Access request
     * @param array $result Validation result
     */
    protected function detectViolation(array $request, array $result): void
    {
        $violation = [
            'user' => $request['user'],
            'permission' => $request['permission'],
            'resource' => $request['resource'] ?? null,
            'timestamp' => time(),
            'reason' => $result['reason'],
        ];
        
        $this->monitoring->trackViolation($violation);
        
        // Generate alert for violation
        $this->alerting->alert('Access violation detected', [
            'violation' => $violation,
        ]);
        
        // Generate violation report
        $this->reporting->generateViolationReport($violation);
    }

    /**
     * Generate access report
     * 
     * @param array $filters Report filters
     * @return array Access report
     */
    public function generateAccessReport(array $filters = []): array
    {
        $this->validateAccess('access.report');
        
        $this->logging->info('Generating access report', [
            'filters' => $filters,
        ]);
        
        try {
            $report = $this->reporting->generateAccessReport([
                'filters' => $filters,
                'include_patterns' => true,
                'include_violations' => true,
                'include_metrics' => true,
            ]);
            
            $this->logging->info('Access report generated', [
                'filters' => $filters,
                'report' => $report,
            ]);
            
            return $report;
            
        } catch (\Exception $e) {
            $this->logging->error('Access report generation failed', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
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