<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\Pages;

use App\Filament\Teacher\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignment extends EditRecord
{
    protected static string $resource = AssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones de eliminar para profesores
        ];
    }

    // Esta página no debería ser accesible para profesores
    public function mount(int | string $record): void
    {
        abort(403, 'No tienes permisos para editar asignaciones.');
    }
}
