<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResetsPersonnelPassword;
use App\Models\User;
use App\Services\UserService;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ESBTPComptableController extends Controller
{
    use ResetsPersonnelPassword;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware(['auth', 'permission:system.manage']);
        $this->userService = $userService;
    }

    public function index()
    {
        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        return view('esbtp.comptables.create');
    }

    public function store(Request $request)
    {
        // system.manage ouvre l'ancien écran, mais ne permet pas de déléguer
        // automatiquement toutes les permissions d'un rôle comptable.
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'comptable'), 403);

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
            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'comptable',
                'Création comptable depuis la route historique.'
            );

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
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création comptabilité annulée', ['type' => get_class($e)]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'La création a échoué. Aucune modification enregistrée.');
        }
    }

    public function show(User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);
        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($user, auth()->user());

        return view('esbtp.comptables.show', compact('user', 'activite'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->can('comptabilite.access'), 403);
        $this->authorize('update', $user);

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

    public function toggleStatus(User $user, SuperAdminLifecycleGuard $lifecycle)
    {
        abort_unless($user->can('comptabilite.access'), 403);

        try {
            $isActive = $lifecycle->toggleUser(
                $user->id,
                authorize: fn (User $target) => $this->authorize('update', $target),
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Le changement de statut a échoué.');
        }

        $label = $isActive ? 'activé' : 'désactivé';

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Comptable {$label}.",
                'is_active' => $isActive,
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
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'caissier'), 403);

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

            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'caissier',
                'Création caissier depuis la route historique.'
            );

            DB::commit();

            $credentials = $this->userService->getCredentialsInfo(
                $user->username,
                $this->userService->generateDefaultPassword()
            );

            return redirect()
                ->route('esbtp.personnel.unified.index')
                ->with('success', "Caissier {$user->name} créé avec succès.")
                ->with('credentials', $credentials);
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création comptabilité annulée', ['type' => get_class($e)]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'La création a échoué. Aucune modification enregistrée.');
        }
    }

    public function destroy(User $user, SuperAdminLifecycleGuard $lifecycle)
    {
        abort_unless($user->can('comptabilite.access'), 403);

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            app(RoleAssignmentService::class)->change(
                auth()->user(), $user->id, 'remove', 'comptable',
                'Retrait de la fonction comptable depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Fonction comptable retirée avec succès');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait comptable annulé', ['type' => get_class($e)]);
            return redirect()->back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    /**
     * Lot 18d — Réinitialise le mot de passe du comptable à UserService::defaultPassword() et
     * force le changement à la première connexion. Logique partagée via
     * ResetsPersonnelPassword (alignée sur caissier, coordinateur, etc.).
     */
    public function resetPassword(User $user)
    {
        $this->authorize('update', $user);

        if (! $user->hasRole('comptable')) {
            abort(404, 'Comptable non trouvé.');
        }

        return $this->resetPersonnelPassword($user, 'comptable');
    }
}
