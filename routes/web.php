<?php

use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('leads', LeadController::class);
    Route::patch('leads/{lead}/notes', [LeadController::class, 'updateNotes'])->name('leads.notes.update');
});

require __DIR__.'/settings.php';
