<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Livewire\PublicStudentRegistration;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use App\Imports\StudentImport;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use App\Models\Student;
use App\Models\Group;
use App\Models\Inscription;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Html;
use Filament\Forms\Contracts\HasForms;




class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),

            Action::make('import')
                ->label('Importar Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->size(ActionSize::Medium)
                ->modalHeading('Importar Estudiantes desde Excel')
                ->modalSubheading('Sigue las instrucciones para evitar errores')
                ->modalContent(function () {
                    $guideUrl = asset('storage/documentos/import-guides/guia-estudiante.xlsx');

                    return new HtmlString("
        <div class=\"space-y-4 text-sm text-gray-700 dark:text-gray-300\">
            <p><strong>Instrucciones:</strong></p>
            <ul class=\"list-disc pl-5 space-y-1\">
                 <li><strong>Los encabezados deben coincidir exactamente</strong> con los del archivo de guía proporcionado.</li>
    <li>Las columnas deben estar en el siguiente orden: <em>(ver archivo de guía)</em>.</li>
    <li><strong>No debe haber filas vacías</strong> entre los registros.</li>
    <li>Formatos de archivo permitidos: <code>.xlsx</code> o <code>.csv</code>.</li>
    <li>En la columna <strong>Género</strong>, los valores válidos son: <code>Masculino-M</code>, <code>Femenino-F</code> u <code>Otro-O</code>.</li>
    <li>En la columna <strong>Estado del alumno</strong>, los valores válidos son: <code>activo</code>, <code>inactivo</code>, <code>suspendido</code> o <code>graduado</code>.</li>
            </ul>
            <p>
                Puedes descargar la plantilla de ejemplo desde
                <a href=\"{$guideUrl}\" target=\"_blank\" class=\"text-primary-600 underline font-semibold\">
                    este enlace<enlace></enlace>
                </a>.
            </p>
        </div>
    ");
                })

                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->storeFiles(false),
                ])
                ->action(function (array $data) {
                    $uploadedFile = $data['file'];

                    $path = Storage::putFile('imports', $uploadedFile);

                    try {
                        $import = new StudentImport();
                        Excel::import($import, Storage::path($path));

                        Notification::make()
                            ->title('Importación completada')
                            ->success()
                            ->send();
                    } catch (ValidationException $e) {
                        $failures = $e->failures();

                        $message = "Errores encontrados:\n";

                        foreach ($failures as $failure) {
                            $message .= "- Fila {$failure->row()}: ";
                            $message .= implode(', ', $failure->errors()) . "\n";
                        }

                        Notification::make()
                            ->title('Errores en la importación')
                            ->body($message)
                            ->danger()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error inesperado')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('inscribir')
                ->label('Inscribir estudiante')
                ->modalHeading('Inscribir Estudiante')
                ->modalWidth('lg')
                ->form([
                    Select::make('student_id')
                        ->label('Estudiante')
                        ->options(Student::where('status', 'active')->get()->pluck('full_name', 'id')->toArray())
                        ->searchable()
                        ->required(),
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
                            return Group::whereHas('period', function (Builder $query) use ($careerId) {
                                $query->where('career_id', $careerId);
                            })->with('period.career')->get()->pluck('name','id');
                        })
                        ->searchable()
                        ->required()
                        ->reactive(),
                ])
                ->action(function (array $data) {
                    Inscription::create([
                        'student_id' => $data['student_id'],
                        'group_id'   => $data['group_id'],
                        'status'     => 'active',
                    ]);
                    Notification::make()
                        ->title('Inscripción exitosa')
                        ->body('El estudiante ha sido inscrito correctamente.')
                        ->success()
                        ->send();

                })
        ];
    }

}
