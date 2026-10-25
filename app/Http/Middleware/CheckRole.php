<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Pont de compatibilité pour les vieilles routes « role:* ».
 *
 * Un nom de rôle ne constitue JAMAIS une autorisation. Les routes métier
 * doivent utiliser « permission:* » directement, mais si un ancien appel
 * subsiste, ce pont traduit UNIQUEMENT l'étiquette legacy en permission
 * explicite et refuse toute valeur inconnue. Aucun hasRole / bypass superAdmin.
 */
class CheckRole
{
    private const LEGACY_PERMISSIONS = [
        'superAdmin' => 'system.manage',
        'serviceTechnique' => 'system.technical_access',
        'secretaire' => 'identity.school_manager',
        'coordinateur' => 'identity.coordinate',
        'directeurEtudes' => 'identity.direct_studies',
        'enseignant' => 'identity.teach',
        'teacher' => 'identity.teach',
        'etudiant' => 'identity.student',
    ];

    public function handle(Request $request, Closure $next, string $roles)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        foreach (preg_split('/[,|]/', $roles) as $name) {
            $required = self::LEGACY_PERMISSIONS[trim($name)] ?? null;
            if ($required !== null && $user->can($required)) {
                return $next($request);
            }
        }

        abort(403, 'Accès non autorisé : permission requise.');
    }
}
