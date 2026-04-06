<?php

namespace App\Filament\Resources\AlertResource\Pages;

use App\Filament\Resources\AlertResource;
use App\Models\Assignment;
use App\Models\Teacher;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlert extends EditRecord
{
    protected static string $resource = AlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Cargar los grupos asociados para mostrarlos en el formulario
        $data['groups'] = $this->record->groups()->pluck('groups.id')->toArray();

        // Cargar usuarios adicionales (los que no son maestros de los grupos)
        // Por ahora cargamos todos los usuarios asignados, pero podrías filtrar
        // solo los que fueron agregados manualmente si necesitas esa distinción
        $data['additional_users'] = $this->record->users()->pluck('users.id')->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        // 1. Sincronizar grupos
        $groups = $this->data['groups'] ?? [];

        if (!empty($groups)) {
            $this->record->groups()->sync($groups);
        } else {
            $this->record->groups()->detach();
        }

        // 2. Recalcular y sincronizar usuarios
        $newUserIds = collect();

        // 2.1. Obtener maestros de los grupos
        if (!empty($groups)) {
            $teacherIds = Assignment::whereIn('group_id', $groups)
                ->distinct()
                ->pluck('teacher_id');

            $teacherUserIds = Teacher::whereIn('id', $teacherIds)
                ->whereHas('user')
                ->get()
                ->pluck('user.id')
                ->filter();

            $newUserIds = $newUserIds->merge($teacherUserIds);
        }

        // 2.2. Agregar usuarios adicionales
        $additionalUsers = $this->data['additional_users'] ?? [];
        if (!empty($additionalUsers)) {
            $newUserIds = $newUserIds->merge($additionalUsers);
        }

        $newUserIds = $newUserIds->unique()->values();

        // 2.3. Sincronizar manteniendo el estado de vistas y cierres
        $syncData = [];
        foreach ($newUserIds as $userId) {
            $existingPivot = $this->record->users()->where('user_id', $userId)->first();

            $syncData[$userId] = [
                'viewed_at' => $existingPivot ? $existingPivot->pivot->viewed_at : null,
                'closed_at' => $existingPivot ? $existingPivot->pivot->closed_at : null,
            ];
        }

        if (!empty($syncData)) {
            $this->record->users()->sync($syncData);
        } else {
            $this->record->users()->detach();
        }
    }
}
