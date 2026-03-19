<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Assignment;
use App\Models\Cycle;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Mail;

class SendEmails extends Page
{
    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.resources.survey-resource.pages.send-emails';

    public Survey $record;
    public $currentCycle;
    public $eligibleStudents = [];
    public $totalStudents = 0;
    public $emailsSent = 0;
    public $emailSubject = '';
    public $emailBody = '';

    public function mount(): void
    {
        $this->currentCycle = Cycle::active()->current()->first();

        // Configurar email por defecto
        $this->emailSubject = 'Evaluación docente – participación obligatoria y anónima';
        $this->emailBody = 'Estimado/a estudiante:

Te informamos que ya se encuentra disponible la evaluación docente correspondiente al ciclo actual.

Tu participación es obligatoria y fundamental para mejorar la calidad académica y los procesos de enseñanza. Te pedimos responderla de manera responsable, objetiva y honesta, basándote en tu experiencia real durante el curso.

Es importante que sepas que:
• Todas las respuestas son completamente anónimas.
• Ningún docente ni área administrativa puede identificar quién respondió la encuesta.
• La información se utiliza únicamente con fines de mejora académica.
• La evaluación estará disponible por tiempo limitado, por lo que te solicitamos completarla a la brevedad dentro del periodo establecido.

Agradecemos tu compromiso y seriedad en este proceso, ya que tus respuestas tienen un impacto directo en la mejora continua de la institución.

Tu código de acceso a la encuesta es: {{student_id}}

Para acceder a la evaluación, ingresa a: {{survey_url}}

Atentamente,
Coordinación Académica';

        if ($this->currentCycle) {
            $this->loadEligibleStudents();
        }
    }

    protected function loadEligibleStudents(): void
    {
        // Obtener todos los estudiantes que tienen asignaturas en el ciclo actual
        $this->eligibleStudents = Student::whereHas('inscriptions.group.assignments')
            ->with(['inscriptions.group.assignments.teacher', 'inscriptions.group.assignments.subject'])
            ->get()
            ->filter(function ($student) {
                $inscription = $student->lastInscription;
                return $inscription && $inscription->group && $inscription->group->assignments->isNotEmpty();
            });

        $this->totalStudents = $this->eligibleStudents->count();
    }

    public function sendEmails(): void
    {
        $this->emailsSent = 0;
        $errors = 0;


        foreach ($this->eligibleStudents as $student) {
            try {
                $emailBody = str_replace(
                    ['{{student_id}}', '{{survey_url}}'],
                    [$student->id, url('/evaluacion/')],
                    $this->emailBody
                );

                Mail::to($student->email)->send(
                    new SurveyInvitationMail(
                        $this->emailSubject,
                        $emailBody,
                         $student->id,
                        $this->record
                    )
                );

                $this->emailsSent++;
            } catch (\Exception $e) {
                $errors++;
                // Log error if needed
            }
        }

        if ($errors > 0) {
            Notification::make()
                ->title('Correos enviados con algunos errores')
                ->body("Se enviaron {$this->emailsSent} correos correctamente. {$errors} correos fallaron.")
                ->warning()
                ->send();
        } else {
            Notification::make()
                ->title('Correos enviados exitosamente')
                ->body("Se enviaron {$this->emailsSent} correos a los estudiantes.")
                ->success()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('emailSubject')
                    ->label('Asunto del correo')
                    ->required()
                    ->maxLength(255),
                Textarea::make('emailBody')
                    ->label('Cuerpo del correo')
                    ->required()
                    ->rows(15)
                    ->helperText('Puedes usar {{student_id}} y {{survey_url}} como variables que serán reemplazadas automáticamente.'),
            ])
            ->statePath('data');
    }

    public function getTitle(): string
    {
        return 'Enviar Invitaciones: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendEmails')
                ->label('Enviar Correos')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar envío de correos')
                ->modalDescription("¿Estás seguro de enviar {$this->totalStudents} correos a los estudiantes?")
                ->action('sendEmails'),
            Action::make('back')
                ->label('Volver')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
