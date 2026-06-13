<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// Inscription d'un nouvel utilisateur
Route::post('/register', [AuthController::class, 'register'])->name('register');
// Connexion (session SPA)
Route::post('/login', [AuthController::class, 'login'])->name('login');
// Connexion (with Expo)
Route::post('/mobile/login', [AuthController::class, 'loginMobile'])->name('login.mobile');

// Envoi du lien de réinitialisation du mot de passe
Route::post('/forgot-password', [PasswordController::class, 'forgot'])->name('password.email');

// Réinitialisation du mot de passe via le token
Route::post('/reset-password', [PasswordController::class, 'reset'])->name('password.update');

// Renvoi de l'e-mail de vérification
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

// Vérification de l'adresse e-mail via le lien signé
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('throttle:6,1')
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    // Utilisateur authentifié courant
    Route::get('/user', [AuthController::class, 'user'])->name('user.current');
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
