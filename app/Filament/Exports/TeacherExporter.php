<?php

namespace App\Filament\Exports;

use App\Models\Teacher;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class TeacherExporter extends Exporter
{
    protected static ?string $model = Teacher::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee_number')->label('Número de empleado'),
            ExportColumn::make('first_name')->label('Nombre'),
            ExportColumn::make('last_name1')->label('Apellido Paterno'),
            ExportColumn::make('last_name2')->label('Apellido Materno'),
            //ExportColumn::make('gender')->label('Género'),
            //ExportColumn::make('date_of_birth')->label('Fecha de Nacimiento'),
            ExportColumn::make('curp')->label('CURP'),
            ExportColumn::make('email')->label('Correo Electrónico'),
            ExportColumn::make('phone')->label('Teléfono'),
            ExportColumn::make('mobile')->label('Teléfono Móvil'),
            //ExportColumn::make('hire_date')->label('Fecha de Contratación'),
            ExportColumn::make('street')->label('Calle'),
            ExportColumn::make('city')->label('Ciudad'),
            ExportColumn::make('state')->label('Estado'),
            ExportColumn::make('postal_code')->label('C.P.'),
            ExportColumn::make('country')->label('País'),
            ExportColumn::make('title')->label('Título'),
            ExportColumn::make('specialization')->label('Especialización'),
            ExportColumn::make('emergency_contact_name')->label('Nombre de Contacto de Emergencia'),
            ExportColumn::make('emergency_contact_phone')->label('Teléfono de Contacto de Emergencia'),

        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de su profesor se ha completado y ' . number_format($export->successful_rows) . ' ' . str('filas')->plural($export->successful_rows) . ' fueron exportadas.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
