<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'avatar',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'bio',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function updateAvatar($avatar)
    {
        if ($this->avatar) {
            // Delete old avatar
            Storage::delete($this->avatar);
        }

        $this->avatar = $avatar;
        $this->save();
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->user->email))) . '?d=mp';
    }
} 