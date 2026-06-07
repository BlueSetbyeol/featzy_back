<?php

use App\Http\Controllers\Discovery\FavoriteController;
use App\Http\Controllers\Discovery\RestaurantDiscoveryController;
use Illuminate\Support\Facades\Route;

// Recherche publique de restaurants
Route::get('/discovery/restaurants', [RestaurantDiscoveryController::class, 'index'])
    ->name('discovery.restaurants.index');
// Fiche publique d'un restaurant
Route::get('/discovery/restaurants/{restaurant}', [RestaurantDiscoveryController::class, 'show'])
    ->name('discovery.restaurants.show');
// Menu public d'un restaurant
Route::get('/discovery/restaurants/{restaurant}/menu', [RestaurantDiscoveryController::class, 'menu'])
    ->name('discovery.restaurants.menu');

Route::middleware('auth:sanctum')->group(function () {
    // Restaurants favoris de l'utilisateur
    Route::get('/me/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    // Ajout d'un restaurant aux favoris
    Route::put('/restaurants/{restaurant}/favorite', [FavoriteController::class, 'store'])->name('favorites.store');
    // Retrait d'un restaurant des favoris
    Route::delete('/restaurants/{restaurant}/favorite', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
});
