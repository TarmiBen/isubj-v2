<?php

namespace App\Filament\Admin\Pages;

use App\Mail\BulkEmailMail;
use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendBulkEmail extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Envío Masivo de Correos';
    protected static string $view = 'filament.admin.pages.send-bulk-email';
    protected static ?string $title = 'Envío Masivo de Correos';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function mount(): void
    {
        $this->form->fill([
            'send_to_students' => false,
            'send_to_teachers' => false,
            'group_filter' => null,
            'selected_students' => [],
            'selected_teachers' => [],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detalles del Correo')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->maxLength(255),

                        RichEditor::make('message')
                            ->label('Mensaje')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'heading',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                            ])
                            ->columnSpanFull(),

                        FileUpload::make('attachments')
                            ->label('Archivos Adjuntos')
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(10240)
                            ->directory('email-attachments')
                            ->preserveFilenames()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Destinatarios')
                    ->schema([
                        Select::make('send_to_students')
                            ->label('Enviar a Alumnos')
                            ->boolean()
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('group_filter', null);
                                    $set('selected_students', []);
                                }
                            }),

                        Select::make('group_filter')
                            ->label('Filtrar por Grupo')
                            ->options(function () {
                                return Group::with(['generation', 'period'])
                                    ->get()
                                    ->mapWithKeys(function ($group) {
                                        $label = $group->code;
                                        if ($group->generation) {
                                            $label .= ' - ' . $group->generation->name;
                                        }
                                        if ($group->period) {
                                            $label .= ' (' . $group->period->name . ')';
                                        }
                                        return [$group->id => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get) => $get('send_to_students'))
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    // Cuando se selecciona un grupo, pre-seleccionar todos los estudiantes de ese grupo
                                    $students = Student::whereHas('inscriptions', function ($query) use ($state) {
                                        $query->where('group_id', $state);
                                    })->pluck('id')->toArray();
                                    $set('selected_students', $students);
                                } else {
                                    // Cuando no hay filtro, pre-seleccionar todos los estudiantes
                                    $students = Student::pluck('id')->toArray();
                                    $set('selected_students', $students);
                                }
                            }),

                        CheckboxList::make('selected_students')
                            ->label('Seleccionar Alumnos')
                            ->options(function (Get $get) {
                                $groupId = $get('group_filter');

                                if ($groupId) {
                                    return Student::whereHas('inscriptions', function ($query) use ($groupId) {
                                        $query->where('group_id', $groupId);
                                    })
                                    ->orderBy('name')
                                    ->orderBy('last_name1')
                                    ->get()
                                    ->mapWithKeys(function ($student) {
                                        return [$student->id => $student->name . ' ' . $student->last_name1 . ' ' . $student->last_name2 . ' (' . $student->email . ')'];
                                    });
                                }

                                return Student::orderBy('name')
                                    ->orderBy('last_name1')
                                    ->get()
                                    ->mapWithKeys(function ($student) {
                                        return [$student->id => $student->name . ' ' . $student->last_name1 . ' ' . $student->last_name2 . ' (' . $student->email . ')'];
                                    });
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->visible(fn (Get $get) => $get('send_to_students'))
                            ->columnSpanFull(),

                        Select::make('send_to_teachers')
                            ->label('Enviar a Profesores')
                            ->boolean()
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    // Pre-seleccionar todos los profesores
                                    $teachers = Teacher::pluck('id')->toArray();
                                    $set('selected_teachers', $teachers);
                                } else {
                                    $set('selected_teachers', []);
                                }
                            }),

                        CheckboxList::make('selected_teachers')
                            ->label('Seleccionar Profesores')
                            ->options(function () {
                                return Teacher::orderBy('first_name')
                                    ->orderBy('last_name1')
                                    ->get()
                                    ->mapWithKeys(function ($teacher) {
                                        return [$teacher->id => $teacher->first_name . ' ' . $teacher->last_name1 . ' ' . $teacher->last_name2 . ' (' . $teacher->email . ')'];
                                    });
                            })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->visible(fn (Get $get) => $get('send_to_teachers'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        // Validar que se haya seleccionado al menos un destinatario
        $selectedStudents = $data['selected_students'] ?? [];
        $selectedTeachers = $data['selected_teachers'] ?? [];

        if (empty($selectedStudents) && empty($selectedTeachers)) {
            Notification::make()
                ->title('Error')
                ->body('Debes seleccionar al menos un destinatario.')
                ->danger()
                ->send();
            return;
        }

        // Recopilar todos los correos electrónicos
        $bccEmails = [];

        // Obtener correos de estudiantes seleccionados
        if (!empty($selectedStudents)) {
            $studentEmails = Student::whereIn('id', $selectedStudents)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $bccEmails = array_merge($bccEmails, $studentEmails);
        }

        // Obtener correos de profesores seleccionados
        if (!empty($selectedTeachers)) {
            $teacherEmails = Teacher::whereIn('id', $selectedTeachers)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $bccEmails = array_merge($bccEmails, $teacherEmails);
        }

        // Eliminar duplicados y vacíos
        $bccEmails = array_filter(array_unique($bccEmails));

        if (empty($bccEmails)) {
            Notification::make()
                ->title('Error')
                ->body('Los destinatarios seleccionados no tienen correos electrónicos válidos.')
                ->danger()
                ->send();
            return;
        }

        // Preparar archivos adjuntos
        $attachmentPaths = [];
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $path = Storage::disk('public')->path($attachment);
                if (file_exists($path)) {
                    $attachmentPaths[] = $path;
                }
            }
        }

        try {
            // Enviar el correo con BCC
            $copyEmails = ['subdireccion@isubj.com', 'direccion@isubj.com', 'tarmi13@gmail.com'];

            Mail::to($copyEmails[0])
                ->cc(array_slice($copyEmails, 1))
                ->bcc($bccEmails)
                ->send(new BulkEmailMail(
                    $data['subject'],
                    $data['message'],
                    $attachmentPaths
                ));

            // Limpiar archivos adjuntos temporales
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    Storage::disk('public')->delete($attachment);
                }
            }

            $totalRecipients = count($bccEmails);
            $totalStudents = count($selectedStudents);
            $totalTeachers = count($selectedTeachers);

            Notification::make()
                ->title('Correo Enviado Exitosamente')
                ->body("Se envió el correo a {$totalRecipients} destinatarios ({$totalStudents} alumnos, {$totalTeachers} profesores).")
                ->success()
                ->duration(10000)
                ->send();

            // Resetear el formulario
            $this->form->fill([
                'subject' => null,
                'message' => null,
                'attachments' => [],
                'send_to_students' => false,
                'send_to_teachers' => false,
                'group_filter' => null,
                'selected_students' => [],
                'selected_teachers' => [],
            ]);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al Enviar Correo')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }
}

