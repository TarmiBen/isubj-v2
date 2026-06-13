<?php

namespace App\Filament\Exports;

use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InformativeGradesExport implements WithEvents
{
    protected Student $student;
    protected int $unitIndex;
    protected $assignments;

    /**
     * Mapa de las 10 posiciones de materia del template a sus celdas
     * de nombre ({{materiaN}}) y calificación ({{cN}}).
     */
    protected array $slots = [
        1 => ['B9', 'C9'],
        2 => ['B10', 'C10'],
        3 => ['B11', 'C11'],
        4 => ['B12', 'C12'],
        5 => ['B13', 'C13'],
        6 => ['E9', 'F9'],
        7 => ['E10', 'F10'],
        8 => ['E11', 'F11'],
        9 => ['E12', 'F12'],
        10 => ['E13', 'F13'],
    ];

    public function __construct(int $studentId, int $unitIndex)
    {
        $this->student = Student::findOrFail($studentId);
        $this->unitIndex = $unitIndex;

        $inscription = $this->student->lastInscription;

        $this->assignments = $inscription
            ? $inscription->group->assignments()
                ->with([
                    'subject',
                    'units' => fn ($query) => $query->orderBy('id'),
                    'units.qualification' => fn ($query) => $query->where('student_id', $studentId),
                ])
                ->get()
            : collect();
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/xlsx/reporte_calificaciones_bach.xlsx');

                if (!file_exists($templatePath)) {
                    Log::error('Template file not found: ' . $templatePath);
                    throw new \Exception('Template file not found: reporte_calificaciones_bach.xlsx');
                }

                $tempPath = tempnam(sys_get_temp_dir(), 'informativo_') . '.xlsx';

                if (!copy($templatePath, $tempPath)) {
                    throw new \Exception('Failed to copy template file');
                }

                try {
                    $reader = IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(false);
                    $reader->setReadEmptyCells(true);
                    $spreadsheet = $reader->load($tempPath);
                    $sheet = $spreadsheet->getActiveSheet();

                    $sheet->setCellValue('B6', 'Alumno:  ' . $this->student->fullName);

                    $cValues = [];
                    $observaciones = [];
                    $assignments = $this->assignments->values();

                    for ($i = 1; $i <= 10; $i++) {
                        [$nameCell, $gradeCell] = $this->slots[$i];
                        $assignment = $assignments->get($i - 1);

                        if (!$assignment) {
                            $sheet->setCellValue($nameCell, '');
                            $sheet->setCellValue($gradeCell, '');
                            continue;
                        }

                        $sheet->setCellValue($nameCell, $assignment->subject->name ?? '');

                        $unit = $assignment->units->sortBy('id')->values()->get($this->unitIndex - 1);

                        if (!$unit) {
                            $sheet->setCellValue($gradeCell, '');
                            continue;
                        }

                        $score = $unit->qualification->score ?? null;

                        if (is_numeric($score)) {
                            $sheet->setCellValue($gradeCell, $score);
                            $cValues[] = (float) $score;
                        } else {
                            $sheet->setCellValue($gradeCell, '');
                        }

                        $observaciones[] = $this->buildObservacionLine($assignment, $unit);
                    }

                    $sheet->setCellValue('E15', !empty($cValues)
                        ? round(array_sum($cValues) / count($cValues), 1)
                        : '');

                    $sheet->setCellValue('B18', implode("\n", $observaciones));
                    $sheet->getStyle('B18')->getAlignment()->setWrapText(true);

                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    $writer->save($tempPath);

                    $template = new LocalTemporaryFile($tempPath);
                    $event->writer->reopen($template, Excel::XLSX);
                } catch (\Throwable $e) {
                    Log::error('Error processing informative report template: ' . $e->getMessage());
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                    throw $e;
                }

                register_shutdown_function(function () use ($tempPath) {
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                });
            },
        ];
    }

    /**
     * Construye la línea de observaciones para una materia/unidad: lee el
     * documento de calificaciones de la unidad (si existe), localiza la fila
     * del alumno y extrae las ponderaciones registradas en E:AJ.
     */
    private function buildObservacionLine($assignment, $unit): string
    {
        $subjectName = $assignment->subject->name ?? '';
        $unitName = $unit->name;

        if (empty($unit->document_src)) {
            return "{$subjectName} - {$unitName}: Sin documento cargado";
        }

        $path = Storage::disk('public')->path($unit->document_src);

        if (!file_exists($path)) {
            return "{$subjectName} - {$unitName}: Sin documento cargado";
        }

        $worksheet = IOFactory::load($path)->getActiveSheet();

        $studentRow = null;
        $row = 12;

        while (true) {
            $studentId = $worksheet->getCell("A{$row}")->getValue();

            if ($studentId === null || $studentId === '') {
                break;
            }

            if ((int) $studentId === $this->student->id) {
                $studentRow = $row;
                break;
            }

            $row++;
        }

        if (!$studentRow) {
            return "{$subjectName} - {$unitName}: Alumno no encontrado en el documento";
        }

        $values = [];
        $startCol = Coordinate::columnIndexFromString('E');
        $endCol = Coordinate::columnIndexFromString('AJ');

        for ($col = $startCol; $col <= $endCol; $col++) {
            $columnLetter = Coordinate::stringFromColumnIndex($col);

            // La cabecera de la fila 11 marca el inicio de cada rubro
            // (las columnas restantes del rubro están combinadas y vienen vacías).
            $header = trim((string) $worksheet->getCell("{$columnLetter}11")->getValue());

            if ($header === '') {
                continue;
            }

            $value = $worksheet->getCell("{$columnLetter}{$studentRow}")->getCalculatedValue();
            $values[] = "{$header}: " . (($value !== null && $value !== '') ? $value : 0);
        }

        if (empty($values)) {
            return "{$subjectName} - {$unitName}: Sin ponderaciones registradas";
        }

        return "{$subjectName} - {$unitName}: " . implode(', ', $values);
    }
}
