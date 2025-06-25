<?php

namespace App\Modules\Soc2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ControlAssessment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'soc2_control_assessments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'certification_id',
        'control_id',
        'control_name',
        'control_description',
        'control_category', // Security, Availability, Processing Integrity, Confidentiality, Privacy
        'assessment_date',
        'compliance_score', // 0-100
        'assessment_status', // compliant, non_compliant, partially_compliant, not_applicable
        'exceptions_found',
        'exceptions_description',
        'remediation_required',
        'remediation_deadline',
        'remediation_status', // not_started, in_progress, completed, overdue
        'assessor_name',
        'assessor_notes',
        'evidence_provided',
        'evidence_location',
        'testing_procedures',
        'testing_results',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assessment_date' => 'date',
        'remediation_deadline' => 'date',
        'exceptions_found' => 'boolean',
        'remediation_required' => 'boolean',
        'compliance_score' => 'decimal:2',
        'metadata' => 'array',
        'testing_results' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
        'assessor_notes',
    ];

    /**
     * Get the certification that owns this control assessment.
     */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /**
     * Get the evidence files for this control assessment.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Get the audit logs for this control assessment.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Scope a query to only include compliant controls.
     */
    public function scopeCompliant($query)
    {
        return $query->where('assessment_status', 'compliant');
    }

    /**
     * Scope a query to only include non-compliant controls.
     */
    public function scopeNonCompliant($query)
    {
        return $query->where('assessment_status', 'non_compliant');
    }

    /**
     * Scope a query to only include controls by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('control_category', $category);
    }

    /**
     * Scope a query to only include controls requiring remediation.
     */
    public function scopeRequiringRemediation($query)
    {
        return $query->where('remediation_required', true);
    }

    /**
     * Scope a query to only include overdue remediations.
     */
    public function scopeOverdueRemediation($query)
    {
        return $query->where('remediation_deadline', '<', now())
                    ->where('remediation_status', '!=', 'completed');
    }

    /**
     * Check if the control is compliant.
     */
    public function isCompliant(): bool
    {
        return $this->assessment_status === 'compliant';
    }

    /**
     * Check if the control is non-compliant.
     */
    public function isNonCompliant(): bool
    {
        return $this->assessment_status === 'non_compliant';
    }

    /**
     * Check if the control is partially compliant.
     */
    public function isPartiallyCompliant(): bool
    {
        return $this->assessment_status === 'partially_compliant';
    }

    /**
     * Check if the control is not applicable.
     */
    public function isNotApplicable(): bool
    {
        return $this->assessment_status === 'not_applicable';
    }

    /**
     * Check if remediation is overdue.
     */
    public function isOverdueRemediation(): bool
    {
        return $this->remediation_required &&
               $this->remediation_deadline &&
               $this->remediation_deadline->isPast() &&
               $this->remediation_status !== 'completed';
    }

    /**
     * Check if remediation is in progress.
     */
    public function isRemediationInProgress(): bool
    {
        return $this->remediation_status === 'in_progress';
    }

    /**
     * Check if remediation is completed.
     */
    public function isRemediationCompleted(): bool
    {
        return $this->remediation_status === 'completed';
    }

    /**
     * Get the compliance level based on score.
     */
    public function getComplianceLevel(): string
    {
        if ($this->compliance_score >= 90) {
            return 'excellent';
        } elseif ($this->compliance_score >= 80) {
            return 'good';
        } elseif ($this->compliance_score >= 70) {
            return 'fair';
        } elseif ($this->compliance_score >= 60) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Get the days until remediation deadline.
     */
    public function getDaysUntilRemediationDeadline(): ?int
    {
        if (!$this->remediation_deadline) {
            return null;
        }

        return $this->remediation_deadline->diffInDays(now());
    }

    /**
     * Get the assessment age in days.
     */
    public function getAssessmentAgeInDays(): int
    {
        return $this->assessment_date->diffInDays(now());
    }

    /**
     * Get the control category for display.
     */
    public function getCategoryDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->control_category));
    }

    /**
     * Get the assessment status for display.
     */
    public function getStatusDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->assessment_status));
    }

    /**
     * Get the remediation status for display.
     */
    public function getRemediationStatusDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->remediation_status));
    }

    /**
     * Check if the control meets minimum compliance threshold.
     */
    public function meetsComplianceThreshold(): bool
    {
        $threshold = config('modules.modules.soc2.validation.thresholds.control_compliance', 80);
        return $this->compliance_score >= $threshold;
    }

    /**
     * Get the risk level based on compliance score and exceptions.
     */
    public function getRiskLevel(): string
    {
        if ($this->exceptions_found && $this->compliance_score < 60) {
            return 'critical';
        } elseif ($this->exceptions_found || $this->compliance_score < 70) {
            return 'high';
        } elseif ($this->compliance_score < 80) {
            return 'medium';
        } else {
            return 'low';
        }
    }
} 