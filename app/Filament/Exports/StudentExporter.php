<?php

namespace App\Filament\Exports;

use App\Models\Student;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StudentExporter extends Exporter
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student_number')->label('Número de estudiante'),
            ExportColumn::make('name')->label('Nombre'),
            ExportColumn::make('last_name1')->label('Apellido Paterno'),
            ExportColumn::make('last_name2')->label('Apellido Materno'),
            //ExportColumn::make('gender')->label('Género'),
            /**ExportColumn::make('date_of_birth')
                ->label('Fecha de Nacimiento')
                ->formatStateUsing(fn ($state) => $state && strtotime($state) !== false
                    ? \Carbon\Carbon::parse($state)->format('d/m/Y')
                    : 'Sin fecha'),**/
            ExportColumn::make('email')->label('Correo Electrónico'),
            ExportColumn::make('phone')->label('Teléfono'),
            ExportColumn::make('street')->label('Calle'),
            ExportColumn::make('city')->label('Ciudad'),
            ExportColumn::make('state')->label('Estado'),
            ExportColumn::make('postal_code')->label('C.P.'),
            ExportColumn::make('country')->label('País'),
            //ExportColumn::make('enrollment_date')->label('Fecha de Inscripción'),
            ExportColumn::make('guardian_name')->label('Nombre del Tutor'),
            ExportColumn::make('guardian_phone')->label('Teléfono del Tutor'),
            ExportColumn::make('emergency_contact_name')->label('Nombre de Contacto de Emergencia'),
            ExportColumn::make('emergency_contact_phone')->label('Emergencia Teléfono'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Su exportación de estudiante se ha completado y ' . number_format($export->successful_rows) . ' ' . str('filas')->plural($export->successful_rows) . ' exportadas.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' fallo en la exportación.';
        }

        return $body;
    }

}
