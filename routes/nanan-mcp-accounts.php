<?php

use App\Http\Controllers\Nanan\McpConnectionsController;
use Illuminate\Support\Facades\Route;

// Authentification Laravel et CSRF du groupe "web" (jamais un lien GET
// pour une révocation). L'utilisateur agit exclusivement sur ses liaisons.
Route::middleware(['auth'])->prefix('nanan/mcp')->name('nanan.mcp.')->group(function () {
    Route::get('/connections', [McpConnectionsController::class, 'index'])
        ->name('connections.index');

    Route::get('/connect', [McpConnectionsController::class, 'connect'])
        ->name('connect');

    // Approbation nominative par un acteur habilité distinct de l'employé.
    // Ne crée aucun lien : seul le détenteur de la session cible peut
    // confirmer le consentement après approbation.
    Route::get('/review', [McpConnectionsController::class, 'review'])
        ->name('review');

    Route::post('/connections/approve', [McpConnectionsController::class, 'approve'])
        ->middleware('throttle:5,1')
        ->name('connections.approve');

    Route::post('/connections/claim', [McpConnectionsController::class, 'claim'])
        ->middleware('throttle:5,1')
        ->name('connections.claim');

    Route::post('/connections/{identity}/revoke', [McpConnectionsController::class, 'revoke'])
        ->whereNumber('identity')
        ->middleware('throttle:10,1')
        ->name('connections.revoke');
});
