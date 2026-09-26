<?php

use App\Http\Controllers\ESBTP\ESBTPAdmissionsAujourdhuiController;
use Illuminate\Support\Facades\Route;

/*
 * Admissions : le poste « Aujourd'hui » de l'accueil. Inclus depuis routes/web.php.
 * Meme permission que l'Accueil du jour, dont il reutilise les actions « reçue ».
 * La liste des dossiers reste sur esbtp.demandes.* (routes/web.php).
 */
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'paywall'])->group(function () {
    Route::get('/inscriptions/aujourdhui', ESBTPAdmissionsAujourdhuiController::class)
        ->middleware(['permission:inscriptions.rdv.accueil', 'throttle:120,1'])
        ->name('admissions.aujourdhui');
});
