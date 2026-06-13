<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UnitDocumentExportService
{
    /**
     * Genera una copia de solo lectura del documento de calificaciones de la
     * unidad, con un código QR (en AK2:AL5) que enlaza a la vista pública de
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