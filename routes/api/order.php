<?php

use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\OrderItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Ouverture de la commande unique d'une réservation (pré-commande)
    Route::post('/reservations/{reservation}/order', [OrderController::class, 'store'])
        ->middleware('can:createOrder,reservation')->name('reservations.order.store');

    // Détail d'une commande (participant ou propriétaire du restaurant)
    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->middleware('can:view,order')->name('orders.show');
    // Validation de la commande (organisateur) → décrément du stock
    Route::post('/orders/{order}/place', [OrderController::class, 'place'])
        ->middleware('can:place,order')->name('orders.place');
    // Ajout d'une ligne par un participant (pour lui-même)
    Route::post('/orders/{order}/items', [OrderItemController::class, 'store'])
        ->middleware('can:addItem,order')->name('orders.items.store');

    // Mise à jour d'une ligne de commande (auteur de la ligne)
    Route::patch('/order-items/{orderItem}', [OrderItemController::class, 'update'])
        ->middleware('can:update,orderItem')->name('order-items.update');
    // Suppression d'une ligne de commande (auteur de la ligne)
    Route::delete('/order-items/{orderItem}', [OrderItemController::class, 'destroy'])
        ->middleware('can:delete,orderItem')->name('order-items.destroy');
});
