<?php

namespace App\Policies;

use App\Models\Qualification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QualificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_qualification');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Qualification $qualification): bool
    {
        return $user->can('view_qualification');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_qualification');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Qualification $qualification): bool
    {
        return $user->can('edit_qualification');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Qualification $qualification): bool
    {
        return $user->can('delete_qualification');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Qualification $qualification): bool
    {
        return $user->can('restore_qualification');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Qualification $qualification): bool
    {
        return $user->can('force_delete_qualification');
    }
}
