<?php

use App\Http\Controllers\Reservation\InvitationController;
use App\Http\Controllers\Reservation\ParticipantController;
use App\Http\Controllers\Reservation\ReservationController;
use App\Http\Controllers\Restaurant\RestaurantReservationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Réservations organisées par l'utilisateur connecté
    Route::get('/me/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    // Invitations reçues par l'utilisateur connecté
    Route::get('/me/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    // Création d'une réservation sur un créneau d'un restaurant
    Route::post('/restaurants/{restaurant}/reservations', [ReservationController::class, 'store'])
        ->middleware('role:client')->name('reservations.store');
    // Détail d'une réservation (organisateur, participant ou propriétaire du restaurant)
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])
        ->middleware('can:view,reservation')->name('reservations.show');
    // Annulation d'une réservation (organisateur ou propriétaire du restaurant)
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->middleware('can:cancel,reservation')->name('reservations.cancel');

    // Board des réservations (propriétaire du restaurant)
    Route::get('/restaurants/{restaurant}/reservations', [RestaurantReservationController::class, 'index'])
        ->middleware('can:update,restaurant')->name('restaurants.reservations.index');
    // Transitions de statut côté restaurateur
    Route::post('/reservations/{reservation}/seat', [RestaurantReservationController::class, 'seat'])
        ->middleware('can:manage,reservation')->name('reservations.seat');
    Route::post('/reservations/{reservation}/complete', [RestaurantReservationController::class, 'complete'])
        ->middleware('can:manage,reservation')->name('reservations.complete');
    Route::post('/reservations/{reservation}/no-show', [RestaurantReservationController::class, 'noShow'])
        ->middleware('can:manage,reservation')->name('reservations.no-show');

    // Invitation de convives (organisateur ; users explicites et/ou groupe d'amis)
    Route::post('/reservations/{reservation}/participants', [ParticipantController::class, 'store'])
        ->middleware('can:invite,reservation')->name('reservations.participants.store');
    // Retrait d'un convive (organisateur)
    Route::delete('/reservations/{reservation}/participants/{participant}', [ParticipantController::class, 'destroy'])
        ->middleware('can:delete,participant')->scopeBindings()->name('reservations.participants.destroy');
    // Réponse d'un invité à son invitation (RSVP)
    Route::post('/reservations/{reservation}/rsvp', [InvitationController::class, 'rsvp'])
        ->middleware('can:rsvp,reservation')->name('reservations.rsvp');
});
