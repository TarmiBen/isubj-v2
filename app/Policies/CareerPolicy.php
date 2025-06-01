<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Career; // ✅ Nombre de clase en mayúscula

class CareerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_career');
    }

    public function view(User $user, Career $career): bool
    {
        return $user->can('view_career');
    }

    public function create(User $user): bool
    {
        return $user->can('create_career');
    }

    public function update(User $user, Career $career): bool
    {
        return $user->can('update_career');
    }

    public function delete(User $user, Career $career): bool
    {
        return $user->can('delete_career');
    }

    public function restore(User $user, Career $career): bool
    {
        return $user->can('restore_career');
    }

    public function forceDelete(User $user, Career $career): bool
    {
        return $user->can('force_delete_career');
    }
}
