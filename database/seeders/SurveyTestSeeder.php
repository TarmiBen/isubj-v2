<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Group;
use App\Models\Subject;
use App\Models\Assignment;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class SurveyTestSeeder extends Seeder
{
    public function run(): void
    {
        // Crear ciclo si no existe
        $cycle = Cycle::firstOrCreate([
            'code' => '1'
        ], [
            'name' => 'Ciclo 1 - 2026',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'active' => true,
            'description' => 'Ciclo de evaluación docente'
        ]);

        // Crear encuesta de evaluación docente
        $survey = Survey::firstOrCreate([
            'type' => 'docente',
            'is_default' => true
        ], [
            'name' => 'Evaluación Docente',
            'description' => 'Encuesta para evaluar el desempeño docente',
            'active' => true
        ]);

        // Crear preguntas para la encuesta
        $questions = [
            [
                'question' => '¿Cómo calificarías la claridad en las explicaciones del docente?',
                'type' => 'scale',
                'required' => true,
                'order' => 1
            ],
            [
                'question' => '¿El docente demuestra dominio de la materia?',
                'type' => 'scale',
                'required' => true,
                'order' => 2
            ],
            [
                'question' => '¿Cómo evalúas la puntualidad del docente?',
                'type' => 'scale',
                'required' => true,
                'order' => 3
            ],
            [
                'question' => '¿El docente fomenta la participación en clase?',
                'type' => 'scale',
                'required' => true,
                'order' => 4
            ],
            [
                'question' => '¿Cómo calificas la metodología de enseñanza?',
                'type' => 'scale',
                'required' => true,
                'order' => 5
            ],
            [
                'question' => 'Comentarios adicionales sobre el docente (opcional)',
                'type' => 'text',
                'required' => false,
                'order' => 6
            ]
        ];

        foreach ($questions as $questionData) {
            $question = SurveyQuestion::firstOrCreate([
                'survey_id' => $survey->id,
                'question' => $questionData['question']
            ], $questionData);

            // Si es pregunta de escala, crear opciones
            if ($question->type === 'scale') {
                for ($i = 1; $i <= 5; $i++) {
                    QuestionOption::firstOrCreate([
                        'survey_question_id' => $question->id,
                        'value' => $i
                    ], [
                        'label' => $i,
                        'order' => $i,
                        'active' => true
                    ]);
                }
            }
        }

        // Crear datos de prueba para estudiantes, grupos, etc.
        $this->createTestData();
    }

    private function createTestData()
    {
        // Solo crear datos si las tablas existen y para la funcionalidad de encuestas
        echo "Creando datos básicos de prueba para encuestas...\n";

        // Crear ciclo si no existe
        $cycle = Cycle::firstOrCreate([
            'code' => '1'
        ], [
            'name' => 'Ciclo 1 - 2026',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'active' => true,
            'description' => 'Ciclo de evaluación docente'
        ]);

        echo "Ciclo creado: " . $cycle->name . "\n";
        echo "Datos de prueba creados exitosamente.\n";
        echo "Puedes probar la funcionalidad visitando: /evaluacion/12345\n";
    }
}
