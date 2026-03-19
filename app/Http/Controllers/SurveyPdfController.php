<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyRelated;
use App\Models\SurveyResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SurveyPdfController extends Controller
{
    public function download(Request $request, Survey $survey)
    {
        $cycle = Cycle::findOrFail($request->query('cycle_id'));

        $questions = $survey->questions()->orderBy('order')->get();

        $surveyRelated = SurveyRelated::where('survey_id', $survey->id)
            ->where('cycle_id', $cycle->id)
            ->with(['survivable'])
            ->get();

        // ── Sección 1: resumen por docente (todas sus asignaciones en el ciclo) ──
        $teacherSummaries = $this->buildTeacherSummaries($surveyRelated, $questions);

        // ── Sección 2: detalle por grupo ──
        $groupDetails = $this->buildGroupDetails($surveyRelated, $questions);

        $pdf = Pdf::loadView('pdf.survey-report', [
            'survey'          => $survey,
            'cycle'           => $cycle,
            'questions'       => $questions,
            'teacherSummaries' => $teacherSummaries,
            'groupDetails'    => $groupDetails,
        ])->setPaper('letter', 'portrait');

        $filename = 'Reporte_' . str_replace(' ', '_', $survey->name) . '_' . $cycle->code . '.pdf';

        return $pdf->download($filename);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sección 1 — por docente, promedio de TODAS sus materias/grupos en el ciclo
    // ──────────────────────────────────────────────────────────────────────────
    private function buildTeacherSummaries($surveyRelated, $questions): array
    {
        // Agrupar relateds por teacher_id
        $byTeacher = [];

        foreach ($surveyRelated as $related) {
            if ($related->survivable_type !== Assignment::class) {
                continue;
            }

            $assignment = Assignment::with(['teacher', 'subject', 'group'])->find($related->survivable_id);
            if (! $assignment || ! $assignment->teacher) {
                continue;
            }

            $teacherId = $assignment->teacher->id;

            if (! isset($byTeacher[$teacherId])) {
                $byTeacher[$teacherId] = [
                    'teacher_name' => $assignment->teacher->name,
                    'response_ids' => [],
                ];
            }

            $responseIds = SurveyResponse::where('survey_related_id', $related->id)
                ->whereNotNull('completed_at')
                ->pluck('id')
                ->toArray();

            $byTeacher[$teacherId]['response_ids'] = array_merge(
                $byTeacher[$teacherId]['response_ids'],
                $responseIds
            );
        }

        $summaries = [];

        foreach ($byTeacher as $teacherId => $data) {
            if (empty($data['response_ids'])) {
                continue;
            }

            $responseIds = $data['response_ids'];
            $questionStats = $this->buildQuestionStats($questions, $responseIds);

            // Promedio general de preguntas numéricas
            $numericAvgs = collect($questionStats)
                ->whereNotNull('average')
                ->pluck('average');

            $summaries[] = [
                'teacher_name'    => $data['teacher_name'],
                'total_responses' => count($responseIds),
                'overall_average' => $numericAvgs->isNotEmpty() ? round($numericAvgs->avg(), 2) : null,
                'question_stats'  => $questionStats,
            ];
        }

        return collect($summaries)->sortByDesc('overall_average')->values()->toArray();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sección 2 — por grupo → por asignación (docente + materia)
    // ──────────────────────────────────────────────────────────────────────────
    private function buildGroupDetails($surveyRelated, $questions): array
    {
        $byGroup = [];

        foreach ($surveyRelated as $related) {
            if ($related->survivable_type !== Assignment::class) {
                continue;
            }

            $assignment = Assignment::with(['teacher', 'subject', 'group'])->find($related->survivable_id);
            if (! $assignment || ! $assignment->teacher || ! $assignment->group) {
                continue;
            }

            $responseIds = SurveyResponse::where('survey_related_id', $related->id)
                ->whereNotNull('completed_at')
                ->pluck('id')
                ->toArray();

            if (empty($responseIds)) {
                continue;
            }

            $groupId   = $assignment->group->id;
            $groupCode = $assignment->group->code;

            if (! isset($byGroup[$groupId])) {
                $byGroup[$groupId] = [
                    'group_code'    => $groupCode,
                    'assignments'   => [],
                ];
            }

            $questionStats = $this->buildQuestionStats($questions, $responseIds);

            $numericAvgs = collect($questionStats)
                ->whereNotNull('average')
                ->pluck('average');

            $byGroup[$groupId]['assignments'][] = [
                'teacher_name'    => $assignment->teacher->name,
                'subject_name'    => $assignment->subject->name,
                'total_responses' => count($responseIds),
                'overall_average' => $numericAvgs->isNotEmpty() ? round($numericAvgs->avg(), 2) : null,
                'question_stats'  => $questionStats,
            ];
        }

        return collect($byGroup)->sortBy('group_code')->values()->toArray();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Construye estadísticas por pregunta dado un conjunto de response_ids
    // ──────────────────────────────────────────────────────────────────────────
    private function buildQuestionStats($questions, array $responseIds): array
    {
        $stats = [];

        foreach ($questions as $question) {
            $answers = SurveyAnswer::where('survey_question_id', $question->id)
                ->whereIn('survey_response_id', $responseIds)
                ->get();

            if ($question->type === 'text') {
                $stats[] = [
                    'question_id' => $question->id,
                    'question'    => $question->question,
                    'type'        => 'text',
                    'average'     => null,
                    'responses'   => $answers->whereNotNull('answer_text')
                        ->where('answer_text', '!=', '')
                        ->pluck('answer_text')
                        ->toArray(),
                ];
            } else {
                // scale, rating, yes_no, single_choice, multiple_choice
                $avg = $answers->whereNotNull('answer_numeric')->avg('answer_numeric');
                $stats[] = [
                    'question_id' => $question->id,
                    'question'    => $question->question,
                    'type'        => $question->type,
                    'average'     => $avg !== null ? round($avg, 2) : null,
                    'responses'   => [],
                ];
            }
        }

        return $stats;
    }
}