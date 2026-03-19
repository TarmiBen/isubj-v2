<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\Pages;

use App\Filament\Teacher\Resources\AssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAssignment extends CreateRecord
{
    protected static string $resource = AssignmentResource::class;

    // Esta página no debería ser accesible para profesores
    public function mount(): void
    {
        abort(403, 'No tienes permisos para crear asignaciones.');
    }
}
