<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ESBTPComptableController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware(['auth', 'role:superAdmin']);
        $this->userService = $userService;
    }

    public function index()
    {
        $comptables = User::role('comptable')->orderBy('name')->get();
        return view('esbtp.comptables.index', compact('comptables'));
    }

    public function create()
    {
        return view('esbtp.comptables.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|string|email|max:255|unique:users,email',
            'telephone'  => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Créer l'utilisateur avec username et password automatiques
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['telephone'] ?? null,
            ], 'comptable');

            // Mettre à jour les champs supplémentaires
            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'department' => $validated['department'] ?? null,
            ]);

            // Assigner le rôle comptable
            $user->assignRole('comptable');

            DB::commit();

            // Obtenir les informations de connexion pour affichage
            $credentials = $this->userService->getCredentialsInfo(
                $user->username,
                $this->userService->generateDefaultPassword()
            );

            return redirect()
                ->route('esbtp.personnel.unified.index')
                ->with('success', "Comptable {$user->name} créé avec succès.")
                ->with('credentials', $credentials);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function show(User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);
        return view('esbtp.comptables.show', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'telephone'  => 'nullable|string|max:20',
            'department' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour.',
                'user'    => $user->fresh(),
            ]);
        }

        return redirect()
            ->route('esbtp.comptables.show', $user)
            ->with('success', 'Informations mises à jour.');
    }

    public function toggleStatus(User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);

        $user->update(['is_active' => !$user->is_active]);

        $label = $user->is_active ? 'activé' : 'désactivé';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Comptable {$label}.",
                'is_active' => $user->is_active,
            ]);
        }

        return redirect()->back()->with('success', "Comptable {$label}.");
    }

    public function createCaissier()
    {
        return view('esbtp.caissiers.create');
    }

    public function storeCaissier(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|string|email|max:255|unique:users,email',
            'telephone' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['telephone'] ?? null,
            ], 'caissier');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
            ]);

            $user->assignRole('caissier');

            DB::commit();

            $credentials = $this->userService->getCredentialsInfo(
                $user->username,
                $this->userService->generateDefaultPassword()
            );

            return redirect()
                ->route('esbtp.personnel.unified.index')
                ->with('success', "Caissier {$user->name} créé avec succès.")
                ->with('credentials', $credentials);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            DB::beginTransaction();

            $user->update([
                'is_active' => false,
                'email' => $user->email . '_deleted_' . time(),
            ]);
            $user->removeRole('comptable');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Comptable désactivé avec succès');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Lot 18d — Réinitialise le mot de passe du comptable à Bonjour@2025 et
     * force le changement à la première connexion. Pattern aligné sur les
     * autres rôles (coordinateur, secretaire, caissier).
     */
    public function resetPassword(User $user)
    {
        if (! $user->hasRole('comptable')) {
            abort(404, 'Comptable non trouvé.');
        }

        try {
            $defaultPassword = 'Bonjour@2025';

            $user->password              = Hash::make($defaultPassword);
            $user->must_change_password  = true;
            $user->save();

            \Log::info('🔑 Password reset for comptable to default', [
                'comptable_id'         => $user->id,
                'comptable_name'       => $user->name,
                'reset_by'             => auth()->user()->name,
                'timestamp'            => now(),
                'must_change_password' => true,
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success'  => true,
                    'password' => $defaultPassword,
                    'message'  => 'Mot de passe réinitialisé avec succès !',
                ]);
            }

            return redirect()->back()
                ->with('success', 'Mot de passe réinitialisé à Bonjour@2025 avec succès ! Le comptable devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);
        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed for comptable', [
                'comptable_id' => $user->id,
                'error'        => $e->getMessage(),
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
