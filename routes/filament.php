<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentDocumentController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCardSimpleController;

Route::middleware(['web', 'auth', 'filament'])->group(function () {
    Route::delete('students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])
        ->name('filament.admin.resources.students.documents.destroy');

});
