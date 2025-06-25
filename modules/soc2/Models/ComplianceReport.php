<?php

namespace App\Modules\Soc2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplianceReport extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'soc2_compliance_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'certification_id',
        'report_type', // initial, periodic, final, exception
        'report_period_start',
        'report_period_end',
        'report_date',
        'status', // draft, in_review, approved, rejected, published
        'overall_compliance_score',
        'security_compliance_score',
        'availability_compliance_score',
        'processing_integrity_compliance_score',
        'confidentiality_compliance_score',
        'privacy_compliance_score',
        'total_controls_assessed',
        'compliant_controls',
        'non_compliant_controls',
        'partially_compliant_controls',
        'not_applicable_controls',
        'total_risks_assessed',
        'high_risks',
        'medium_risks',
        'low_risks',
        'critical_findings',
        'major_findings',
        'minor_findings',
        'recommendations',
        'executive_summary',
        'detailed_findings',
        'remediation_plan',
        'next_review_date',
        'approved_by',
        'approved_at',
        'reviewer_notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'report_period_start' => 'date',
        'report_period_end' => 'date',
        'report_date' => 'date',
        'approved_at' => 'datetime',
        'next_review_date' => 'date',
        'overall_compliance_score' => 'decimal:2',
        'security_compliance_score' => 'decimal:2',
        'availability_compliance_score' => 'decimal:2',
        'processing_integrity_compliance_score' => 'decimal:2',
        'confidentiality_compliance_score' => 'decimal:2',
        'privacy_compliance_score' => 'decimal:2',
        'total_controls_assessed' => 'integer',
        'compliant_controls' => 'integer',
        'non_compliant_controls' => 'integer',
        'partially_compliant_controls' => 'integer',
        'not_applicable_controls' => 'integer',
        'total_risks_assessed' => 'integer',
        'high_risks' => 'integer',
        'medium_risks' => 'integer',
        'low_risks' => 'integer',
        'critical_findings' => 'integer',
        'major_findings' => 'integer',
        'minor_findings' => 'integer',
        'recommendations' => 'array',
        'detailed_findings' => 'array',
        'remediation_plan' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
        'reviewer_notes',
    ];

    /**
     * Get the certification that owns this compliance report.
     */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /**
     * Get the user who approved this report.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'approved_by');
    }

    /**
     * Get the audit logs for this compliance report.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Scope a query to only include approved reports.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include published reports.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include reports by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope a query to only include reports by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include reports within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include reports with compliance score above threshold.
     */
    public function scopeAboveComplianceThreshold($query, float $threshold = 80)
    {
        return $query->where('overall_compliance_score', '>=', $threshold);
    }

    /**
     * Check if the report is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the report is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the report is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the report is in review.
     */
    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }

    /**
     * Check if the report is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if the report meets minimum compliance threshold.
     */
    public function meetsComplianceThreshold(): bool
    {
        $threshold = config('modules.modules.soc2.validation.thresholds.overall_compliance', 80);
        return $this->overall_compliance_score >= $threshold;
    }

    /**
     * Get the compliance level based on overall score.
     */
    public function getComplianceLevel(): string
    {
        if ($this->overall_compliance_score >= 95) {
            return 'excellent';
        } elseif ($this->overall_compliance_score >= 90) {
            return 'good';
        } elseif ($this->overall_compliance_score >= 80) {
            return 'fair';
        } elseif ($this->overall_compliance_score >= 70) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Get the report type for display.
     */
    public function getTypeDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->report_type));
    }

    /**
     * Get the status for display.
     */
    public function getStatusDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get the report period as a formatted string.
     */
    public function getPeriodDisplay(): string
    {
        return $this->report_period_start->format('M j, Y') . ' - ' . $this->report_period_end->format('M j, Y');
    }

    /**
     * Get the compliance percentage.
     */
    public function getCompliancePercentage(): float
    {
        if ($this->total_controls_assessed === 0) {
            return 0;
        }

        return round(($this->compliant_controls / $this->total_controls_assessed) * 100, 2);
    }

    /**
     * Get the non-compliance percentage.
     */
    public function getNonCompliancePercentage(): float
    {
        if ($this->total_controls_assessed === 0) {
            return 0;
        }

        return round(($this->non_compliant_controls / $this->total_controls_assessed) * 100, 2);
    }

    /**
     * Get the total findings count.
     */
    public function getTotalFindings(): int
    {
        return $this->critical_findings + $this->major_findings + $this->minor_findings;
    }

    /**
     * Get the risk distribution as percentages.
     */
    public function getRiskDistribution(): array
    {
        if ($this->total_risks_assessed === 0) {
            return [
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ];
        }

        return [
            'high' => round(($this->high_risks / $this->total_risks_assessed) * 100, 2),
            'medium' => round(($this->medium_risks / $this->total_risks_assessed) * 100, 2),
            'low' => round(($this->low_risks / $this->total_risks_assessed) * 100, 2),
        ];
    }

    /**
     * Get the days until next review.
     */
    public function getDaysUntilNextReview(): ?int
    {
        if (!$this->next_review_date) {
            return null;
        }

        return $this->next_review_date->diffInDays(now());
    }

    /**
     * Get the report age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->report_date->diffInDays(now());
    }

    /**
     * Check if the report has critical findings.
     */
    public function hasCriticalFindings(): bool
    {
        return $this->critical_findings > 0;
    }

    /**
     * Check if the report has major findings.
     */
    public function hasMajorFindings(): bool
    {
        return $this->major_findings > 0;
    }

    /**
     * Check if the report requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this->hasCriticalFindings() || 
               $this->overall_compliance_score < 70 ||
               $this->isRejected();
    }

    /**
     * Get the recommendations as a formatted string.
     */
    public function getRecommendationsString(): string
    {
        if (empty($this->recommendations)) {
            return 'No recommendations provided';
        }

        return implode('; ', $this->recommendations);
    }

    /**
     * Get the detailed findings summary.
     */
    public function getFindingsSummary(): string
    {
        $findings = [];
        
        if ($this->critical_findings > 0) {
            $findings[] = "{$this->critical_findings} critical";
        }
        
        if ($this->major_findings > 0) {
            $findings[] = "{$this->major_findings} major";
        }
        
        if ($this->minor_findings > 0) {
            $findings[] = "{$this->minor_findings} minor";
        }
        
        if (empty($findings)) {
            return 'No findings';
        }
        
        return implode(', ', $findings) . ' finding(s)';
    }

    /**
     * Check if the report is overdue for review.
     */
    public function isOverdueReview(): bool
    {
        return $this->next_review_date && $this->next_review_date->isPast();
    }
} 