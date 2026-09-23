<?php

use App\Http\Controllers\Support\DemandeSupportController;
use Illuminate\Support\Facades\Route;

/*
| KLASSCI Care — signaler un probleme et suivre ses demandes. Les demandes
| vivent au Master ; ces routes ne sont qu'un relais serveur (le navigateur
| ne voit jamais l'identifiant du Master).
*/
Route::middleware(['auth'])->prefix('support')->name('support.')->group(function () {
    Route::post('demandes', [DemandeSupportController::class, 'store'])
        ->middleware('throttle:10,1')->name('demandes.store');
    Route::get('demandes', [DemandeSupportController::class, 'index'])
        ->middleware('throttle:60,1')->name('demandes.index');
    Route::get('demandes/{reference}', [DemandeSupportController::class, 'show'])
        ->middleware('throttle:60,1')->name('demandes.show');
    Route::post('demandes/{reference}/messages', [DemandeSupportController::class, 'repondre'])
        ->middleware('throttle:20,1')->name('demandes.repondre');
});
