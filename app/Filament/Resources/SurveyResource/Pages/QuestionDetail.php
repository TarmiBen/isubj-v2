<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Filament\Resources\SurveyResource;
use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyRelated;
use App\Models\SurveyResponse;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class QuestionDetail extends Page
{
    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.resources.survey-resource.pages.question-detail';

    public Survey $record;
    public SurveyQuestion $question;
    public $currentCycle;
    public $questionStatistics = [];
    public $textResponses = [];

    public function mount(string $question): void
    {
        $this->question = SurveyQuestion::findOrFail($question);
        $this->currentCycle = Cycle::active()->current()->first();

        if ($this->currentCycle) {
            $this->loadQuestionDetail();
        }
    }

    protected function loadQuestionDetail(): void
    {
        // Obtener todas las respuestas de esta pregunta en el ciclo actual
        $answers = SurveyAnswer::where('survey_question_id', $this->question->id)
            ->whereHas('response', function (Builder $query) {
                $query->whereNotNull('completed_at')
                    ->where('cycle_id', $this->currentCycle->id);
            })
            ->with(['response.surveyRelated.survivable.teacher', 'response.surveyRelated.survivable.subject'])
            ->get();

        if ($this->question->type === 'scale') {
            // Estadísticas para preguntas de escala
            $this->questionStatistics = [
                'total_responses' => $answers->count(),
                'average_score' => round($answers->whereNotNull('answer_numeric')->avg('answer_numeric'), 2),
                'score_distribution' => $this->getScoreDistribution($answers),
                'by_teacher' => $this->getStatisticsByTeacher($answers),
            ];
        } else {
            // Respuestas de texto
            $this->textResponses = $answers->whereNotNull('answer_text')
                ->map(function ($answer) {
                    $assignment = $answer->response->surveyRelated->survivable;
                    return [
                        'text' => $answer->answer_text,
                        'teacher_name' => $assignment->teacher->name ?? 'Sin docente',
                        'subject_name' => $assignment->subject->name ?? 'Sin materia',
                        'created_at' => $answer->created_at,
                    ];
                })
                ->sortBy('created_at')
                ->values()
                ->toArray();
        }
    }

    protected function getScoreDistribution($answers): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $answers->where('answer_numeric', $i)->count();
            $percentage = $answers->count() > 0 ? round(($count / $answers->count()) * 100, 1) : 0;
            $distribution[$i] = [
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        return $distribution;
    }

    protected function getStatisticsByTeacher($answers): array
    {
        $byTeacher = [];

        foreach ($answers as $answer) {
            $assignment = $answer->response->surveyRelated->survivable;
            if ($assignment && $assignment->teacher) {
                $teacherId = $assignment->teacher->id;

                if (!isset($byTeacher[$teacherId])) {
                    $byTeacher[$teacherId] = [
                        'teacher_name' => $assignment->teacher->name,
                        'subject_name' => $assignment->subject->name,
                        'responses' => [],
                        'average' => 0,
                        'count' => 0
                    ];
                }

                if ($answer->answer_numeric) {
                    $byTeacher[$teacherId]['responses'][] = $answer->answer_numeric;
                    $byTeacher[$teacherId]['count']++;
                }
            }
        }

        // Calcular promedios
        foreach ($byTeacher as $teacherId => &$data) {
            if ($data['count'] > 0) {
                $data['average'] = round(array_sum($data['responses']) / $data['count'], 2);
            }
        }

        return array_values($byTeacher);
    }

    public function getTitle(): string
    {
        return 'Detalle de Pregunta: ' . substr($this->question->question, 0, 50) . '...';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Volver a Estadísticas')
                ->url($this->getResource()::getUrl('statistics', ['record' => $this->record]))
                ->color('gray'),
        ];
    }
}
