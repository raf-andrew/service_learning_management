<?php

namespace App\Modules\Auth\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\UserRole;
use App\Modules\Shared\Services\Core\AuditService;

class AuthenticationService
{
    /**
     * The audit service instance.
     */
    protected AuditService $auditService;

    /**
     * Create a new authentication service instance.
     */
    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Authenticate a user with credentials.
     */
    public function authenticate(array $credentials, bool $remember = false): array
    {
        try {
            $userModel = config('auth.providers.users.model', 'App\Models\User');
            $user = $userModel::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                $this->auditService->log('authentication_failed', [
                    'email' => $credentials['email'],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'user' => null,
                ];
            }

            // Check if user is active
            if (!$user->is_active ?? true) {
                $this->auditService->log('authentication_blocked_inactive_user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Account is inactive',
                    'user' => null,
                ];
            }

            // Check for account lockout
            if ($this->isAccountLocked($user->id)) {
                $this->auditService->log('authentication_blocked_locked_account', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Account is temporarily locked',
                    'user' => null,
                ];
            }

            // Attempt authentication
            if (Auth::attempt($credentials, $remember)) {
                $this->clearFailedAttempts($user->id);
                $this->updateLastLogin($user);
                $this->loadUserRoles($user);

                $this->auditService->log('authentication_successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                    'remember' => $remember,
                ]);

                return [
                    'success' => true,
                    'message' => 'Authentication successful',
                    'user' => $user,
                ];
            }

            // Increment failed attempts
            $this->incrementFailedAttempts($user->id);

            $this->auditService->log('authentication_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => request()->ip(),
                'failed_attempts' => $this->getFailedAttempts($user->id),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid credentials',
                'user' => null,
            ];

        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'email' => $credentials['email'] ?? 'unknown',
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => false,
                'message' => 'Authentication error occurred',
                'user' => null,
            ];
        }
    }

    /**
     * Logout the current user.
     */
    public function logout(): array
    {
        try {
            $user = Auth::user();

            if ($user) {
                $this->auditService->log('logout_successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => request()->ip(),
                ]);

                // Clear user sessions
                $this->clearUserSessions($user->id);
            }

            Auth::logout();

            return [
                'success' => true,
                'message' => 'Logout successful',
            ];

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'Logout error occurred',
            ];
        }
    }

    /**
     * Refresh the user's authentication token.
     */
    public function refreshToken(): array
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'No authenticated user',
                    'token' => null,
                ];
            }

            // Generate new token
            $token = $user->createToken('auth-token')->plainTextToken;

            $this->auditService->log('token_refreshed', [
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
                'token' => $token,
            ];

        } catch (\Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return [
                'success' => false,
                'message' => 'Token refresh error occurred',
                'token' => null,
            ];
        }
    }

    /**
     * Validate a user's password.
     */
    public function validatePassword(int $userId, string $password): bool
    {
        $userModel = config('auth.providers.users.model', 'App\Models\User');
        $user = $userModel::find($userId);

        if (!$user) {
            return false;
        }

        return Hash::check($password, $user->password);
    }

    /**
     * Change a user's password.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        try {
            $userModel = config('auth.providers.users.model', 'App\Models\User');
            $user = $userModel::find($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            // Validate current password
            if (!Hash::check($currentPassword, $user->password)) {
                $this->auditService->log('password_change_failed_invalid_current', [
                    'user_id' => $userId,
                    'ip_address' => request()->ip(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ];
            }

            // Validate new password
            if (!$this->validatePasswordStrength($newPassword)) {
                return [
                    'success' => false,
                    'message' => 'New password does not meet strength requirements',
                ];
            }

            // Update password
            $user->password = Hash::make($newPassword);
            $user->password_changed_at = now();
            $user->save();

            // Clear all sessions for this user
            $this->clearUserSessions($userId);

            $this->auditService->log('password_changed', [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'message' => 'Password changed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Password change error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Password change error occurred',
            ];
        }
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        try {
            $userModel = config('auth.providers.users.model', 'App\Models\User');
            $user = $userModel::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            // Validate reset token
            if (!$this->validateResetToken($user->id, $token)) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token',
                ];
            }

            // Validate new password
            if (!$this->validatePasswordStrength($newPassword)) {
                return [
                    'success' => false,
                    'message' => 'New password does not meet strength requirements',
                ];
            }

            // Update password
            $user->password = Hash::make($newPassword);
            $user->password_changed_at = now();
            $user->save();

            // Clear reset token
            $this->clearResetToken($user->id);

            // Clear all sessions for this user
            $this->clearUserSessions($user->id);

            $this->auditService->log('password_reset', [
                'user_id' => $user->id,
                'email' => $email,
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'message' => 'Password reset successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Password reset error', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            return [
                'success' => false,
                'message' => 'Password reset error occurred',
            ];
        }
    }

    /**
     * Generate a password reset token.
     */
    public function generateResetToken(string $email): array
    {
        try {
            $userModel = config('auth.providers.users.model', 'App\Models\User');
            $user = $userModel::where('email', $email)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                ];
            }

            // Generate reset token
            $token = Str::random(64);
            $expiresAt = now()->addHours(config('modules.modules.auth.password_reset_expiry_hours', 24));

            // Store reset token
            Cache::put("password_reset_{$user->id}", [
                'token' => $token,
                'expires_at' => $expiresAt,
            ], $expiresAt);

            $this->auditService->log('password_reset_token_generated', [
                'user_id' => $user->id,
                'email' => $email,
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'message' => 'Reset token generated successfully',
                'token' => $token,
                'expires_at' => $expiresAt,
            ];

        } catch (\Exception $e) {
            Log::error('Password reset token generation error', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            return [
                'success' => false,
                'message' => 'Reset token generation error occurred',
            ];
        }
    }

    /**
     * Check if an account is locked.
     */
    protected function isAccountLocked(int $userId): bool
    {
        $failedAttempts = $this->getFailedAttempts($userId);
        $maxAttempts = config('modules.modules.auth.max_failed_attempts', 5);
        $lockoutDuration = config('modules.modules.auth.lockout_duration_minutes', 30);

        if ($failedAttempts >= $maxAttempts) {
            $lastAttempt = Cache::get("failed_attempt_time_{$userId}");
            
            if ($lastAttempt && now()->diffInMinutes($lastAttempt) < $lockoutDuration) {
                return true;
            }

            // Clear failed attempts if lockout period has expired
            $this->clearFailedAttempts($userId);
        }

        return false;
    }

    /**
     * Get failed login attempts for a user.
     */
    protected function getFailedAttempts(int $userId): int
    {
        return Cache::get("failed_attempts_{$userId}", 0);
    }

    /**
     * Increment failed login attempts for a user.
     */
    protected function incrementFailedAttempts(int $userId): void
    {
        $attempts = $this->getFailedAttempts($userId) + 1;
        Cache::put("failed_attempts_{$userId}", $attempts, now()->addHours(1));
        Cache::put("failed_attempt_time_{$userId}", now(), now()->addHours(1));
    }

    /**
     * Clear failed login attempts for a user.
     */
    protected function clearFailedAttempts(int $userId): void
    {
        Cache::forget("failed_attempts_{$userId}");
        Cache::forget("failed_attempt_time_{$userId}");
    }

    /**
     * Update user's last login timestamp.
     */
    protected function updateLastLogin($user): void
    {
        $user->last_login_at = now();
        $user->save();
    }

    /**
     * Load user roles and permissions.
     */
    protected function loadUserRoles($user): void
    {
        $user->load(['roles' => function ($query) {
            $query->active()->notExpired();
        }, 'roles.permissions' => function ($query) {
            $query->active();
        }]);
    }

    /**
     * Clear all sessions for a user.
     */
    protected function clearUserSessions(int $userId): void
    {
        // Clear Laravel sessions
        if (method_exists(Auth::class, 'logoutOtherDevices')) {
            Auth::logoutOtherDevices(request('password'));
        }

        // Clear Sanctum tokens if using
        if (class_exists('Laravel\Sanctum\PersonalAccessToken')) {
            $userModel = config('auth.providers.users.model', 'App\Models\User');
            $user = $userModel::find($userId);
            if ($user && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
        }
    }

    /**
     * Validate password strength.
     */
    protected function validatePasswordStrength(string $password): bool
    {
        $minLength = config('modules.modules.auth.password_min_length', 8);
        $requireUppercase = config('modules.modules.auth.password_require_uppercase', true);
        $requireLowercase = config('modules.modules.auth.password_require_lowercase', true);
        $requireNumbers = config('modules.modules.auth.password_require_numbers', true);
        $requireSymbols = config('modules.modules.auth.password_require_symbols', true);

        if (strlen($password) < $minLength) {
            return false;
        }

        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if ($requireSymbols && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Validate reset token.
     */
    protected function validateResetToken(int $userId, string $token): bool
    {
        $resetData = Cache::get("password_reset_{$userId}");

        if (!$resetData) {
            return false;
        }

        return $resetData['token'] === $token && now()->lt($resetData['expires_at']);
    }

    /**
     * Clear reset token.
     */
    protected function clearResetToken(int $userId): void
    {
        Cache::forget("password_reset_{$userId}");
    }
} 