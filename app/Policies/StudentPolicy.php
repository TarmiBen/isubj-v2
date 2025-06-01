<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */

    /**
     * Determine whether the user can view the model.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_student');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('view_student');
    }

    public function create(User $user): bool
    {
        return $user->can('create_student');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('update_student');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('delete_student');
    }

}
