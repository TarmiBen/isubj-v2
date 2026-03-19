<?php

namespace App\Filament\Exports;

use App\Models\Assignment;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


class AssignmentAttendanceExport implements WithTitle, WithEvents
{
    protected $assignment, $students;

    public function __construct(Assignment $assignment)
    {
        $this->assignment = $assignment;

        $this->students = $this->assignment->group->inscriptions()
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
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/xlsx/attendance.xlsx');

                if (file_exists($templatePath)) {
                    // Crear un archivo temporal único
                    $tempPath = tempnam(sys_get_temp_dir(), 'attendance_template_') . '.xlsx';

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
                        $sheet->setCellValue("F4", $this->assignment->group->period->career->name ?? 'Sin carrera');

                        $sheet->setCellValue("D7", $this->assignment->group->period->number ?? 'Sin Semestre');
                        $sheet->setCellValue("D8", $this->assignment->subject->name ?? 'Sin materia');
                        $sheet->setCellValue("D9", $this->assignment->id ?? '0');

                        $sheet->setCellValue("Y7", $this->assignment->teacher->fullName() ?? 'Sin profesor');
                        $sheet->setCellValue("Y8", $this->assignment->group->period->career->modality->name ?? 'Sin modalidad');
                        $sheet->setCellValue("Y9", $this->assignment->group->code ?? 'Sin grupo');

                        // Escribir estudiantes
                        $filaInicio = 12;
                        $row = $filaInicio;

                        foreach ($this->students as $inscription) {
                            $student = $inscription->student;
                            if ($student) {
                                $sheet->setCellValue("A{$row}", $student->id);
                                $sheet->setCellValue("B{$row}", $student->name);
                                $sheet->setCellValue("C{$row}", $student->last_name1);
                                $sheet->setCellValue("D{$row}", $student->last_name2);
                                $row++;
                            }
                        }

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
        return 'Asistencia';
    }



}

