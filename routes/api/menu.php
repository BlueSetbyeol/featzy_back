<?php

use App\Http\Controllers\Menu\MenuCategoryController;
use App\Http\Controllers\Menu\MenuItemController;
use App\Http\Controllers\Menu\MenuItemMediaController;
use App\Http\Controllers\Menu\MenuItemOptionController;
use App\Http\Controllers\Menu\MenuItemOptionGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Liste des catégories du menu
    Route::get('/restaurants/{restaurant}/menu-categories', [MenuCategoryController::class, 'index'])
        ->middleware('can:view,restaurant')->name('menu-categories.index');
    // Création d'une catégorie
    Route::post('/restaurants/{restaurant}/menu-categories', [MenuCategoryController::class, 'store'])
        ->middleware('can:update,restaurant')->name('menu-categories.store');
    // Réordonnancement des catégories
    Route::patch('/restaurants/{restaurant}/menu-categories/reorder', [MenuCategoryController::class, 'reorder'])
        ->middleware('can:update,restaurant')->name('menu-categories.reorder');
    // Mise à jour d'une catégorie
    Route::patch('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'update'])
        ->middleware('can:update,menuCategory')->name('menu-categories.update');
    // Suppression d'une catégorie
    Route::delete('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'destroy'])
        ->middleware('can:delete,menuCategory')->name('menu-categories.destroy');

    // Liste des plats
    Route::get('/restaurants/{restaurant}/menu-items', [MenuItemController::class, 'index'])
        ->middleware('can:view,restaurant')->name('menu-items.index');
    // Création d'un plat
    Route::post('/restaurants/{restaurant}/menu-items', [MenuItemController::class, 'store'])
        ->middleware('can:update,restaurant')->name('menu-items.store');
    // Détail d'un plat
    Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show'])
        ->middleware('can:view,menuItem')->name('menu-items.show');
    // Mise à jour d'un plat
    Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update'])
        ->middleware('can:update,menuItem')->name('menu-items.update');
    // Suppression d'un plat
    Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])
        ->middleware('can:delete,menuItem')->name('menu-items.destroy');
    // Synchronisation des allergènes d'un plat
    Route::put('/menu-items/{menuItem}/allergens', [MenuItemController::class, 'syncAllergens'])
        ->middleware('can:update,menuItem')->name('menu-items.allergens.sync');
    // Ajout d'une photo de plat
    Route::post('/menu-items/{menuItem}/photos', [MenuItemMediaController::class, 'store'])
        ->middleware('can:update,menuItem')->name('menu-items.photos.store');
    // Suppression d'une photo de plat
    Route::delete('/menu-items/{menuItem}/photos/{media}', [MenuItemMediaController::class, 'destroy'])
        ->middleware('can:update,menuItem')->whereNumber('media')->name('menu-items.photos.destroy');

    // Création d'un groupe d'options
    Route::post('/menu-items/{menuItem}/option-groups', [MenuItemOptionGroupController::class, 'store'])
        ->middleware('can:update,menuItem')->name('menu-item-option-groups.store');
    // Mise à jour d'un groupe d'options
    Route::patch('/menu-item-option-groups/{optionGroup}', [MenuItemOptionGroupController::class, 'update'])
        ->middleware('can:update,optionGroup')->name('menu-item-option-groups.update');
    // Suppression d'un groupe d'options
    Route::delete('/menu-item-option-groups/{optionGroup}', [MenuItemOptionGroupController::class, 'destroy'])
        ->middleware('can:delete,optionGroup')->name('menu-item-option-groups.destroy');

    // Ajout d'une option à un groupe
    Route::post('/menu-item-option-groups/{optionGroup}/options', [MenuItemOptionController::class, 'store'])
        ->middleware('can:update,optionGroup')->name('menu-item-options.store');
    // Mise à jour d'une option
    Route::patch('/menu-item-options/{menuItemOption}', [MenuItemOptionController::class, 'update'])
        ->middleware('can:update,menuItemOption')->name('menu-item-options.update');
    // Suppression d'une option
    Route::delete('/menu-item-options/{menuItemOption}', [MenuItemOptionController::class, 'destroy'])
        ->middleware('can:delete,menuItemOption')->name('menu-item-options.destroy');
});
