<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\FinalGrade;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class StudentGradeHistory extends ViewRecord
{
    protected static string $resource = StudentResource::class;
    protected static string $view = 'filament.resources.student-resource.pages.grade-history';

    public function getTitle(): string
    {
        return "Historial de Calificaciones: {$this->record->name} {$this->record->last_name1}";
    }

    public function getActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver al alumno')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(StudentResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function getViewData(): array
    {
        $student = $this->record;

        $inscriptions = $student->inscriptions()
            ->with([
                'group.period.career',
                'cycle',
                'group.assignments.subject',
                'group.assignments.finalGrades' => fn ($q) => $q
                    ->where('student_id', $student->id)
                    ->orderByDesc('attempt'),
            ])
            ->orderByDesc('created_at')
            ->get();

        return compact('inscriptions');
    }
}
