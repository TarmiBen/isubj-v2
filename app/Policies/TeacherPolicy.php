<?php
namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_teacher');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->can('view_teacher');
    }

    public function create(User $user): bool
    {
        return $user->can('create_teacher');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->can('update_teacher');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->can('delete_teacher');
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $user->can('restore_teacher');
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return $user->can('force_delete_teacher');
    }
}
