<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Page Préférences de l'espace étudiant (PWA).
 *
 * Regroupe l'opt-in aux notifications push et le bouton d'installation de
 * l'application. La clé VAPID publique est passée à la vue pour l'abonnement
 * côté navigateur (pushManager.subscribe).
 */
class PreferencesController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Clé VAPID publique (une seule paire pour toute l'app).
        $vapidPublicKey = (string) (config('webpush.vapid.public_key') ?? '');

        // L'utilisateur a-t-il déjà au moins un abonnement push enregistré ?
        $hasPushSubscription = method_exists($user, 'pushSubscriptions')
            ? $user->pushSubscriptions()->exists()
            : false;

        return view('esbtp.etudiants.preferences', [
            'vapidPublicKey' => $vapidPublicKey,
            'hasPushSubscription' => $hasPushSubscription,
        ]);
    }
}
