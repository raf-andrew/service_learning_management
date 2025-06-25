<?php

namespace App\Modules\Soc2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Certification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'soc2_certifications';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'certification_type', // Type I or Type II
        'status', // draft, in_progress, certified, expired, revoked
        'start_date',
        'end_date',
        'auditor_name',
        'auditor_contact',
        'scope_description',
        'trust_service_criteria', // JSON array of TSC
        'compliance_score',
        'security_score',
        'availability_score',
        'processing_integrity_score',
        'confidentiality_score',
        'privacy_score',
        'metadata',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'trust_service_criteria' => 'array',
        'metadata' => 'array',
        'compliance_score' => 'decimal:2',
        'security_score' => 'decimal:2',
        'availability_score' => 'decimal:2',
        'processing_integrity_score' => 'decimal:2',
        'confidentiality_score' => 'decimal:2',
        'privacy_score' => 'decimal:2',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'metadata',
    ];

    /**
     * Get the control assessments for this certification.
     */
    public function controlAssessments(): HasMany
    {
        return $this->hasMany(ControlAssessment::class);
    }

    /**
     * Get the risk assessments for this certification.
     */
    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * Get the compliance reports for this certification.
     */
    public function complianceReports(): HasMany
    {
        return $this->hasMany(ComplianceReport::class);
    }

    /**
     * Get the audit logs for this certification.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'model_id')
            ->where('model_type', self::class);
    }

    /**
     * Get the user who created this certification.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'created_by');
    }

    /**
     * Get the user who last updated this certification.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\Models\User'), 'updated_by');
    }

    /**
     * Scope a query to only include active certifications.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'certified')
                    ->where('end_date', '>', now());
    }

    /**
     * Scope a query to only include expired certifications.
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    /**
     * Scope a query to only include certifications by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('certification_type', $type);
    }

    /**
     * Check if the certification is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'certified' && $this->end_date->isFuture();
    }

    /**
     * Check if the certification is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Check if the certification is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get the overall compliance score.
     */
    public function getOverallScore(): float
    {
        $scores = [
            $this->security_score,
            $this->availability_score,
            $this->processing_integrity_score,
            $this->confidentiality_score,
            $this->privacy_score,
        ];

        return round(array_sum($scores) / count($scores), 2);
    }

    /**
     * Get the days until expiration.
     */
    public function getDaysUntilExpiration(): int
    {
        return max(0, $this->end_date->diffInDays(now()));
    }

    /**
     * Get the certification age in days.
     */
    public function getAgeInDays(): int
    {
        return $this->start_date->diffInDays(now());
    }

    /**
     * Get the trust service criteria as a formatted string.
     */
    public function getTrustServiceCriteriaString(): string
    {
        if (empty($this->trust_service_criteria)) {
            return 'None specified';
        }

        return implode(', ', $this->trust_service_criteria);
    }

    /**
     * Check if the certification meets minimum compliance thresholds.
     */
    public function meetsComplianceThresholds(): bool
    {
        $thresholds = config('modules.modules.soc2.validation.thresholds', [
            'compliance_score' => 90,
            'security_score' => 85,
            'availability_score' => 99.5,
        ]);

        return $this->compliance_score >= $thresholds['compliance_score'] &&
               $this->security_score >= $thresholds['security_score'] &&
               $this->availability_score >= $thresholds['availability_score'];
    }

    /**
     * Get the certification status for display.
     */
    public function getStatusDisplay(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get the certification type for display.
     */
    public function getTypeDisplay(): string
    {
        return "SOC2 Type {$this->certification_type}";
    }
} 