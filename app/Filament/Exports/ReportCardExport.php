<?php

namespace App\Filament\Exports;

use App\Models\Student;
use App\Models\FinalGrade;
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
use Illuminate\Support\Facades\Storage;
use App\Helpers\TextHelper;

class ReportCardExport implements WithEvents
{
    protected $student;
    protected $inscription;
    protected $assignments;

    public function __construct(int $studentId)
    {
        // Cargar el estudiante con su última inscripción
        $this->student = Student::with([
            'lastInscription.group.period.career',
            'lastInscription.group.assignments.subject',
            'lastInscription.group.assignments.finalGrades' => function($query) use ($studentId) {
                $query->where('student_id', $studentId)
                      ->orderBy('attempt', 'desc');
            }
        ])->findOrFail($studentId);

        $this->inscription = $this->student->lastInscription;

        // Obtener las asignaturas con sus calificaciones finales
        if ($this->inscription) {
            $this->assignments = $this->inscription->group->assignments()
                ->with(['subject', 'finalGrades' => function($query) use ($studentId) {
                    $query->where('student_id', $studentId)
                          ->orderBy('attempt', 'desc');
                }])
                ->get();
        } else {
            $this->assignments = collect([]);
        }
    }

    /**
     * Convierte un número a texto en español
     */


    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $templatePath = storage_path('app/templates/xlsx/boleta.xlsx');

                if (!file_exists($templatePath)) {
                    Log::error('Template file not found: ' . $templatePath);
                    throw new \Exception('Template file not found: boleta.xlsx');
                }

                // Crear un archivo temporal único
                $tempPath = tempnam(sys_get_temp_dir(), 'boleta_template_') . '.xlsx';

                // Copiar el template completo preservando todas las partes del archivo
                if (!copy($templatePath, $tempPath)) {
                    Log::error('Failed to copy template file');
                    throw new \Exception('Failed to copy template file');
                }

                try {
                    // Cargar el spreadsheet del template
                    $reader = IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(false);
                    $reader->setReadEmptyCells(true);
                    $spreadsheet = $reader->load($tempPath);
                    $sheet = $spreadsheet->getActiveSheet();

                    // Llenar información del estudiante
                    $sheet->setCellValue('C6',  TextHelper::toUpperWithoutAccents($this->student->fullName) ?? '');

                    // Nombre de la carrera
                    if ($this->inscription) {
                        $careerName = $this->inscription->group->period->career->name ?? '';
                        $sheet->setCellValue('J6', $careerName);
                        if($this->inscription->group->period->career->rvoe){
                            $sheet->setCellValue('J7', 'RVOE');
                            $sheet->setCellValue('J8', $this->inscription->group->period->career->rvoe);
                        }

                        // Semestre
                        $semestre = $this->inscription->group->period->name ?? '';
                        $sheet->setCellValue('F8', $semestre);
                    }

                    // Llenar las asignaturas y calificaciones a partir de la fila 11
                    $row = 11;
                    $totalGrades = 0;
                    $gradeCount = 0;

                    foreach ($this->assignments as $assignment) {
                        // Obtener la última calificación final
                        $finalGrade = $assignment->finalGrades->first();

                        if ($finalGrade) {
                            // Clave de materia
                            $sheet->setCellValue("C{$row}", $assignment->subject->code ?? '');

                            // Nombre de la materia
                            $sheet->setCellValue("E{$row}", $assignment->subject->name ?? '');

                            // Calificación final
                            $sheet->setCellValue("M{$row}", $finalGrade->grade);

                            // Observación (tipo de intento)
                            $attemptType = $finalGrade->getAttemptTypeAttribute();
                            $sheet->setCellValue("N{$row}", $attemptType);

                            // Acumular para el promedio
                            $totalGrades += $finalGrade->grade;
                            $gradeCount++;

                            $row++;
                        }
                    }

                    // Calcular el promedio y convertir a palabras
                    if ($gradeCount > 0) {
                        $average = $totalGrades / $gradeCount;
                        $roundedAverage = round($average, 1);

                        // Convertir a palabras
                        $averageInWords = TextHelper::numberToWords($roundedAverage);

                        // Colocar el promedio en texto en O24
                        $sheet->setCellValue('O24', $averageInWords);
                        $sheet->setCellValue('L24', $roundedAverage);
                    }


                    // Guardar el archivo modificado
                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    $writer->save($tempPath);

                    // Usar el archivo modificado como template
                    $template = new LocalTemporaryFile($tempPath);
                    $event->writer->reopen($template, Excel::XLSX);

                } catch (\Exception $e) {
                    Log::error('Error processing Excel template: ' . $e->getMessage());
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                    throw $e;
                }

                // Limpiar archivo temporal
                register_shutdown_function(function() use ($tempPath) {
                    if (file_exists($tempPath)) {
                        unlink($tempPath);
                    }
                });
            },
        ];
    }
}

