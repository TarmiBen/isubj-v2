<?php
namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_group');
    }

    public function view(User $user, Group $group): bool
    {
        return $user->can('view_group');
    }

    public function create(User $user): bool
    {
        return $user->can('create_group');
    }

    public function update(User $user, Group $group): bool
    {
        return $user->can('update_group');
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->can('delete_group');
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->can('restore_group');
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->can('force_delete_group');
    }
}
