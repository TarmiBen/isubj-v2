<?php

namespace App\Filament\Actions;

use App\Filament\Exports\UnitGradesExport;
use App\Models\Unit;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class ExportUnitGradesAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'exportUnitGrades')
            ->label('Exportar Calificaciones')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function (Unit $record) {
                $fileName = 'Calificaciones_' .
                           str_replace(' ', '_', $record->name) . '_' .
                           str_replace(' ', '_', $record->assignment->subject->name) . '_' .
                           now()->format('Y-m-d_H-i-s') . '.xlsx';

                return Excel::download(
                    new UnitGradesExport($record),
                    $fileName,
                    \Maatwebsite\Excel\Excel::XLSX
                );
            })
            ->requiresConfirmation()
            ->modalHeading('Exportar Calificaciones de Unidad')
            ->modalDescription('Se generará un archivo Excel con el formato de calificaciones para esta unidad.')
            ->modalSubmitActionLabel('Exportar');
    }
}
