<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');

    Route::resource('leads', LeadController::class);
    Route::patch('leads/{lead}/notes', [LeadController::class, 'updateNotes'])->name('leads.notes.update');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status.update');
});

require __DIR__.'/settings.php';
