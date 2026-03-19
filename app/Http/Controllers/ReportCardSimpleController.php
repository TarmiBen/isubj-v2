<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FinalGrade;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportCardSimpleController extends Controller
{
    public function downloadSimple(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id'
        ]);

        try {
            $studentId = $request->input('student_id');

            // Cargar el estudiante con sus relaciones básicas
            $student = Student::with([
                'inscriptions.group.period.career',
                'inscriptions.group.assignments.subject'
            ])->findOrFail($studentId);

            // Obtener la inscripción más reciente
            $inscription = $student->inscriptions()->with([
                'group.period.career',
                'group.assignments.subject'
            ])->latest()->first();

            if (!$inscription) {
                return back()->with('error', 'El estudiante no tiene inscripciones activas.');
            }

            // Obtener las asignaturas del grupo actual
            $assignments = $inscription->group->assignments()->with('subject')->get();

            // Obtener las calificaciones finales del estudiante
            $assignmentIds = $assignments->pluck('id');
            $finalGrades = collect();

            try {
                $finalGradesData = FinalGrade::where('student_id', $studentId)
                    ->whereIn('assignment_id', $assignmentIds)
                    ->get();

                $finalGrades = $finalGradesData->groupBy('assignment_id')
                    ->map(function($grades) {
                        return $grades->sortByDesc('attempt')->first();
                    });
            } catch (\Exception $e) {
                Log::info('No se pudieron cargar calificaciones finales: ' . $e->getMessage());
            }

            // Crear archivo Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar título principal
            $sheet->setCellValue('A1', 'BOLETA DE CALIFICACIONES');
            $sheet->mergeCells('A1:P2');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

            // Información del estudiante
            $sheet->setCellValue('B5', 'Nombre:');
            $sheet->setCellValue('C6', $student->full_name);

            $career = $inscription->group->period->career ?? null;
            $period = $inscription->group->period ?? null;

            $sheet->setCellValue('I5', 'Carrera:');
            $sheet->setCellValue('J6', $career ? $career->name : 'N/A');

            $sheet->setCellValue('E7', 'Semestre:');
            $sheet->setCellValue('F8', $period ? $period->name : 'N/A');

            // Encabezados de la tabla
            $sheet->setCellValue('C10', 'CLAVE');
            $sheet->setCellValue('E10', 'MATERIA');
            $sheet->setCellValue('M10', 'CALIFICACIÓN');
            $sheet->setCellValue('N10', 'OBSERVACIONES');

            // Aplicar estilos a los encabezados
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('C10:N10')->applyFromArray($headerStyle);

            // Llenar materias y calificaciones
            $row = 11;
            $totalGrades = 0;
            $gradeCount = 0;

            foreach ($assignments as $assignment) {
                $subject = $assignment->subject;
                $finalGrade = $finalGrades->get($assignment->id);

                $sheet->setCellValue('C' . $row, $subject->code ?? 'N/A');
                $sheet->setCellValue('E' . $row, $subject->name ?? 'N/A');

                if ($finalGrade) {
                    $sheet->setCellValue('M' . $row, number_format($finalGrade->grade, 1));
                    $attemptType = $finalGrade->getAttemptTypeAttribute();
                    $sheet->setCellValue('N' . $row, $attemptType);

                    $totalGrades += $finalGrade->grade;
                    $gradeCount++;
                } else {
                    $sheet->setCellValue('M' . $row, 'S/C');
                    $sheet->setCellValue('N' . $row, '');
                }

                $row++;
            }

            // Promedio
            if ($gradeCount > 0) {
                $average = $totalGrades / $gradeCount;
                $averageRounded = round($average, 1);
                $averageInWords = $this->numberToWords($averageRounded);

                $sheet->setCellValue('N24', 'Promedio General:');
                $sheet->setCellValue('O24', $averageInWords);
            } else {
                $sheet->setCellValue('N24', 'Promedio General:');
                $sheet->setCellValue('O24', 'Sin calificaciones');
            }

            // Ajustar anchos de columnas
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(30);
            $sheet->getColumnDimension('M')->setWidth(12);
            $sheet->getColumnDimension('N')->setWidth(15);
            $sheet->getColumnDimension('O')->setWidth(20);

            // Guardar archivo
            $fileName = 'boleta_' . $student->student_number . '_' . date('Y-m-d') . '.xlsx';
            $filePath = storage_path('app/public/exports/' . $fileName);

            // Crear directorio si no existe
            $directory = dirname($filePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            if (file_exists($filePath)) {
                return response()->download($filePath)->deleteFileAfterSend(true);
            } else {
                return back()->with('error', 'Error al generar la boleta de calificaciones.');
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar boleta simple: ' . $e->getMessage());
            return back()->with('error', 'Error al generar la boleta: ' . $e->getMessage());
        }
    }

    private function numberToWords($number)
    {
        $units = [
            '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'
        ];

        $integerPart = floor($number);
        $decimalPart = round(($number - $integerPart) * 10);

        $result = '';

        if ($integerPart == 0) {
            $result = 'cero';
        } elseif ($integerPart <= 9) {
            $result = $units[$integerPart];
        } elseif ($integerPart == 10) {
            $result = 'diez';
        }

        if ($decimalPart > 0) {
            $result .= ' punto ' . $units[$decimalPart];
        }

        return ucfirst($result);
    }
}
