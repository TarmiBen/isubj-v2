<?php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_user');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view_user');
    }

    public function create(User $user): bool
    {
        return $user->can('create_user');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('update_user');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete_user');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can('restore_user');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->can('force_delete_user');
    }
}
