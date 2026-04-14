<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Livewire\PublicStudentRegistrationForm;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\FileAccessController;
use App\Http\Controllers\SurveyPdfController;
use App\Livewire\PublicSurveyForm;

Route::view('/', 'welcome');
Route::get('student/create', \App\Livewire\PublicStudentRegistration::class)->name('student.create');

// Rutas para el sistema de evaluación docente
Route::get('/evaluacion', PublicSurveyForm::class)->name('survey.public');
Route::get('/evaluacion/{code}', PublicSurveyForm::class)->name('survey.public.code');

// Ruta para descargar PDF de reporte de evaluación docente
Route::get('/admin/surveys/{survey}/pdf', [SurveyPdfController::class, 'download'])
    ->middleware(['auth'])
    ->name('surveys.pdf');

// Ruta para descargar boleta de calificaciones
Route::get('/student/{studentId}/download-report-card', function($studentId) {
    $student = \App\Models\Student::findOrFail($studentId);
    $fileName = 'Boleta_' . $student->student_number . '_' . date('Y-m-d') . '.xlsx';

    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Filament\Exports\ReportCardExport($studentId),
        $fileName
    );
})->middleware(['auth'])->name('student.download-report-card');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');


// Ruta de descarga de credenciales generadas
Route::get('/admin/credenciales/download/{uuid}', function (string $uuid) {
    $data = \Illuminate\Support\Facades\Cache::get("credencial_{$uuid}");

    if (!$data || !file_exists($data['path'])) {
        abort(404, 'Credencial no encontrada o expirada.');
    }

    $path = $data['path'];
    $name = $data['name'];

    \Illuminate\Support\Facades\Cache::forget("credencial_{$uuid}");

    return response()->download($path, $name)->deleteFileAfterSend(true);
})->middleware(['auth'])->name('credenciales.download');

require __DIR__.'/auth.php';
