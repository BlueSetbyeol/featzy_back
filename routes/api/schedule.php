<?php

use App\Http\Controllers\Schedule\ScheduleExceptionController;
use App\Http\Controllers\Schedule\ServiceScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Liste des horaires de service
    Route::get('/restaurants/{restaurant}/service-schedules', [ServiceScheduleController::class, 'index'])
        ->middleware('can:view,restaurant')->name('service-schedules.index');
    // Création d'un horaire de service
    Route::post('/restaurants/{restaurant}/service-schedules', [ServiceScheduleController::class, 'store'])
        ->middleware('can:update,restaurant')->name('service-schedules.store');
    // Mise à jour d'un horaire de service
    Route::patch('/service-schedules/{serviceSchedule}', [ServiceScheduleController::class, 'update'])
        ->middleware('can:update,serviceSchedule')->name('service-schedules.update');
    // Suppression d'un horaire de service
    Route::delete('/service-schedules/{serviceSchedule}', [ServiceScheduleController::class, 'destroy'])
        ->middleware('can:delete,serviceSchedule')->name('service-schedules.destroy');

    // Liste des exceptions d'horaire (fermetures, horaires spéciaux)
    Route::get('/restaurants/{restaurant}/schedule-exceptions', [ScheduleExceptionController::class, 'index'])
        ->middleware('can:view,restaurant')->name('schedule-exceptions.index');
    // Création d'une exception d'horaire
    Route::post('/restaurants/{restaurant}/schedule-exceptions', [ScheduleExceptionController::class, 'store'])
        ->middleware('can:update,restaurant')->name('schedule-exceptions.store');
    // Mise à jour d'une exception d'horaire
    Route::patch('/schedule-exceptions/{scheduleException}', [ScheduleExceptionController::class, 'update'])
        ->middleware('can:update,scheduleException')->name('schedule-exceptions.update');
    // Suppression d'une exception d'horaire
    Route::delete('/schedule-exceptions/{scheduleException}', [ScheduleExceptionController::class, 'destroy'])
        ->middleware('can:delete,scheduleException')->name('schedule-exceptions.destroy');
});
