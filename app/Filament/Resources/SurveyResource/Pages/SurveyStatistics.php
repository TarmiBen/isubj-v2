<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Filament\Resources\SurveyResource;
use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyRelated;
use App\Models\SurveyResponse;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class SurveyStatistics extends Page
{
    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.resources.survey-resource.pages.survey-statistics';

    public Survey $record;
    public $currentCycle;
    public $totalResponses = 0;
    public $teacherStatistics = [];
    public $questionStatistics = [];

    public function mount(): void
    {
        $cycleId = request()->query('cycle_id');

        if ($cycleId) {
            $this->currentCycle = Cycle::find($cycleId);
        }

        if (! $this->currentCycle) {
            $this->currentCycle = Cycle::active()->current()->first();
        }

        if ($this->currentCycle) {
            $this->loadStatistics();
        }
    }

    protected function loadStatistics(): void
    {
        $surveyRelated = SurveyRelated::where('survey_id', $this->record->id)
            ->where('cycle_id', $this->currentCycle->id)
            ->get();

        $this->totalResponses = SurveyResponse::whereIn('survey_related_id', $surveyRelated->pluck('id'))
            ->whereNotNull('completed_at')
            ->count();

        $this->teacherStatistics = $this->getTeacherStatistics($surveyRelated);
        $this->questionStatistics = $this->getQuestionStatistics();
    }

    protected function getTeacherStatistics($surveyRelated): array
    {
        $statistics = [];

        foreach ($surveyRelated as $related) {
            if ($related->survivable_type === Assignment::class) {
                $assignment = Assignment::with(['teacher', 'subject'])
                    ->find($related->survivable_id);

                if ($assignment && $assignment->teacher) {
                    $responses = SurveyResponse::where('survey_related_id', $related->id)
                        ->whereNotNull('completed_at')
                        ->get();

                    if ($responses->count() > 0) {
                        $scaleAnswers = SurveyAnswer::whereIn('survey_response_id', $responses->pluck('id'))
                            ->whereNotNull('answer_numeric')
                            ->whereHas('question', function (Builder $query) {
                                $query->where('type', 'scale');
                            })
                            ->avg('answer_numeric');

                        $statistics[] = [
                            'teacher_id'      => $assignment->teacher->id,
                            'teacher_name'    => $assignment->teacher->name,
                            'subject_name'    => $assignment->subject->name,
                            'total_responses' => $responses->count(),
                            'average_score'   => round($scaleAnswers, 2),
                            'assignment_id'   => $assignment->id,
                        ];
                    }
                }
            }
        }

        return collect($statistics)->sortByDesc('average_score')->values()->toArray();
    }

    protected function getQuestionStatistics(): array
    {
        $statistics = [];

        foreach ($this->record->questions as $question) {
            $answers = SurveyAnswer::where('survey_question_id', $question->id)
                ->whereHas('response', function (Builder $query) {
                    $query->whereNotNull('completed_at')
                        ->where('cycle_id', $this->currentCycle->id);
                })
                ->get();

            if ($question->type === 'scale') {
                $average = $answers->whereNotNull('answer_numeric')->avg('answer_numeric');
                $statistics[] = [
                    'question_id'    => $question->id,
                    'question'       => $question->question,
                    'type'           => $question->type,
                    'total_responses' => $answers->count(),
                    'average_score'  => round($average, 2),
                ];
            } else {
                $statistics[] = [
                    'question_id'    => $question->id,
                    'question'       => $question->question,
                    'type'           => $question->type,
                    'total_responses' => $answers->count(),
                    'average_score'  => null,
                ];
            }
        }

        return $statistics;
    }

    public function getTitle(): string
    {
        $cycleName = $this->currentCycle ? ' — ' . $this->currentCycle->name : '';
        return 'Estadísticas: ' . $this->record->name . $cycleName;
    }

    protected function getHeaderActions(): array
    {
        $pdfUrl = $this->currentCycle
            ? route('surveys.pdf', ['survey' => $this->record->id, 'cycle_id' => $this->currentCycle->id])
            : null;

        $actions = [
            \Filament\Actions\Action::make('back')
                ->label('Volver')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];

        if ($pdfUrl) {
            $actions[] = \Filament\Actions\Action::make('download_pdf')
                ->label('Descargar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url($pdfUrl)
                ->openUrlInNewTab();
        }

        return $actions;
    }
}