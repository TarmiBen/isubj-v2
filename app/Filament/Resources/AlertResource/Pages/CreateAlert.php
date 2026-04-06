<?php

namespace App\Filament\Resources\AlertResource\Pages;

use App\Filament\Resources\AlertResource;
use App\Models\Assignment;
use App\Models\Teacher;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAlert extends CreateRecord
{
    protected static string $resource = AlertResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Sincronizar grupos
        $groups = $this->data['groups'] ?? [];

        if (!empty($groups)) {
            $this->record->groups()->sync($groups);
        }

        // 2. Asignar usuarios a la alerta
        $userIds = collect();

        // 2.1. Obtener maestros de los grupos seleccionados
        if (!empty($groups)) {
            // Obtener todos los maestros que tienen asignaciones en estos grupos
            $teacherIds = Assignment::whereIn('group_id', $groups)
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

        // 2.2. Agregar usuarios adicionales seleccionados manualmente
        $additionalUsers = $this->data['additional_users'] ?? [];
        if (!empty($additionalUsers)) {
            $userIds = $userIds->merge($additionalUsers);
        }

        // 2.3. Eliminar duplicados y asignar a la tabla pivote
        $userIds = $userIds->unique()->values();

        if ($userIds->isNotEmpty()) {
            $this->record->users()->attach($userIds);
        }
    }
}
