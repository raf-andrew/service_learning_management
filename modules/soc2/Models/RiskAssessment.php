<?php

namespace App\Modules\Soc2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RiskAssessment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'soc2_risk_assessments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'certification_id',
        'risk_id',
        'risk_name',
        'risk_description',
        'risk_category', // Security, Availability, Processing Integrity, Confidentiality, Privacy
        'risk_level', // low, medium, high, critical
        'likelihood', // 1-5 scale
        'impact', // 1-5 scale
        'risk_score', // calculated from likelihood * impact
        'assessment_date',
        'next_review_date',
        'mitigation_status', // not_mitigated, partially_mitigated, fully_mitigated
        'mitigation_controls',
        'mitigation_effectiveness', // 0-100
        'residual_risk_level',
        'residual_risk_score',
        'assessor_name',
        'assessor_notes',
        'stakeholders',
        'business_impact',
        'technical_impact',
        'compliance_impact',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assessment_date' => 'date',
        'next_review_date' => 'date',
        'risk_score' => 'integer',
        'residual_risk_score' => 'integer',
        'likelihood' => 'integer',
        'impact' => 'integer',
        'mitigation_effectiveness' => 'decimal:2',
        'stakeholders' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
        'assessor_notes',
    ];

    /**
     * Get the certification that owns this risk assessment.
     */
    public function certification(): BelongsTo
    {
        return $this->belongsTo(Certification::class);
    }

    /**
     * Get the audit logs for this risk assessment.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Scope a query to only include high-risk assessments.
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope a query to only include risks by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('risk_category', $category);
    }

    /**
     * Scope a query to only include risks by level.
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    /**
     * Scope a query to only include unmitigated risks.
     */
    public function scopeUnmitigated($query)
    {
        return $query->where('mitigation_status', 'not_mitigated');
    }

    /**
     * Scope a query to only include overdue reviews.
     */
    public function scopeOverdueReview($query)
    {
        return $query->where('next_review_date', '<', now());
    }

    /**
     * Check if the risk is high or critical.
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, ['high', 'critical']);
    }

    /**
     * Check if the risk is critical.
     */
    public function isCritical(): bool
    {
        return $this->risk_level === 'critical';
    }

    /**
     * Check if the risk is mitigated.
     */
    public function isMitigated(): bool
    {
        return $this->mitigation_status === 'fully_mitigated';
    }

    /**
     * Check if the risk is partially mitigated.
     */
    public function isPartiallyMitigated(): bool
    {
        return $this->mitigation_status === 'partially_mitigated';
    }

    /**
     * Check if the risk is not mitigated.
     */
    public function isNotMitigated(): bool
    {
        return $this->mitigation_status === 'not_mitigated';
    }

    /**
     * Check if the risk review is overdue.
     */
    public function isOverdueReview(): bool
    {
        return $this->next_review_date && $this->next_review_date->isPast();
    }

    /**
     * Calculate the risk score based on likelihood and impact.
     */
    public function calculateRiskScore(): int
    {
        return $this->likelihood * $this->impact;
    }

    /**
     * Get the risk level based on risk score.
     */
    public function getRiskLevelFromScore(): string
    {
        $score = $this->risk_score;

        if ($score >= 15) {
            return 'critical';
        } elseif ($score >= 10) {
            return 'high';
        } elseif ($score >= 6) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get the days until next review.
     */
    public function getDaysUntilReview(): ?int
    {
        if (!$this->next_review_date) {
            return null;
        }

        return $this->next_review_date->diffInDays(now());
    }

    /**
     * Get the assessment age in days.
     */
    public function getAssessmentAgeInDays(): int
    {
        return $this->assessment_date->diffInDays(now());
    }

    /**
     * Get the risk category for display.
     */
    public function getCategoryDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->risk_category));
    }

    /**
     * Get the risk level for display.
     */
    public function getLevelDisplay(): string
    {
        return ucfirst($this->risk_level);
    }

    /**
     * Get the mitigation status for display.
     */
    public function getMitigationStatusDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->mitigation_status));
    }

    /**
     * Get the likelihood description.
     */
    public function getLikelihoodDescription(): string
    {
        $descriptions = [
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
        ];

        return $descriptions[$this->likelihood] ?? 'Unknown';
    }

    /**
     * Get the impact description.
     */
    public function getImpactDescription(): string
    {
        $descriptions = [
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
        ];

        return $descriptions[$this->impact] ?? 'Unknown';
    }

    /**
     * Check if the risk requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this->isCritical() || 
               ($this->isHighRisk() && $this->isNotMitigated()) ||
               $this->isOverdueReview();
    }

    /**
     * Get the stakeholders as a formatted string.
     */
    public function getStakeholdersString(): string
    {
        if (empty($this->stakeholders)) {
            return 'None specified';
        }

        return implode(', ', $this->stakeholders);
    }

    /**
     * Check if the risk has been effectively mitigated.
     */
    public function isEffectivelyMitigated(): bool
    {
        return $this->mitigation_effectiveness >= 80;
    }

    /**
     * Get the overall impact level.
     */
    public function getOverallImpactLevel(): string
    {
        $impacts = [
            'business_impact' => $this->business_impact,
            'technical_impact' => $this->technical_impact,
            'compliance_impact' => $this->compliance_impact,
        ];

        $maxImpact = max(array_filter($impacts));
        
        if ($maxImpact >= 4) {
            return 'high';
        } elseif ($maxImpact >= 3) {
            return 'medium';
        } else {
            return 'low';
        }
    }
} 