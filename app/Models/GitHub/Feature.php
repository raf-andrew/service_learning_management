<?php

namespace App\Models\GitHub;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'enabled',
        'conditions',
        'description'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'conditions' => 'array'
    ];

    public function isEnabled()
    {
        if (!$this->enabled) {
            return false;
        }

        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition($condition)
    {
        $type = $condition['type'] ?? null;
        $value = $condition['value'] ?? null;

        switch ($type) {
            case 'environment':
                return app()->environment($value);
            case 'config':
                return config($condition['key']) === $value;
            case 'github':
                return $this->evaluateGitHubCondition($condition);
            default:
                return false;
        }
    }

    private function evaluateGitHubCondition($condition)
    {
        $action = $condition['action'] ?? null;
        $value = $condition['value'] ?? null;

        switch ($action) {
            case 'branch':
                return Config::getRepository() === $value;
            case 'token':
                return Config::getToken() !== null;
            default:
                return false;
        }
    }
} 