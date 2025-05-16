<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    protected $user;
    protected $role;

    public function __construct(User $user, Role $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function register(array $data)
    {
        // Validate input data
        $this->validateRegistrationData($data);

        // Create user record
        $user = $this->user->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => Str::random(60),
        ]);

        // Assign default role
        $this->assignRole($user, 'user');

        // Send welcome email
        $this->sendWelcomeEmail($user);

        return $user;
    }

    public function authenticate(array $credentials)
    {
        $user = $this->user->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        // Generate new session token
        $user->session_token = Str::random(60);
        $user->save();

        return $user;
    }

    public function resetPassword(string $email)
    {
        $user = $this->user->where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found');
        }

        $token = Str::random(60);
        $user->password_reset_token = $token;
        $user->save();

        // Send password reset email
        Mail::to($user->email)->send(new \App\Mail\PasswordReset($user, $token));

        return true;
    }

    public function updateProfile(User $user, array $data)
    {
        // Validate profile data
        $this->validateProfileData($data);

        // Update user profile
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
        ]);

        // Handle avatar upload
        if (isset($data['avatar'])) {
            $this->updateAvatar($user, $data['avatar']);
        }

        return $user;
    }

    public function updatePreferences(User $user, array $preferences)
    {
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $preferences
        );

        return $user->preferences;
    }

    public function assignRole(User $user, string $roleName)
    {
        $role = $this->role->where('name', $roleName)->first();

        if (!$role) {
            throw new \Exception('Role not found');
        }

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function removeRole(User $user, string $roleName)
    {
        $role = $this->role->where('name', $roleName)->first();

        if (!$role) {
            throw new \Exception('Role not found');
        }

        $user->roles()->detach($role->id);

        return $user;
    }

    public function hasRole(User $user, string $roleName): bool
    {
        return $user->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(User $user, string $permission): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    protected function validateRegistrationData(array $data)
    {
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function validateProfileData(array $data)
    {
        $validator = \Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|image|max:2048',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function updateAvatar(User $user, $avatar)
    {
        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        // Store new avatar
        $path = $avatar->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();
    }

    protected function sendWelcomeEmail(User $user)
    {
        Mail::to($user->email)->send(new \App\Mail\Welcome($user));
    }
} 