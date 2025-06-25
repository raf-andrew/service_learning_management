<?php

namespace App\Models\GitHub;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Config extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'is_encrypted',
        'description'
    ];

    protected $casts = [
        'is_encrypted' => 'boolean'
    ];

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            return Crypt::decryptString($value);
        }
        return $value;
    }

    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public static function getToken()
    {
        return static::where('key', 'GITHUB_TOKEN')->first()?->value;
    }

    public static function getRepository()
    {
        return static::where('key', 'GITHUB_REPOSITORY')->first()?->value;
    }
} 