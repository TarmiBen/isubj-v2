<?php

namespace App\Filament\Resources\TeacherResource\Pages;

use App\Filament\Resources\TeacherResource;
use App\Imports\TeacherImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ListTeachers extends ListRecords
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->icon('heroicon-o-plus'),
            Action::make('import')
                ->label('Importar Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->size(ActionSize::Medium)
                ->modalHeading('Importar Profesores desde Excel')
                ->modalSubheading('Sigue las instrucciones para evitar errores')
                ->modalContent(function () {
                    $guideUrl = asset('storage/documentos/import-guides/guia-profesor.xlsx');

                    return new HtmlString("
        <div class=\"space-y-4 text-sm text-gray-700 dark:text-gray-300\">
            <p><strong>Instrucciones:</strong></p>
            <ul class=\"list-disc pl-5 space-y-1\">
                 <li><strong>Los encabezados deben coincidir exactamente</strong> con los del archivo de guía proporcionado.</li>
                 <li><strong>No cambiar los nombres de los encabezados</strong></li>
    <li>Las columnas deben estar en el siguiente orden: <em>(ver archivo de guía)</em>.</li>
    <li><strong>No debe haber filas vacías</strong> entre los registros.</li>
    <li>Formatos de archivo permitidos: <code>.xlsx</code> o <code>.csv</code>.</li>
    <li>En la columna <strong>Género</strong>, los valores válidos son: <code>Masculino-M</code>, <code>Femenino-F</code> u <code>Otro-O</code>.</li>
    <li>En la columna <strong>estado</strong>, los valores válidos son: <code>activo</code>, <code>inactivo</code>, <code>suspendido</code> o <code>retirado</code>.</li>
            </ul>
            <p>
                Puedes descargar la plantilla de ejemplo desde
                <a href=\"{$guideUrl}\" target=\"_blank\" class=\"text-primary-600 underline font-semibold\">
                    este enlace <enlace></enlace>
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
                        $import = new TeacherImport();
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
