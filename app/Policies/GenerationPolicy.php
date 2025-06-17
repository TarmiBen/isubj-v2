<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Generation;

class GenerationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_generation');
    }

    public function view(User $user, Generation $generation): bool
    {
        return $user->can('view_generation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_generation');
    }

    public function update(User $user, Generation $generation): bool
    {
        return $user->can('update_generation');
    }

    public function delete(User $user, Generation $generation): bool
    {
        return $user->can('delete_generation');
    }

    public function restore(User $user, Generation $generation): bool
    {
        return $user->can('restore_generation');
    }

    public function forceDelete(User $user, Generation $generation): bool
    {
        return $user->can('force_delete_generation');
    }
}
