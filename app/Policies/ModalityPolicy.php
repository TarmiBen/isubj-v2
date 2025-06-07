<?php
namespace App\Policies;

use App\Models\Modality;
use App\Models\User;

class ModalityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_modality');
    }

    public function view(User $user, Modality $modality): bool
    {
        return $user->can('view_modality');
    }

    public function create(User $user): bool
    {
        return $user->can('create_modality');
    }

    public function update(User $user, Modality $modality): bool
    {
        return $user->can('update_modality');
    }

    public function delete(User $user, Modality $modality): bool
    {
        return $user->can('delete_modality');
    }

    public function restore(User $user, Modality $modality): bool
    {
        return $user->can('restore_modality');
    }

    public function forceDelete(User $user, Modality $modality): bool
    {
        return $user->can('force_delete_modality');
    }
}
