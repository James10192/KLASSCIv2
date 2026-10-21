<?php

use App\Http\Controllers\API\McpBridgeController;
use Illuminate\Support\Facades\Route;

// Pas de middleware auth:sanctum : identité vérifiée par signature HMAC,
// liaison explicite et permissions propres au tenant dans le contrôleur.
// L'endpoint retourne 401 sans configuration MCP activée.
Route::post('/mcp-bridge', McpBridgeController::class)
    ->middleware('throttle:30,1')
    ->name('api.mcp.bridge');
