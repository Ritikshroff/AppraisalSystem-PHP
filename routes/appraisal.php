<?php

use App\Http\Controllers\Appraisal\AdminController;
use App\Http\Controllers\Appraisal\AuthController;
use App\Http\Controllers\Appraisal\AppraisalController;
use App\Http\Controllers\Appraisal\DashboardController;
use Illuminate\Support\Facades\Route;

// Authentication (Guest) Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthController::class, 'signup']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Appraisal Detail, Save, Submit Routes
    Route::get('/appraisals/{id}', [AppraisalController::class, 'show'])->name('appraisals.show');
    Route::post('/appraisals/{id}/save', [AppraisalController::class, 'save'])->name('appraisals.save');
    Route::post('/appraisals/{id}/submit', [AppraisalController::class, 'submit'])->name('appraisals.submit');

    // HR Admin Routes
    Route::middleware('role:HR')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/cycle/{id}/window', [AdminController::class, 'updateCycleWindow'])->name('cycle.window');
        Route::post('/cycle/assign', [AdminController::class, 'assignEmployeeToCycle'])->name('cycle.assign');
        Route::post('/cycle/{id}/enroll-all', [AdminController::class, 'enrollAllEmployees'])->name('cycle.enrollAll');
        Route::post('/employee/{id}/update', [AdminController::class, 'updateEmployee'])->name('employee.update');
    });
});
