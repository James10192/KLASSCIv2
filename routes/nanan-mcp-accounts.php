<?php

use App\Http\Controllers\Nanan\McpConnectionsController;
use Illuminate\Support\Facades\Route;

// Authentification Laravel et CSRF du groupe "web" (jamais un lien GET
// pour une révocation). L'utilisateur agit exclusivement sur ses liaisons.
Route::middleware(['auth'])->prefix('nanan/mcp')->name('nanan.mcp.')->group(function () {
    Route::get('/connections', [McpConnectionsController::class, 'index'])
        ->name('connections.index');

    Route::post('/connections/{identity}/revoke', [McpConnectionsController::class, 'revoke'])
        ->whereNumber('identity')
        ->middleware('throttle:10,1')
        ->name('connections.revoke');
});
