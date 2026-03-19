<?php

namespace App\Http\Controllers;

use App\Filament\Exports\ReportCardExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReportCardController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id'
        ]);

        try {
            $studentId = $request->input('student_id');
            $export = new ReportCardExport($studentId);
            $filePath = $export->export();

            if (file_exists($filePath)) {
                return response()->download($filePath)->deleteFileAfterSend(true);
            } else {
                return back()->with('error', 'Error al generar la boleta de calificaciones.');
            }
        } catch (\Exception $e) {
            Log::error('Error al descargar boleta: ' . $e->getMessage());
            return back()->with('error', 'Error al generar la boleta: ' . $e->getMessage());
        }
    }
}
