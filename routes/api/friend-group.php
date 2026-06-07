<?php

use App\Http\Controllers\FriendGroup\FriendGroupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Liste des groupes d'amis
    Route::get('/friend-groups', [FriendGroupController::class, 'index'])->name('friend-groups.index');
    // Création d'un groupe d'amis
    Route::post('/friend-groups', [FriendGroupController::class, 'store'])->name('friend-groups.store');
    // Détail d'un groupe d'amis
    Route::get('/friend-groups/{friendGroup}', [FriendGroupController::class, 'show'])
        ->middleware('can:view,friendGroup')->name('friend-groups.show');
    // Mise à jour d'un groupe d'amis
    Route::patch('/friend-groups/{friendGroup}', [FriendGroupController::class, 'update'])
        ->middleware('can:update,friendGroup')->name('friend-groups.update');
    // Suppression d'un groupe d'amis
    Route::delete('/friend-groups/{friendGroup}', [FriendGroupController::class, 'destroy'])
        ->middleware('can:delete,friendGroup')->name('friend-groups.destroy');
    // Synchronisation des membres du groupe
    Route::put('/friend-groups/{friendGroup}/members', [FriendGroupController::class, 'syncMembers'])
        ->middleware('can:update,friendGroup')->name('friend-groups.members.sync');
    // Retrait d'un membre du groupe
    Route::delete('/friend-groups/{friendGroup}/members/{user}', [FriendGroupController::class, 'removeMember'])
        ->middleware('can:update,friendGroup')->whereNumber('user')->name('friend-groups.members.remove');
});
