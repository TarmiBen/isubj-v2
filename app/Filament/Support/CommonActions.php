<?php

namespace App\Filament\Support;

use App\Models\Document;
use App\Models\Group;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class CommonActions
{
    public static function uploadDocumentModel($model): Action
    {
        return Action::make('uploadDocument')
            ->label('Cargar  Documento')
            ->modalSubmitActionLabel('Cargar')
            ->icon('heroicon-o-document-plus')
            ->form([
                TextInput::make('name')
                    ->label('Nombre del Documento')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('file')
                    ->label('Archivo')
                    ->required()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'application/zip',
                        'application/x-rar-compressed'
                    ])
                    ->maxSize(20480) // 20MB
                    ->directory('assignment-documents')
                    ->preserveFilenames()
            ])
            ->action(function (array $data) use ($model) {
                $fileSize = null;
                $mimeType = null;

                try {
                    if (Storage::exists($data['file'])) {
                        $fileSize = Storage::size($data['file']);
                        $mimeType = Storage::mimeType($data['file']);
                    }
                } catch (\Exception $e) {
                    $fileSize = 0;
                    $mimeType = 'unknown';
                }

                Document::create([
                    'name' => $data['name'],
                    'src' => $data['file'],
                    'documentable_type' => get_class($model),
                    'documentable_id' => $model->id,
                    'meta' => [
                        'size' => $fileSize,
                        'mime_type' => $mimeType
                    ]
                ]);

                Notification::make()
                    ->title('Documento cargado exitosamente')
                    ->success()
                    ->send();

            });
    }

    public static function EnrollStudent($model = null): Action
    {
        return Action::make('inscribir')
            ->label('Inscribir estudiante')
            ->modalHeading($model ? 'Inscribir al estudiante '. $model->name : 'Inscribir estudiante')
            ->modalSubheading('Selecciona la carrera y el grupo al que deseas inscribir al Alumno.')
            ->icon('heroicon-o-plus')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Inscribir')
            ->visible(fn () => static::canManageInscriptions())
            ->form(array_filter([
                !$model ? Select::make('student_id')
                    ->label('Estudiante')
                    ->options(\App\Models\Student::where('status', 'active')->get()->pluck('full_name', 'id')->toArray())
                    ->searchable()
                    ->required() : null,
                Select::make('career_id')
                    ->label('Carrera')
                    ->options(
                        \App\Models\Career::where('status', 'active')
                            ->get()
                            ->mapWithKeys(fn ($career) => [
                                $career->id => "{$career->code} - {$career->name}"
                            ])
                    )
                    ->searchable()
                    ->preload()
                    ->afterStateUpdated(fn (callable $set) => $set('group_id', null))
                    ->helperText('Selecciona primero una carrera'),
                Select::make('group_id')
                    ->label('Grupo')
                    ->options(function (callable $get){
                        $careerId = $get('career_id');
                        if (!$careerId) return [];

                        $activeCycle = \App\Models\Cycle::where('active', true)->first();

                        $query = Group::whereHas('period', function (Builder $query) use ($careerId) {
                            $query->where('career_id', $careerId);
                        })->with('period.career');

                        // Filtrar por ciclo activo
                        if ($activeCycle) {
                            $query->where('cycle_id', $activeCycle->id);
                        }

                        return $query->get()->pluck('code','id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive(),
            ]))
            ->action(function (array $data) use ($model) {
                $activeCycle = \App\Models\Cycle::where('active', true)->first();

                \App\Models\Inscription::create([
                    'student_id' => $model ? $model->id : $data['student_id'],
                    'group_id'   => $data['group_id'],
                    'cycle_id'   => $activeCycle?->id,
                    'status'     => 'active',
                ]);
                Notification::make()
                    ->title('Inscripción exitosa')
                    ->body('El estudiante ha sido inscrito correctamente.')
                    ->success()
                    ->send();
            });
    }

    /** Estados posibles de una inscripción (enum de la tabla `inscriptions`). */
    public const INSCRIPTION_STATUSES = [
        'active'    => 'Activa',
        'inactive'  => 'Inactiva',
        'down'      => 'Baja',
        'abandoned' => 'Abandonó',
    ];

    /**
     * Permiso requerido para crear o modificar la inscripción de un alumno.
     * No existen permisos propios de `inscription`, y una inscripción es parte
     * del expediente académico del alumno, así que se usa el de actualizarlo.
     */
    public static function canManageInscriptions(): bool
    {
        return auth()->user()?->can('update_student') ?? false;
    }

    /**
     * Editar una inscripción existente del alumno: grupo, ciclo y estatus.
     */
    public static function EditInscription($model): Action
    {
        return Action::make('editarInscripcion')
            ->label('Editar inscripción')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->modalHeading('Editar inscripción de ' . ($model->full_name ?? $model->name))
            ->modalDescription('Cambia el grupo, el ciclo o el estatus. Si el alumno tiene varias inscripciones, elige primero cuál vas a modificar.')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Guardar cambios')
            ->visible(fn () => static::canManageInscriptions() && $model->inscriptions()->exists())
            ->fillForm(function () use ($model): array {
                // Si sólo hay una inscripción, se precarga directamente.
                $inscriptions = $model->inscriptions()->with('group.period')->get();

                if ($inscriptions->count() !== 1) {
                    return [];
                }

                $i = $inscriptions->first();

                return [
                    'inscription_id' => $i->id,
                    'career_id'      => $i->group?->period?->career_id,
                    'group_id'       => $i->group_id,
                    'cycle_id'       => $i->cycle_id,
                    'status'         => $i->status,
                ];
            })
            ->form([
                Select::make('inscription_id')
                    ->label('Inscripción')
                    ->options(fn () => \App\Models\Inscription::where('student_id', $model->id)
                        ->with(['group.period.career', 'cycle'])
                        ->get()
                        ->mapWithKeys(fn ($i) => [
                            $i->id => sprintf(
                                '%s — %s%s (%s)',
                                $i->group?->code ?? 'Sin grupo',
                                $i->group?->period?->career?->name ?? 'Sin carrera',
                                $i->cycle?->name ? ' · ' . $i->cycle->name : '',
                                static::INSCRIPTION_STATUSES[$i->status] ?? $i->status,
                            ),
                        ]))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $i = \App\Models\Inscription::with('group.period')->find($state);

                        $set('career_id', $i?->group?->period?->career_id);
                        $set('group_id', $i?->group_id);
                        $set('cycle_id', $i?->cycle_id);
                        $set('status', $i?->status);
                    })
                    ->helperText('Se muestran todas las inscripciones del alumno, incluidas las dadas de baja.'),

                Select::make('career_id')
                    ->label('Carrera')
                    ->options(
                        \App\Models\Career::where('status', 'active')
                            ->get()
                            ->mapWithKeys(fn ($career) => [$career->id => "{$career->code} - {$career->name}"])
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('group_id', null))
                    ->helperText('Cambiarla vuelve a filtrar los grupos.'),

                Select::make('group_id')
                    ->label('Grupo')
                    ->options(function (callable $get) {
                        $careerId = $get('career_id');

                        if (! $careerId) {
                            return [];
                        }

                        return Group::whereHas('period', fn (Builder $q) => $q->where('career_id', $careerId))
                            ->with('period.career')
                            ->get()
                            ->pluck('code', 'id');
                    })
                    ->searchable()
                    ->required(),

                Select::make('cycle_id')
                    ->label('Ciclo')
                    ->options(fn () => \App\Models\Cycle::orderByDesc('active')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Select::make('status')
                    ->label('Estatus')
                    ->options(static::INSCRIPTION_STATUSES)
                    ->required()
                    ->helperText('Sólo las inscripciones activas generan mensualidades automáticas.'),
            ])
            ->action(function (array $data) use ($model): void {
                $inscription = \App\Models\Inscription::where('student_id', $model->id)
                    ->whereKey($data['inscription_id'])
                    ->firstOrFail();

                $inscription->update([
                    'group_id' => $data['group_id'],
                    'cycle_id' => $data['cycle_id'],
                    'status'   => $data['status'],
                ]);

                Notification::make()
                    ->title('Inscripción actualizada')
                    ->body('Los cambios se guardaron correctamente.')
                    ->success()
                    ->send();
            });
    }

    public static function IconReportCardStudent($studentId): Action
    {
        return Action::make('downloadReportCardStudent')
            ->label('Descargar Boleta')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->url(route('student.download-report-card', ['studentId' => $studentId]))
            ->openUrlInNewTab();
    }
}
