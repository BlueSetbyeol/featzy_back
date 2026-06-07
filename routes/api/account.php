<?php

use App\Http\Controllers\Account\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Mise à jour du profil
    Route::patch('/me', [ProfileController::class, 'update'])->name('profile.update');
    // Changement de mot de passe
    Route::put('/me/password', [ProfileController::class, 'updatePassword'])->name('password.change');
    // Suppression du compte
    Route::delete('/me', [ProfileController::class, 'destroy'])->name('account.destroy');
});
