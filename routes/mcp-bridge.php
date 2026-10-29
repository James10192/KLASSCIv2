<?php

use App\Http\Controllers\API\McpBridgeController;
use Illuminate\Support\Facades\Route;

// Pas de middleware auth:sanctum : identité vérifiée par signature HMAC,
// liaison explicite et permissions propres au tenant dans le contrôleur.
// L'endpoint retourne 401 sans configuration MCP activée.
// Le groupe api applique throttle:api AVANT l'authentification HMAC : tous
// les utilisateurs partagent l'IP de sortie de la passerelle et bloqueraient
// leurs collègues. Le contrôleur limite APRÈS la signature, par identité locale.
// Les requêtes non signées sont filtrées au niveau Cloudflare/WAF.
// Ne pas ajouter une limite par IP du gateway sur cette route.
Route::post('/mcp-bridge', McpBridgeController::class)
    ->withoutMiddleware(['throttle:api'])
    ->name('api.mcp.bridge');
