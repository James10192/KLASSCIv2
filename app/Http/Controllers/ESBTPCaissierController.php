<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * ESBTPCaissierController
 *
 * Lot 18 — Gestion dédiée des caissiers (show, edit, update, destroy,
 * toggleStatus, resetPassword).
 *
 * Les routes create/store restent gérées par ESBTPComptableController
 * (createCaissier, storeCaissier) pour rester compatibles avec
 * `routes/web.php` ligne 1902-1903.
 */
class ESBTPCaissierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la fiche d'un caissier.
     */
    public function show(User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        return view('esbtp.caissiers.show', compact('caissier'));
    }

    /**
     * Affiche le formulaire d'édition d'un caissier.
     */
    public function edit(User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        return view('esbtp.caissiers.edit', compact('caissier'));
    }

    /**
     * Met à jour un caissier.
     */
    public function update(Request $request, User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|string|email|max:255|unique:users,email,' . $caissier->id,
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $caissier->name  = $validated['name'];
            $caissier->email = $validated['email'] ?? null;
            $caissier->phone = $validated['phone'] ?? null;

            if (array_key_exists('is_active', $validated)) {
                $caissier->is_active = (bool) $validated['is_active'];
            }

            $caissier->save();

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Informations mises à jour.',
                    'user'    => $caissier->fresh(),
                ]);
            }

            return redirect()->route('esbtp.caissiers.show', $caissier)
                ->with('success', 'Caissier mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur : ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Désactive un caissier (soft delete : retire le rôle + désactive le compte).
     */
    public function destroy(User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        if ($caissier->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            DB::beginTransaction();

            $caissier->update([
                'is_active' => false,
                'email'     => $caissier->email ? $caissier->email . '_deleted_' . time() : null,
            ]);
            $caissier->removeRole('caissier');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Caissier désactivé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Bascule l'état actif/inactif du caissier.
     */
    public function toggleStatus(User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        $caissier->update([
            'is_active' => ! $caissier->is_active,
        ]);

        $label = $caissier->is_active ? 'activé' : 'désactivé';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success'   => true,
                'message'   => "Caissier {$label}.",
                'is_active' => $caissier->is_active,
            ]);
        }

        return redirect()->back()->with('success', "Caissier {$label} avec succès.");
    }

    /**
     * Réinitialise le mot de passe à Bonjour@2025 et force le changement
     * à la première connexion. Pattern aligné sur ESBTPCoordinateurController.
     */
    public function resetPassword(User $caissier)
    {
        $this->ensureCanManage();
        $this->ensureIsCaissier($caissier);

        try {
            $defaultPassword = 'Bonjour@2025';

            $caissier->password              = Hash::make($defaultPassword);
            $caissier->must_change_password  = true;
            $caissier->save();

            \Log::info('🔑 Password reset for caissier to default', [
                'caissier_id'           => $caissier->id,
                'caissier_name'         => $caissier->name,
                'reset_by'              => auth()->user()->name,
                'timestamp'             => now(),
                'must_change_password'  => true,
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success'  => true,
                    'password' => $defaultPassword,
                    'message'  => 'Mot de passe réinitialisé avec succès !',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Mot de passe réinitialisé à Bonjour@2025 avec succès ! Le caissier devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);
        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed for caissier', [
                'caissier_id' => $caissier->id,
                'error'       => $e->getMessage(),
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

    /**
     * Garde-fou : seul superAdmin / secretaire / comptable / les utilisateurs
     * avec users.manage peuvent gérer les caissiers.
     */
    private function ensureCanManage(): void
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $allowed = $user->hasAnyRole(['superAdmin', 'secretaire', 'comptable'])
            || $user->can('users.manage');

        abort_unless($allowed, 403, 'Action non autorisée.');
    }

    /**
     * Vérifie que l'utilisateur cible est bien un caissier.
     */
    private function ensureIsCaissier(User $caissier): void
    {
        if (! $caissier->hasRole('caissier')) {
            abort(404, 'Caissier non trouvé.');
        }
    }
}
