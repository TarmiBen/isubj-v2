<?php

namespace App\Observers;

use App\Models\Alert;
use App\Models\Assignment;
use App\Models\Teacher;
use App\Models\User;

class AlertObserver
{
    /**
     * Handle the Alert "created" event.
     */
    public function created(Alert $alert): void
    {
        // Asignar created_by si no está definido
        if (auth()->check() && !$alert->created_by) {
            $alert->update(['created_by' => auth()->id()]);
        }

        // Obtener los IDs de usuarios que recibirán la alerta
        $userIds = collect();

        // 1. Obtener maestros de los grupos seleccionados
        if (request()->has('groups') && is_array(request('groups'))) {
            $groupIds = request('groups');

            // Obtener todos los maestros que tienen asignaciones en estos grupos
            $teacherIds = Assignment::whereIn('group_id', $groupIds)
                ->distinct()
                ->pluck('teacher_id');

            // Obtener los user_id de estos maestros
            $teacherUserIds = Teacher::whereIn('id', $teacherIds)
                ->whereHas('user')
                ->get()
                ->pluck('user.id')
                ->filter();

            $userIds = $userIds->merge($teacherUserIds);
        }

        // 2. Agregar usuarios adicionales seleccionados manualmente
        if (request()->has('additional_users') && is_array(request('additional_users'))) {
            $userIds = $userIds->merge(request('additional_users'));
        }

        // 3. Eliminar duplicados y asignar a la tabla pivote
        $userIds = $userIds->unique()->values();

        if ($userIds->isNotEmpty()) {
            $alert->users()->attach($userIds);
        }
    }

    /**
     * Handle the Alert "updated" event.
     */
    public function updated(Alert $alert): void
    {
        // Si se actualizan los grupos o usuarios, recalcular destinatarios
        if (request()->has('groups') || request()->has('additional_users')) {
            // Obtener usuarios actuales
            $currentUserIds = $alert->users()->pluck('user_id');

            // Calcular nuevos usuarios
            $newUserIds = collect();

            // Obtener maestros de los grupos
            if (request()->has('groups') && is_array(request('groups'))) {
                $groupIds = request('groups');

                $teacherIds = Assignment::whereIn('group_id', $groupIds)
                    ->distinct()
                    ->pluck('teacher_id');

                $teacherUserIds = Teacher::whereIn('id', $teacherIds)
                    ->whereHas('user')
                    ->get()
                    ->pluck('user.id')
                    ->filter();

                $newUserIds = $newUserIds->merge($teacherUserIds);
            }

            // Agregar usuarios adicionales
            if (request()->has('additional_users') && is_array(request('additional_users'))) {
                $newUserIds = $newUserIds->merge(request('additional_users'));
            }

            $newUserIds = $newUserIds->unique()->values();

            // Sincronizar: mantener vistas y cierres para usuarios existentes
            $syncData = [];
            foreach ($newUserIds as $userId) {
                $existingPivot = $alert->users()->where('user_id', $userId)->first();

                $syncData[$userId] = [
                    'viewed_at' => $existingPivot ? $existingPivot->pivot->viewed_at : null,
                    'closed_at' => $existingPivot ? $existingPivot->pivot->closed_at : null,
                ];
            }

            $alert->users()->sync($syncData);
        }
    }
}

