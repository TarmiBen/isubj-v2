<?php
namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_assignment');
    }

    public function view(User $user, Assignment $assignment): bool
    {
        return $user->can('view_assignment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_assignment');
    }

    public function update(User $user, Assignment $assignment): bool
    {
        return $user->can('update_assignment');
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->can('delete_assignment');
    }

    public function restore(User $user, Assignment $assignment): bool
    {
        return $user->can('restore_assignment');
    }

    public function forceDelete(User $user, Assignment $assignment): bool
    {
        return $user->can('force_delete_assignment');
    }
}
