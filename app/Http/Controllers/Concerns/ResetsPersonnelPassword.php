<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Réinitialisation mot de passe personnel — pattern unifié.
 *
 * Utilisé par ESBTPCaissierController + ESBTPComptableController (Lot 18).
 * Les autres rôles (coordinateur, secrétaire, enseignant, étudiant) ont
 * actuellement leur propre `resetPassword()` legacy — à migrer ici au fil
 * des touches.
 *
 * Mot de passe par défaut : `Bonjour@2025` + `must_change_password = true`.
 * Réponse adaptative JSON (AJAX) ou flash redirect.
 */
trait ResetsPersonnelPassword
{
    /**
     * Réinitialise le mot de passe à la valeur par défaut et force le changement
     * à la première connexion.
     *
     * @param  string  $roleLabel  ex: "comptable", "caissier" — utilisé dans logs et message
     */
    protected function resetPersonnelPassword(User $user, string $roleLabel): JsonResponse|RedirectResponse
    {
        try {
            $defaultPassword = 'Bonjour@2025';

            $user->password = Hash::make($defaultPassword);
            $user->must_change_password = true;
            $user->save();

            Log::info('Password reset for personnel to default', [
                'role' => $roleLabel,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'reset_by' => auth()->user()?->name,
                'timestamp' => now(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'password' => $defaultPassword,
                    'message' => 'Mot de passe réinitialisé avec succès !',
                ]);
            }

            return redirect()->back()
                ->with('success', "Mot de passe réinitialisé à {$defaultPassword} avec succès ! Le {$roleLabel} devra changer son mot de passe à la première connexion.")
                ->with('new_password', $defaultPassword);
        } catch (\Exception $e) {
            Log::error('Password reset failed for personnel', [
                'role' => $roleLabel,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la réinitialisation : ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erreur lors de la réinitialisation : ' . $e->getMessage());
        }
    }
}
