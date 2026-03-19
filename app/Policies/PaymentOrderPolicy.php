<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PaymentOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment::order');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentOrder $model): bool
    {
        return $user->can('view_payment::order');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_payment::order');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentOrder $model): bool
    {
        return $user->can('update_payment::order');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentOrder $model): bool
    {
        return $user->can('delete_payment::order');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payment::order');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, PaymentOrder $model): bool
    {
        return $user->can('force_delete_payment::order');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_payment::order');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, PaymentOrder $model): bool
    {
        return $user->can('restore_payment::order');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_payment::order');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, PaymentOrder $model): bool
    {
        return $user->can('replicate_payment::order');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_payment::order');
    }
}
