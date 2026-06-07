<?php

use App\Http\Controllers\Availability\AvailabilityController;
use Illuminate\Support\Facades\Route;

// Créneaux disponibles d'un restaurant (public)
Route::get('/restaurants/{restaurant}/availabilities', [AvailabilityController::class, 'index'])
    ->name('availabilities.index');

Route::middleware('auth:sanctum')->group(function () {
    // Génération des créneaux à partir des horaires
    Route::post('/restaurants/{restaurant}/availabilities/generate', [AvailabilityController::class, 'generate'])
        ->middleware('can:update,restaurant')->name('availabilities.generate');
});
