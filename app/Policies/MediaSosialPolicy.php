<?php

namespace App\Policies;

use App\Models\MediaSosial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MediaSosialPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view the links
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaSosial $mediaSosial): bool
    {
        return true; // Everyone can view the links
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaSosial $mediaSosial): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaSosial $mediaSosial): bool
    {
        return $user->role === 'admin';
    }
}
