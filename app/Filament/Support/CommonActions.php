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
