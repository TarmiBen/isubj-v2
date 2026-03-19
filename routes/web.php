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


require __DIR__.'/auth.php';
