<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\PublicStudentRegistrationForm;
use App\Http\Controllers\StudentDocumentController;


Route::view('/', 'welcome');
Route::get('student/create', \App\Livewire\PublicStudentRegistration::class)->name('student.create');
Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

require __DIR__.'/auth.php';
