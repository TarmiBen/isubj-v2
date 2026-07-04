<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\FinalGrade;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected static string $view = 'filament.resources.group-resource.pages.view-group';

    public function getTitle(): string
    {
        return "Grupo: {$this->record->code}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar'),
        ];
    }

    protected function getViewData(): array
    {
        $group = $this->record;
        $group->load(['period.career', 'cycle']);

        $assignments = $group->assignments()->with('subject')->get()->sortBy('subject.name')->values();
        $assignmentIds = $assignments->pluck('id');

        $students = $group->inscriptions()
            ->where('status', 'active')
            ->with('student')
            ->get()
            ->pluck('student')
            ->filter()
            ->sortBy([
                ['last_name1', 'asc'],
                ['last_name2', 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        $studentIds = $students->pluck('id');

        // Última calificación (mayor intento) por alumno/assignment, indexada como "studentId-assignmentId"
        $finalGrades = FinalGrade::whereIn('assignment_id', $assignmentIds)
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('attempt')
            ->get()
            ->groupBy(fn ($fg) => "{$fg->student_id}-{$fg->assignment_id}")
            ->map(fn ($group) => $group->first());

        // Promedio por alumno (sólo materias con calificación registrada)
        $studentAverages = [];

        foreach ($students as $student) {
            $grades = $assignments
                ->map(fn ($assignment) => $finalGrades->get("{$student->id}-{$assignment->id}")?->grade)
                ->filter(fn ($grade) => $grade !== null)
                ->map(fn ($grade) => (float) $grade);

            $studentAverages[$student->id] = $grades->isNotEmpty()
                ? round($grades->avg(), 1)
                : null;
        }

        $validAverages = collect($studentAverages)->filter(fn ($avg) => $avg !== null);
        $groupAverage = $validAverages->isNotEmpty() ? round($validAverages->avg(), 1) : null;

        return compact('group', 'assignments', 'students', 'finalGrades', 'studentAverages', 'groupAverage');
    }
}