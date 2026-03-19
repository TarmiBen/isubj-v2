<?php

namespace App\Filament\Resources\Teacher\TeacherSubjectResource\Pages;

use App\Filament\Resources\Teacher\TeacherSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherSubject extends ViewRecord
{
    protected static string $resource = TeacherSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function getTitle(): string
    {
        return 'Detalles de la Asignatura';
    }
}
