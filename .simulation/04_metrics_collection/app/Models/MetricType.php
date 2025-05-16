<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetricType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'unit',
        'data_type',
        'validation_rules',
        'aggregation_methods'
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'aggregation_methods' => 'array'
    ];

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function aggregations(): HasMany
    {
        return $this->hasMany(MetricAggregation::class);
    }

    public function validateValue($value): bool
    {
        // Basic type validation
        if (!$this->validateDataType($value)) {
            return false;
        }

        // Apply custom validation rules
        foreach ($this->validation_rules as $rule) {
            if (!$this->applyValidationRule($rule, $value)) {
                return false;
            }
        }

        return true;
    }

    private function validateDataType($value): bool
    {
        return match ($this->data_type) {
            'integer' => is_int($value),
            'float' => is_float($value) || is_numeric($value),
            'boolean' => is_bool($value),
            'string' => is_string($value),
            default => false,
        };
    }

    private function applyValidationRule(array $rule, $value): bool
    {
        return match ($rule['type']) {
            'min' => $value >= $rule['value'],
            'max' => $value <= $rule['value'],
            'regex' => preg_match($rule['pattern'], $value),
            default => true,
        };
    }
} 