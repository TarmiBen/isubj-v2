<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Imports\StudentImport;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;


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
                })
        ];
    }
}
