<?php

namespace App\Modules\Soc2\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Modules\Soc2\Models\Certification;
use App\Modules\Soc2\Models\ComplianceReport;
use App\Modules\Soc2\Models\ControlAssessment;
use App\Modules\Soc2\Models\RiskAssessment;
use App\Modules\Soc2\Models\AuditLog;

/**
 * SOC2 Report Generation and Export Service
 * 
 * Provides comprehensive report generation, export capabilities, and compliance analysis
 * for SOC2 Type I and Type II certifications.
 */
class ReportService
{
    /**
     * Generate a comprehensive compliance report
     */
    public function generateReport(
        Certification $certification,
        string $reportType,
        string $startDate,
        string $endDate
    ): array {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Gather data for the report period
        $controlAssessments = $certification->controlAssessments()
            ->whereBetween('assessment_date', [$start, $end])
            ->get();

        $riskAssessments = $certification->riskAssessments()
            ->whereBetween('assessment_date', [$start, $end])
            ->get();

        $auditLogs = AuditLog::where('model_type', Certification::class)
            ->where('model_id', $certification->id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Calculate control scores
        $controlScores = $this->calculateControlScores($controlAssessments);

        // Calculate risk assessments
        $riskData = $this->analyzeRiskAssessments($riskAssessments);

        // Calculate exceptions
        $exceptions = $this->identifyExceptions($controlAssessments, $riskAssessments);

        // Generate recommendations
        $recommendations = $this->generateRecommendations($controlAssessments, $riskAssessments, $exceptions);

        // Calculate overall compliance score
        $overallScore = $this->calculateOverallScore($controlScores, $riskData);

        return [
            'summary' => [
                'report_type' => $reportType,
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
                'certification_id' => $certification->id,
                'certification_type' => $certification->certification_type,
                'overall_score' => $overallScore,
                'total_controls' => $controlAssessments->count(),
                'total_risks' => $riskAssessments->count(),
                'total_audit_events' => $auditLogs->count(),
            ],
            'detailed' => [
                'control_assessments' => $controlAssessments->toArray(),
                'risk_assessments' => $riskAssessments->toArray(),
                'audit_logs' => $auditLogs->toArray(),
            ],
            'overall_score' => $overallScore,
            'control_scores' => $controlScores,
            'risk_assessments' => $riskData,
            'exceptions' => $exceptions,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Export a report to various formats
     */
    public function exportReport(ComplianceReport $report, string $format, ?string $outputPath = null): string
    {
        $filename = $this->generateFilename($report, $format);
        $filePath = $outputPath ?: storage_path('modules/soc2/reports/' . $filename);

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($report, $filePath);
            case 'html':
                return $this->exportToHtml($report, $filePath);
            case 'json':
                return $this->exportToJson($report, $filePath);
            case 'xml':
                return $this->exportToXml($report, $filePath);
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    /**
     * Calculate control scores by category
     */
    private function calculateControlScores($controlAssessments): array
    {
        $scores = [];
        $categories = [];

        foreach ($controlAssessments as $assessment) {
            $controlId = $assessment->control_id;
            $score = $assessment->compliance_score;

            $scores[$controlId] = $score;

            // Group by category
            $category = $this->getControlCategory($controlId);
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $score;
        }

        // Calculate category averages
        foreach ($categories as $category => $categoryScores) {
            $scores[$category . '_avg'] = round(array_sum($categoryScores) / count($categoryScores), 2);
        }

        return $scores;
    }

    /**
     * Analyze risk assessments
     */
    private function analyzeRiskAssessments($riskAssessments): array
    {
        $analysis = [
            'total_risks' => $riskAssessments->count(),
            'risk_levels' => [
                'low' => 0,
                'medium' => 0,
                'high' => 0,
                'critical' => 0,
            ],
            'risk_categories' => [],
            'mitigated_risks' => 0,
            'overdue_reviews' => 0,
        ];

        foreach ($riskAssessments as $risk) {
            $analysis['risk_levels'][$risk->risk_level]++;
            
            if (!isset($analysis['risk_categories'][$risk->risk_category])) {
                $analysis['risk_categories'][$risk->risk_category] = 0;
            }
            $analysis['risk_categories'][$risk->risk_category]++;

            if ($risk->isMitigated()) {
                $analysis['mitigated_risks']++;
            }

            if ($risk->isOverdueReview()) {
                $analysis['overdue_reviews']++;
            }
        }

        return $analysis;
    }

    /**
     * Identify exceptions and issues
     */
    private function identifyExceptions($controlAssessments, $riskAssessments): array
    {
        $exceptions = [];

        // Control assessment exceptions
        foreach ($controlAssessments as $assessment) {
            if (!$assessment->isCompliant()) {
                $exceptions[] = "Control {$assessment->control_id} is non-compliant (Score: {$assessment->compliance_score}%)";
            }

            if ($assessment->exceptions_found) {
                $exceptions[] = "Control {$assessment->control_id} has exceptions: {$assessment->exceptions_description}";
            }

            if ($assessment->isOverdueRemediation()) {
                $exceptions[] = "Control {$assessment->control_id} has overdue remediation (Due: {$assessment->remediation_due_date})";
            }
        }

        // Risk assessment exceptions
        foreach ($riskAssessments as $risk) {
            if ($risk->risk_level === 'critical' && !$risk->isMitigated()) {
                $exceptions[] = "Critical risk '{$risk->risk_name}' is not mitigated";
            }

            if ($risk->isOverdueReview()) {
                $exceptions[] = "Risk '{$risk->risk_name}' has overdue review (Due: {$risk->review_due_date})";
            }
        }

        return $exceptions;
    }

    /**
     * Generate recommendations based on assessments
     */
    private function generateRecommendations($controlAssessments, $riskAssessments, $exceptions): array
    {
        $recommendations = [];

        // Control-based recommendations
        $lowScoreControls = $controlAssessments->filter(fn($c) => $c->compliance_score < 80);
        foreach ($lowScoreControls as $control) {
            $recommendations[] = "Improve control {$control->control_id} compliance (Current: {$control->compliance_score}%)";
        }

        // Risk-based recommendations
        $unmitigatedRisks = $riskAssessments->filter(fn($r) => !$r->isMitigated() && $r->risk_level !== 'low');
        foreach ($unmitigatedRisks as $risk) {
            $recommendations[] = "Implement mitigation for {$risk->risk_level} risk '{$risk->risk_name}'";
        }

        // Exception-based recommendations
        if (count($exceptions) > 0) {
            $recommendations[] = "Address " . count($exceptions) . " identified exceptions";
        }

        return $recommendations;
    }

    /**
     * Calculate overall compliance score
     */
    private function calculateOverallScore(array $controlScores, array $riskData): float
    {
        $controlScore = 0;
        $riskScore = 0;

        // Calculate control score (average of all control scores)
        if (!empty($controlScores)) {
            $controlValues = array_filter($controlScores, fn($key) => !str_ends_with($key, '_avg'), ARRAY_FILTER_USE_KEY);
            $controlScore = !empty($controlValues) ? array_sum($controlValues) / count($controlValues) : 0;
        }

        // Calculate risk score (based on risk levels and mitigation)
        if ($riskData['total_risks'] > 0) {
            $riskScore = 100 - (
                ($riskData['risk_levels']['critical'] * 20) +
                ($riskData['risk_levels']['high'] * 10) +
                ($riskData['risk_levels']['medium'] * 5)
            );
            $riskScore = max(0, $riskScore);
        }

        // Weighted average (70% controls, 30% risks)
        return round(($controlScore * 0.7) + ($riskScore * 0.3), 2);
    }

    /**
     * Get control category based on control ID
     */
    private function getControlCategory(string $controlId): string
    {
        $categories = [
            'CC' => 'Common Criteria',
            'A' => 'Availability',
            'C' => 'Confidentiality',
            'I' => 'Integrity',
            'P' => 'Privacy',
        ];

        foreach ($categories as $prefix => $category) {
            if (str_starts_with($controlId, $prefix)) {
                return $category;
            }
        }

        return 'Other';
    }

    /**
     * Generate filename for export
     */
    private function generateFilename(ComplianceReport $report, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportType = strtolower(str_replace(' ', '_', $report->report_type));
        
        return "soc2_report_{$reportType}_{$timestamp}.{$format}";
    }

    /**
     * Export report to PDF format
     */
    private function exportToPdf(ComplianceReport $report, string $filePath): string
    {
        $content = $this->generatePdfContent($report);
        
        // For now, create a simple text-based PDF
        // In production, use a proper PDF library like Dompdf or mPDF
        file_put_contents($filePath, $content);
        
        return $filePath;
    }

    /**
     * Export report to HTML format
     */
    private function exportToHtml(ComplianceReport $report, string $filePath): string
    {
        $content = $this->generateHtmlContent($report);
        file_put_contents($filePath, $content);
        
        return $filePath;
    }

    /**
     * Export report to JSON format
     */
    private function exportToJson(ComplianceReport $report, string $filePath): string
    {
        $data = [
            'report_id' => $report->id,
            'report_type' => $report->report_type,
            'generated_at' => now()->toISOString(),
            'data' => json_decode($report->report_data, true),
        ];
        
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        
        return $filePath;
    }

    /**
     * Export report to XML format
     */
    private function exportToXml(ComplianceReport $report, string $filePath): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><soc2_report></soc2_report>');
        
        $xml->addChild('report_id', $report->id);
        $xml->addChild('report_type', $report->report_type);
        $xml->addChild('generated_at', now()->toISOString());
        
        $data = json_decode($report->report_data, true);
        $this->arrayToXml($data, $xml);
        
        $xml->asXML($filePath);
        
        return $filePath;
    }

    /**
     * Generate PDF content
     */
    private function generatePdfContent(ComplianceReport $report): string
    {
        $data = json_decode($report->report_data, true);
        
        $content = "SOC2 Compliance Report\n";
        $content .= "Report Type: {$report->report_type}\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        if (isset($data['summary'])) {
            $content .= "Summary:\n";
            foreach ($data['summary'] as $key => $value) {
                $content .= ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
            }
        }
        
        return $content;
    }

    /**
     * Generate HTML content
     */
    private function generateHtmlContent(ComplianceReport $report): string
    {
        $data = json_decode($report->report_data, true);
        
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<title>SOC2 Compliance Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>SOC2 Compliance Report</h1>\n";
        $html .= "<p><strong>Report Type:</strong> {$report->report_type}</p>\n";
        $html .= "<p><strong>Generated:</strong> " . now()->format('Y-m-d H:i:s') . "</p>\n";
        
        if (isset($data['summary'])) {
            $html .= "<h2>Summary</h2>\n<table>\n";
            foreach ($data['summary'] as $key => $value) {
                $html .= "<tr><td>" . ucfirst(str_replace('_', ' ', $key)) . "</td><td>{$value}</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }

    /**
     * Convert array to XML
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
} 