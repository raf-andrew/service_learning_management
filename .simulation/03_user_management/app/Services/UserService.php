<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function updateProfile(User $user, array $data)
    {
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $user->email_verification_token = Str::random(60);
        }

        if (isset($data['avatar'])) {
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }
            $user->avatar = $data['avatar']->store('avatars', 'public');
        }

        if (isset($data['preferences'])) {
            $user->preferences = array_merge($user->preferences ?? [], $data['preferences']);
        }

        $user->save();

        return $user;
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return [
            'success' => true,
            'message' => 'Password updated successfully'
        ];
    }

    public function updatePreferences(User $user, array $preferences)
    {
        $user->preferences = array_merge($user->preferences ?? [], $preferences);
        $user->save();

        return $user;
    }

    public function deleteAccount(User $user, string $password)
    {
        if (!Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Password is incorrect'
            ];
        }

        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        $user->tokens()->delete();
        $user->delete();

        return [
            'success' => true,
            'message' => 'Account deleted successfully'
        ];
    }
} 