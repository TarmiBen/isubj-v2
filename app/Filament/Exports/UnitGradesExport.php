<?php

namespace App\Filament\Exports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Illuminate\Support\Facades\Log;

class UnitGradesExport implements WithTitle, WithEvents
{
    protected $unit, $students, $rubros;

    /**
     * Convierte un número de columna (0-based) a letra de columna de Excel
     */
    private function numberToColumnLetter($columnNumber)
    {
        $columnLetter = '';
        while ($columnNumber >= 0) {
            $columnLetter = chr(($columnNumber % 26) + ord('A')) . $columnLetter;
            $columnNumber = intval($columnNumber / 26) - 1;
        }
        return $columnLetter;
    }

    /**
     * Convierte una letra de columna de Excel a número (0-based)
     */
    private function columnLetterToNumber($columnLetter)
    {
        $columnNumber = 0;
        $length = strlen($columnLetter);
        for ($i = 0; $i < $length; $i++) {
            $columnNumber = $columnNumber * 26 + (ord($columnLetter[$i]) - ord('A') + 1);
        }
        return $columnNumber - 1;
    }

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;

        $this->students = $this->unit->assignment->group->inscriptions()
            ->with(['student' => function ($query) {
                $query->where('status', 'active')
                    ->orderBy('last_name1')
                    ->orderBy('last_name2')
                    ->orderBy('name')
                    ->select('id', 'name', 'last_name1', 'last_name2');
            }])
            ->whereHas('student', function ($query) {
                $query->where('status', 'active');
            })
            ->get();

        $this->rubros = $this->unit->meta['rubros'] ?? [];
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/xlsx/format_unity.xlsx');

                if (file_exists($templatePath)) {
                    // Crear un archivo temporal único
                    $tempPath = tempnam(sys_get_temp_dir(), 'unity_template_') . '.xlsx';

                    // Copiar el template completo preservando todas las partes del archivo
                    if (!copy($templatePath, $tempPath)) {
                        Log::error('Failed to copy template file');
                        return;
                    }

                    try {
                        // Cargar el spreadsheet del template con todas sus partes
                        $reader = IOFactory::createReader('Xlsx');
                        $reader->setReadDataOnly(false); // Importante: leer todo, incluidas las imágenes
                        $reader->setReadEmptyCells(true);
                        $spreadsheet = $reader->load($tempPath);
                        $sheet = $spreadsheet->getActiveSheet();

                    // Escribir información de la asignación
                    $sheet->setCellValue("F2", config('app.name') ?? 'Sistema de Gestión');
                    $sheet->setCellValue("F4", $this->unit->assignment->group->period->career->name ?? 'Sin carrera');

                    $sheet->setCellValue("D7", $this->unit->assignment->group->period->number ?? 'Sin Semestre');
                    $sheet->setCellValue("D8", $this->unit->assignment->subject->name ?? 'Sin materia');
                    $sheet->setCellValue("D9", $this->unit->id ?? '0');

                    $sheet->setCellValue("Y7", $this->unit->assignment->teacher->fullName() ?? 'Sin profesor');
                    $sheet->setCellValue("Y8", $this->unit->assignment->group->period->career->modality->name ?? 'Sin modalidad');
                    $sheet->setCellValue("Y9", $this->unit->assignment->group->code ?? 'Sin grupo');

                    // Escribir cabeceras básicas en fila 11
                    $sheet->setCellValue("A11", "#");
                    $sheet->setCellValue("B11", "Nombre");
                    $sheet->setCellValue("C11", "Apellido P.");
                    $sheet->setCellValue("D11", "Apellido M.");

                    // Calcular distribución de columnas para rubros (E a AJ = 32 columnas disponibles)
                    $columnasDisponibles = 32; // De E a AJ
                    $numRubros = count($this->rubros);

                    if ($numRubros > 0) {
                        $columnasPorRubro = floor($columnasDisponibles / $numRubros);
                        $columnasExtras = $columnasDisponibles % $numRubros;

                        $currentColumnIndex = $this->columnLetterToNumber('E'); // Empezar en columna E

                        foreach ($this->rubros as $index => $rubro) {
                            $columnsForThisRubro = $columnasPorRubro;
                            if ($index < $columnasExtras) {
                                $columnsForThisRubro++; // Distribuir columnas extras
                            }

                            // Calcular columna inicial y final para este rubro
                            $currentColumn = $this->numberToColumnLetter($currentColumnIndex);
                            $endColumnIndex = $currentColumnIndex + $columnsForThisRubro - 1;
                            $endColumn = $this->numberToColumnLetter($endColumnIndex);

                            // Combinar celdas para el encabezado del rubro
                            if ($columnsForThisRubro > 1) {
                                $sheet->mergeCells("{$currentColumn}11:{$endColumn}11");
                            }

                            // Establecer el valor del encabezado del rubro
                            $sheet->setCellValue("{$currentColumn}11", $rubro['nombre'] . " ({$rubro['valor']}%)");

                            // Aplicar estilos al encabezado
                            $sheet->getStyle("{$currentColumn}11:{$endColumn}11")
                                ->getAlignment()
                                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                                ->setVertical(Alignment::VERTICAL_CENTER);

                            $sheet->getStyle("{$currentColumn}11:{$endColumn}11")
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()
                                ->setARGB('FF000000'); // Negro

                            $sheet->getStyle("{$currentColumn}11:{$endColumn}11")
                                ->getFont()
                                ->getColor()
                                ->setARGB('FFFFFFFF'); // Texto blanco

                            // Avanzar a la siguiente columna
                            $currentColumnIndex = $endColumnIndex + 1;
                        }
                    }

                    // Combinar columnas AK y AL para "Total"
                    $sheet->mergeCells("AK11:AL11");
                    $sheet->setCellValue("AK11", "Total");
                    $sheet->getStyle("AK11:AL11")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $sheet->getStyle("AK11:AL11")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FF000000'); // Negro

                    $sheet->getStyle("AK11:AL11")
                        ->getFont()
                        ->getColor()
                        ->setARGB('FFFFFFFF'); // Texto blanco

                    // Escribir estudiantes y configurar validación
                    $filaInicio = 12;
                    $row = $filaInicio;
                    $maxRow = $filaInicio + count($this->students) - 1;

                    foreach ($this->students as $inscription) {
                        $student = $inscription->student;
                        if ($student) {
                            $sheet->setCellValue("A{$row}", $student->id);
                            $sheet->setCellValue("B{$row}", $student->name);
                            $sheet->setCellValue("C{$row}", $student->last_name1);
                            $sheet->setCellValue("D{$row}", $student->last_name2);

                            // Configurar validación de datos y fórmulas para rubros
                            if ($numRubros > 0) {
                                $currentColumnIndex = $this->columnLetterToNumber('E'); // Empezar en columna E
                                $columnasPorRubro = floor($columnasDisponibles / $numRubros);
                                $columnasExtras = $columnasDisponibles % $numRubros;
                                $totalFormulaParts = [];

                                foreach ($this->rubros as $index => $rubro) {
                                    $columnsForThisRubro = $columnasPorRubro;
                                    if ($index < $columnasExtras) {
                                        $columnsForThisRubro++;
                                    }

                                    // Calcular columna inicial y final para este rubro
                                    $currentColumn = $this->numberToColumnLetter($currentColumnIndex);
                                    $endColumnIndex = $currentColumnIndex + $columnsForThisRubro - 1;
                                    $endColumn = $this->numberToColumnLetter($endColumnIndex);

                                    // Combinar celdas para este rubro en esta fila de estudiante
                                    if ($columnsForThisRubro > 1) {
                                        $sheet->mergeCells("{$currentColumn}{$row}:{$endColumn}{$row}");
                                    }

                                    // Aplicar validación de datos para no exceder el valor del rubro
                                    for ($colIndex = $currentColumnIndex; $colIndex <= $endColumnIndex; $colIndex++) {
                                        $col = $this->numberToColumnLetter($colIndex);
                                        $validation = $sheet->getCell("{$col}{$row}")->getDataValidation();
                                        $validation->setType(DataValidation::TYPE_CUSTOM);
                                        $validation->setFormula1("AND({$col}{$row}>=0,{$col}{$row}<={$rubro['valor']})");
                                        $validation->setShowErrorMessage(true);
                                        $validation->setErrorTitle('Valor inválido');
                                        $validation->setError("El valor debe estar entre 0 y {$rubro['valor']} puntos para el rubro '{$rubro['nombre']}'");
                                        $validation->setPromptTitle('Ingrese calificación');
                                        $validation->setPrompt("Ingrese un valor entre 0 y {$rubro['valor']} para '{$rubro['nombre']}'");
                                        $validation->setShowInputMessage(true);


                                    }

                                    if ($columnsForThisRubro == 1) {
                                        $totalFormulaParts[] = $currentColumn . $row;
                                    } else {
                                        $totalFormulaParts[] = "SUM({$currentColumn}{$row}:{$endColumn}{$row})";
                                    }


                                    // Avanzar a la siguiente columna
                                    $currentColumnIndex = $endColumnIndex + 1;
                                }

                                // Establecer fórmula del total en columnas AK-AL
                                // Establecer fórmula del total en columnas AK-AL
                                if (!empty($totalFormulaParts)) {
                                    $suma = "SUM(E{$row}:AJ{$row})";
                                    $totalFormula = "=IF(({$suma})<7,ROUNDDOWN({$suma},1),ROUND({$suma},1))";
                                    $sheet->setCellValue("AK{$row}", $totalFormula);
                                    $sheet->mergeCells("AK{$row}:AL{$row}");
                                }

                            }

                            $row++;
                        }
                    }

                    // Aplicar bordes a toda la tabla
                    $lastColumn = 'AL';
                    $sheet->getStyle("A11:{$lastColumn}{$maxRow}")
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(Border::BORDER_THIN);

                    // Aplicar estilos a las cabeceras básicas
                    $sheet->getStyle("A11:D11")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB('FF000000'); // Negro

                    $sheet->getStyle("A11:D11")
                        ->getFont()
                        ->getColor()
                        ->setARGB('FFFFFFFF'); // Texto blanco

                    $sheet->getStyle("A11:D11")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Ajustar ancho de columnas
                    $sheet->getColumnDimension('A')->setWidth(8);
                    $sheet->getColumnDimension('B')->setWidth(15);
                    $sheet->getColumnDimension('C')->setWidth(15);
                    $sheet->getColumnDimension('D')->setWidth(15);

                    // Ajustar ancho de columnas de rubros (0.70 cm ≈ 2.65 caracteres)
                    $startColIndex = $this->columnLetterToNumber('E');
                    $endColIndex = $this->columnLetterToNumber('AJ');
                    for ($colIndex = $startColIndex; $colIndex <= $endColIndex; $colIndex++) {
                        $col = $this->numberToColumnLetter($colIndex);
                        $sheet->getColumnDimension($col)->setWidth(2.65);
                    }

                    $sheet->getColumnDimension('AK')->setWidth(10);
                    $sheet->getColumnDimension('AL')->setWidth(10);

                        // Guardar el archivo modificado manteniendo todas las partes del Excel
                        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                        $writer->save($tempPath);

                        // Usar el archivo modificado como template
                        $template = new LocalTemporaryFile($tempPath);
                        $event->writer->reopen($template, Excel::XLSX);

                    } catch (\Exception $e) {
                        Log::error('Error processing Excel template: ' . $e->getMessage());
                        // Limpiar archivo temporal en caso de error
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                        throw $e;
                    }

                    // Limpiar archivo temporal - se hará después de que se complete la exportación
                    register_shutdown_function(function() use ($tempPath) {
                        if (file_exists($tempPath)) {
                            unlink($tempPath);
                        }
                    });
                } else {
                    Log::error('Template file not found: ' . $templatePath);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Calificaciones_Unidad_' . $this->unit->name;
    }
}

