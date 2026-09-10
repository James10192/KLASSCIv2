<?php

use App\Http\Controllers\ESBTPDispenseController;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;

/**
 * Dispenses de matiere (BTS).
 *
 * Fichier dedie plutot que quelques lignes de plus dans web.php : ce fichier-la
 * est un point de collision entre agents, et une dispense n'a rien a y gagner.
 *
 * Toutes les reponses sont JSON : l'ecran de l'etudiant appelle ces routes sans
 * recharger la page.
 */
Route::prefix('esbtp')->name('esbtp.')
    ->middleware([
        'auth',
        'installed',
        'force.password.change',
        'paywall',
        ForceJsonResponse::class,
    ])
    ->group(function () {
        Route::get('/etudiants/{etudiant}/dispenses', [ESBTPDispenseController::class, 'index'])
            ->middleware(['permission:dispenses.view', 'throttle:60,1'])
            ->name('etudiants.dispenses.index');

        Route::post('/etudiants/{etudiant}/dispenses', [ESBTPDispenseController::class, 'store'])
            ->middleware(['permission:dispenses.manage', 'throttle:20,1'])
            ->name('etudiants.dispenses.store');

        // « revoquer » et non « destroy » : une dispense ne se supprime jamais,
        // le bulletin distribue s'appuie dessus.
        Route::patch('/dispenses/{dispense}/revoquer', [ESBTPDispenseController::class, 'revoquer'])
            ->middleware(['permission:dispenses.manage', 'throttle:20,1'])
            ->name('dispenses.revoquer');
    });
