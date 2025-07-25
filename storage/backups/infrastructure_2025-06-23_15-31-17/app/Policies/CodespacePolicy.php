<?php

namespace App\Policies;

use App\Models\Codespace;
use App\Models\User;

class CodespacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Codespace $codespace): bool
    {
        return $user->id === $codespace->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Codespace $codespace): bool
    {
        return $user->id === $codespace->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Codespace $codespace): bool
    {
        return $user->id === $codespace->user_id;
    }
} 