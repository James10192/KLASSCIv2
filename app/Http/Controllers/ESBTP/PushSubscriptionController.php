<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Gestion des abonnements Web Push (PWA) de l'étudiant connecté.
 *
 * Le navigateur appelle subscribe/unsubscribe en AJAX (JSON) depuis la page
 * Préférences. La sécurité repose sur le groupe de routes (auth + role:etudiant)
 * et le scoping au user courant via le trait HasPushSubscriptions.
 */
class PushSubscriptionController extends Controller
{
    /**
     * Enregistre (ou met à jour) l'abonnement push du navigateur courant.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        $user = Auth::user();

        try {
            $user->updatePushSubscription(
                $data['endpoint'],
                $data['keys']['p256dh'],
                $data['keys']['auth'],
                $data['contentEncoding'] ?? null
            );
        } catch (\Throwable $e) {
            Log::error('[push] Echec enregistrement abonnement', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Impossible d'enregistrer l'abonnement aux notifications.",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications activées sur cet appareil.',
        ]);
    }

    /**
     * Supprime l'abonnement push du navigateur courant.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
        ]);

        $user = Auth::user();

        try {
            $user->deletePushSubscription($data['endpoint']);
        } catch (\Throwable $e) {
            Log::error('[push] Echec suppression abonnement', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Impossible de désactiver les notifications.",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications désactivées sur cet appareil.',
        ]);
    }
}
