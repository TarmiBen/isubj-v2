<?php

namespace App\Filament\Resources\Teacher\TeacherSubjectResource\Pages;

use App\Filament\Resources\Teacher\TeacherSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherSubjects extends ListRecords
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
        return 'Mis Asignaturas';
    }

    public function getHeading(): string
    {
        return 'Mis Asignaturas Asignadas';
    }
}
