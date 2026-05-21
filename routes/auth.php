<?php

/**
 * Routes d'authentification — extraites de web.php (audit 2026-05-21).
 *
 * Hérite du middleware group 'web' via RouteServiceProvider qui charge web.php
 * avec ->middleware('web'). web.php fait `require __DIR__.'/auth.php'` qui
 * inline ces définitions dans le même contexte de middleware group.
 *
 * Routes inclues :
 * - GET/POST /login (avec throttle:login)
 * - POST /logout
 * - GET /auth/sso-from-group
 * - GET/POST /password/* (reset flow avec throttle:3,1)
 * - GET/POST /password/change (force password change)
 */

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// Routes d'authentification simplifiées
// POST /login : anti brute-force via rate limiter 'login' (5/min par email + 10/min par IP)
// défini dans RouteServiceProvider::configureRateLimiting().
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// SSO depuis le portail groupe adminKlassci (token HMAC-SHA256 signé par master).
// Pas de middleware 'guest': un user déjà loggé doit pouvoir basculer vers le target user du token.
Route::get('/auth/sso-from-group', \App\Http\Controllers\Auth\GroupPortalSsoController::class)
    ->name('auth.sso-from-group');

// Routes d'enregistrement publiques supprimées (audit sécurité 2026-05-21).
// KLASSCI étant un SaaS supérieur/universitaire, les inscriptions étudiantes
// passent par /esbtp/inscriptions/create (secrétaire/caissier) et les staffs
// par /esbtp/users/create (admin). Toute création de compte est gatée par
// permission users.create — pas d'inscription publique.

// Routes de réinitialisation de mot de passe
// Password reset — throttle anti-spam + anti user enumeration (audit 2026-05-21).
// 3 tentatives par minute sur les 2 POST sensibles (send link + reset).
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:3,1')
    ->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:3,1')
    ->name('password.update');

// Routes de changement de mot de passe forcé
Route::middleware(['auth'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'showChangeForm'])->name('password.change.form');
    Route::post('/password/change', [PasswordChangeController::class, 'updatePassword'])->name('password.change.update');
});
