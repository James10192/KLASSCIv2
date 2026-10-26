<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResetsPersonnelPassword;
use App\Models\User;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
    use ResetsPersonnelPassword;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la fiche d'un caissier.
     */
    public function show(User $caissier)
    {
        $this->ensureCanManage('view');
        $this->ensureIsCaissier($caissier);

        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($caissier, auth()->user());

        return view('esbtp.caissiers.show', compact('caissier', 'activite'));
    }

    /**
     * Affiche le formulaire d'édition d'un caissier.
     */
    public function edit(User $caissier)
    {
        $this->ensureCanManage('edit');
        $this->ensureIsCaissier($caissier);

        return view('esbtp.caissiers.edit', compact('caissier'));
    }

    /**
     * Met à jour un caissier.
     */
    public function update(Request $request, User $caissier, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManage('edit');
        $this->ensureIsCaissier($caissier);

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|string|email|max:255|unique:users,email,' . $caissier->id,
            'phone'     => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $attributes = [
                'name'  => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ];

            if (array_key_exists('is_active', $validated)) {
                $attributes['is_active'] = (bool) $validated['is_active'];
            }

            $updatedCaissier = $lifecycle->updateUser(
                $caissier->id,
                $attributes,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Informations mises à jour.',
                    'user'    => $updatedCaissier,
                ]);
            }

            return redirect()->route('esbtp.caissiers.show', $caissier)
                ->with('success', 'Caissier mis à jour avec succès.');
        } catch (\Exception $e) {
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
    public function destroy(User $caissier, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManage('delete');
        $this->ensureIsCaissier($caissier);

        if ($caissier->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            $lifecycle->deactivateUser(
                $caissier->id,
                function (User $user): void {
                    $user->update([
                        'is_active' => false,
                        'email' => $user->email ? $user->email . '_deleted_' . time() : null,
                    ]);
                    app(\App\Services\Personnel\RoleAssignmentService::class)->retirerPendantDesactivation(
                        $user, 'caissier', Auth::id(), 'Désactivation du caissier'
                    );
                },
                authorize: fn (User $user) => $this->authorize('delete', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Caissier désactivé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Bascule l'état actif/inactif du caissier.
     */
    public function toggleStatus(User $caissier, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManage('edit');
        $this->ensureIsCaissier($caissier);

        try {
            $isActive = $lifecycle->toggleUser(
                $caissier->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $label = $isActive ? 'activé' : 'désactivé';

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success'   => true,
                'message'   => "Caissier {$label}.",
                'is_active' => $isActive,
            ]);
        }

        return redirect()->back()->with('success', "Caissier {$label} avec succès.");
    }

    /**
     * Réinitialise le mot de passe à UserService::defaultPassword() et force le changement
     * à la première connexion. Logique partagée via ResetsPersonnelPassword.
     */
    public function resetPassword(User $caissier)
    {
        $this->ensureCanManage('edit');
        $this->ensureIsCaissier($caissier);
        $this->authorize('update', $caissier);

        return $this->resetPersonnelPassword($caissier, 'caissier');
    }

    /**
     * Garde-fou : la permission caissiers.<action> (ou users.manage, la
     * permission générale de gestion des comptes) décide seule. Le nom du
     * rôle de l'acteur n'ouvre rien : un comptable y accède si l'école lui
     * accorde caissiers.*.
     */
    private function ensureCanManage(string $action): void
    {
        $user = Auth::user();

        abort_unless(
            $user && ($user->can('caissiers.'.$action) || $user->can('users.manage')),
            403,
            'Action non autorisée : permission requise.'
        );
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
