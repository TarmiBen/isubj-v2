<?php

use App\Http\Controllers\Api\Student\AgendaController;
use App\Http\Controllers\Api\Student\AuthController;
use App\Http\Controllers\Api\Student\HistoryController;
use App\Http\Controllers\Api\Student\HomeController;
use App\Http\Controllers\Api\Student\PaymentController;
use App\Http\Controllers\Api\Student\ProfileController;
use App\Http\Controllers\Api\Student\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — App de alumnos (estudiantes.isubj.com)
|--------------------------------------------------------------------------
| Todo bajo /api/v1/students. Autenticación por Sanctum (Bearer token, no
| cookies) para poder servir un subdominio distinto sin CSRF/cookie domain.
*/

Route::prefix('v1/students')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:student-auth');
    Route::post('/auth/set-password', [AuthController::class, 'setPassword'])
        ->middleware('throttle:student-auth');

    Route::middleware(['auth:sanctum', 'ability:student', 'throttle:student-api', \App\Http\Middleware\EnsureStudentUser::class])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/home', HomeController::class);

        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::get('/subjects/{assignment}', [SubjectController::class, 'show']);

        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);

        Route::get('/agenda', [AgendaController::class, 'index']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto']);

        Route::get('/history', [HistoryController::class, 'index']);
        Route::get('/history/{inscription}', [HistoryController::class, 'show']);
    });
});
