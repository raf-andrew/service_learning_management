<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function create(array $data): User
    {
        try {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            return $this->user->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    public function find(int $id): ?User
    {
        try {
            return $this->user->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find user: ' . $e->getMessage());
            throw new \Exception('Failed to find user: ' . $e->getMessage());
        }
    }

    public function findByEmail(string $email): ?User
    {
        try {
            return $this->user->where('email', $email)->first();
        } catch (\Exception $e) {
            Log::error('Failed to find user by email: ' . $e->getMessage());
            throw new \Exception('Failed to find user by email: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $user = $this->find($id);
            if (!$user) {
                return false;
            }

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            return $user->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update user: ' . $e->getMessage());
            throw new \Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $user = $this->find($id);
            if (!$user) {
                return false;
            }

            return $user->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete user: ' . $e->getMessage());
            throw new \Exception('Failed to delete user: ' . $e->getMessage());
        }
    }

    public function all(array $filters = []): Collection
    {
        try {
            $query = $this->user->query();

            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get users: ' . $e->getMessage());
            throw new \Exception('Failed to get users: ' . $e->getMessage());
        }
    }

    public function paginate(array $filters = [], int $perPage = 15)
    {
        try {
            $query = $this->user->query();

            if (isset($filters['role'])) {
                $query->where('role', $filters['role']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to paginate users: ' . $e->getMessage());
            throw new \Exception('Failed to paginate users: ' . $e->getMessage());
        }
    }

    public function updateProfile(int $id, array $data): bool
    {
        try {
            $user = $this->find($id);
            if (!$user) {
                return false;
            }

            // Only allow updating specific profile fields
            $allowedFields = ['name', 'email', 'phone', 'address', 'bio', 'avatar'];
            $profileData = array_intersect_key($data, array_flip($allowedFields));

            return $user->update($profileData);
        } catch (\Exception $e) {
            Log::error('Failed to update user profile: ' . $e->getMessage());
            throw new \Exception('Failed to update user profile: ' . $e->getMessage());
        }
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        try {
            $user = $this->find($id);
            if (!$user) {
                return false;
            }

            return $user->update([
                'password' => Hash::make($newPassword)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user password: ' . $e->getMessage());
            throw new \Exception('Failed to update user password: ' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        try {
            $user = $this->find($id);
            if (!$user) {
                return false;
            }

            return $user->update(['status' => $status]);
        } catch (\Exception $e) {
            Log::error('Failed to update user status: ' . $e->getMessage());
            throw new \Exception('Failed to update user status: ' . $e->getMessage());
        }
    }
} 