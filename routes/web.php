<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadImportController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');

    // Registered before the leads resource so /leads/import isn't captured by leads/{lead}.
    Route::get('leads/import', [LeadImportController::class, 'create'])->name('leads.import.create');
    Route::post('leads/import/preview', [LeadImportController::class, 'preview'])->name('leads.import.preview');
    Route::post('leads/import', [LeadImportController::class, 'store'])->name('leads.import.store');

    Route::resource('leads', LeadController::class);
    Route::patch('leads/{lead}/notes', [LeadController::class, 'updateNotes'])->name('leads.notes.update');
    Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status.update');

    Route::resource('message-templates', MessageTemplateController::class)->except('show');
});

require __DIR__.'/settings.php';
