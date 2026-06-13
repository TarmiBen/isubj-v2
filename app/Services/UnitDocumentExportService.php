<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitDocumentExportService
{
    /**
     * Genera una copia de solo lectura del documento de calificaciones de la
     * unidad, con un código QR (en AK2:AL5) que
     * enlaza a la vista pública de
     * estatus de la unidad, y protege la hoja para evitar su edición.
     */
    public static function download(Unit $unit): BinaryFileResponse
    {
        $sourcePath = Storage::disk('public')->path($unit->document_src);

        if (!file_exists($sourcePath)) {
            throw new \RuntimeException('El documento de la unidad no se encuentra disponible.');
        }

        $spreadsheet = IOFactory::load($sourcePath);
        $sheet = $spreadsheet->getActiveSheet();

        $qrPath = tempnam(sys_get_temp_dir(), 'unit_qr_') . '.png';
        file_put_contents($qrPath, QrCode::format('png')->size(200)->generate($unit->public_status_url));

        $sheet->mergeCells('AK2:AL5');

        // F5: nombre de la unidad (Unidad 1, Unidad 2, etc.) en negritas.
        $sheet->setCellValue('F5', $unit->name);
        $sheet->getStyle('F5')->getFont()->setBold(true);

        // Fila 10: leyenda de validación, unificada en todo el ancho de la tabla.
        $sheet->mergeCells('A10:AL10');
        $sheet->setCellValue('A10', 'ACTA VALIDADA EN SISTEMA');
        $sheet->getStyle('A10:AL10')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A10:AL10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A10:AL10')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // A partir de la fila 11: separar AK/AL en "Total" (con los valores
        // existentes) y una columna AL vacía reservada para la firma.
        foreach ($sheet->getMergeCells() as $mergeRange) {
            if (preg_match('/^AK(\d+):AL\1$/', $mergeRange, $matches) && (int) $matches[1] >= 11) {
                $sheet->unmergeCells($mergeRange);
            }
        }

        $drawing = new Drawing();
        $drawing->setName('Estatus Unidad QR');
        $drawing->setPath($qrPath);
        $drawing->setResizeProportional(false);
        $drawing->setWidth(70);
        $drawing->setHeight(70);
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setCoordinates('AK2');
        $drawing->setWorksheet($sheet);

        // Proteger la hoja: el documento queda como solo lectura.
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('TarmiBenom1*');

        $tempPath = tempnam(sys_get_temp_dir(), 'unit_doc_') . '.xlsx';
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        @unlink($qrPath);

        $fileName = 'Documento_' .
            str_replace(' ', '_', $unit->name) . '_' .
            str_replace(' ', '_', $unit->assignment->subject->name ?? 'Unidad') . '.xlsx';

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
