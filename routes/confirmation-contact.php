<?php

use App\Http\Controllers\ESBTP\ESBTPConfirmationContactController;
use Illuminate\Support\Facades\Route;

/*
 * « Confirmer le contact » d'une demande du portail dont la famille n'a pas
 * confirme l'adresse ou le numero. Inclus depuis routes/web.php.
 */
Route::prefix('esbtp')->name('esbtp.')->middleware(['auth', 'paywall'])->group(function () {
    Route::post('/inscriptions/candidatures/{candidature}/confirmer-contact', [ESBTPConfirmationContactController::class, 'candidature'])
        ->middleware('throttle:30,1')->name('candidatures.confirmer-contact');
    Route::post('/reinscriptions/demandes/{demande}/confirmer-contact', [ESBTPConfirmationContactController::class, 'reinscription'])
        ->middleware('throttle:30,1')->name('reinscription-demandes.confirmer-contact');
});
