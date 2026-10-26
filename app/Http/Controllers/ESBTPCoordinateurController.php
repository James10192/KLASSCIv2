<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\MotDePasseChoisi;
use App\Services\UserService;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Support\ListeInfinie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ESBTPCoordinateurController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    /**
     * Display a listing of the coordinators.
     */
    public function index(Request $request)
    {
        // Vérifier les permissions
        $this->authorize('coordinateurs.view');

        $coordinateurs = User::role('coordinateur')
            ->with(['roles'])
            ->orderBy('name')
            // Departage stable : la liste se charge par tranches.
            ->orderBy('users.id')
            ->paginate(10);

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse($coordinateurs, fn ($coordinateur) => view('esbtp.coordinateurs._carte', compact('coordinateur'))->render());
        }

        // Sur tous les coordinateurs, pas sur la premiere tranche affichee.
        $compteurs = [
            'actifs' => User::role('coordinateur')->where('is_active', true)->count(),
            'inactifs' => User::role('coordinateur')->where('is_active', false)->count(),
            'du_mois' => User::role('coordinateur')->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('esbtp.coordinateurs.index', compact('coordinateurs', 'compteurs'));
    }

    /**
     * Show the form for creating a new coordinator.
     */
    public function create()
    {
        $this->authorize('coordinateurs.create');
        
        return view('esbtp.coordinateurs.create');
    }

    /**
     * Store a newly created coordinator in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('coordinateurs.create');
        // La création attribue aussi le rôle : même délégation effective que
        // l'écran unique personnel/unified (aucun passe-droit par fonction).
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'coordinateur'), 403);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date|before:today',
            'adresse' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Créer l'utilisateur avec username et password automatiques
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['telephone'] ?? null,
            ], 'coordinateur');

            // Mettre à jour les champs supplémentaires
            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'email_verified_at' => !empty($validated['email']) ? now() : null,
            ]);

            // Mutation sécurisée avec revalidation des droits et audit durable
            // dans la même transaction que la création du compte.
            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'coordinateur',
                'Création du coordinateur depuis la route historique.'
            );

            DB::commit();

            // Obtenir les informations de connexion pour affichage
            $credentials = $this->userService->getCredentialsInfo(
                $user->username, 
                $this->userService->generateDefaultPassword()
            );

            return redirect()->route('esbtp.personnel.unified.index')
                           ->with('success', 'Coordinateur créé avec succès.')
                           ->with('credentials', $credentials);

        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création coordinateur annulée', ['type' => get_class($e)]);
            return back()->with('error', 'La création a échoué. Aucune modification n’a été enregistrée.')
                        ->withInput();
        }
    }

    /**
     * Display the specified coordinator.
     */
    public function show(User $coordinateur)
    {
        $this->authorize('coordinateurs.view');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        // Statistiques du coordinateur
        $statistiques = $this->getCoordinateurStatistiques($coordinateur->id);

        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($coordinateur, auth()->user());

        return view('esbtp.coordinateurs.show', compact('coordinateur', 'statistiques', 'activite'));
    }

    /**
     * Show the form for editing the specified coordinator.
     */
    public function edit(User $coordinateur)
    {
        $this->authorize('coordinateurs.edit');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        return view('esbtp.coordinateurs.edit', compact('coordinateur'));
    }

    /**
     * Update the specified coordinator in storage.
     */
    public function update(Request $request, User $coordinateur, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('coordinateurs.edit');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $coordinateur->id,
            'password' => MotDePasseChoisi::facultatif(),
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date|before:today',
            'adresse' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'date_naissance' => $validated['date_naissance'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            // Si un nouveau mot de passe est fourni
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $lifecycle->updateUser(
                $coordinateur->id,
                $updateData,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                           ->with('success', 'Coordinateur mis à jour avec succès.');

        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'La mise à jour a échoué. Contactez le support.')
                        ->withInput();
        }
    }

    /**
     * Remove the specified coordinator from storage.
     */
    public function destroy(User $coordinateur, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('users.manage');
        
        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        // Empêcher la suppression de son propre compte
        if ($coordinateur->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            // Ce DELETE historique retire une FONCTION, non le compte entier.
            // Un enseignant-coordinateur conserve son accès enseignant et ses relations.
            // La désactivation complète utilise le workflow indépendant du personnel.
            app(RoleAssignmentService::class)->change(
                auth()->user(), $coordinateur->id, 'remove', 'coordinateur',
                'Retrait de la fonction coordinateur depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                           ->with('success', 'Coordinateur supprimé avec succès.');

        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait fonction coordinateur annulé', ['type' => get_class($e)]);
            return back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    /**
     * Calculer les statistiques d'un coordinateur
     */
    private function getCoordinateurStatistiques($coordinateurId)
    {
        // Ici on peut ajouter des statistiques spécifiques au coordinateur
        // Pour l'instant, on retourne des statistiques de base
        
        return [
            'date_creation' => User::find($coordinateurId)->created_at,
            'derniere_connexion' => User::find($coordinateurId)->last_login_at ?? 'Jamais',
            'statut' => User::find($coordinateurId)->is_active ? 'Actif' : 'Inactif',
            'nb_classes_gerees' => 0, // À implémenter selon la logique métier
            'nb_enseignants_supervises' => 0, // À implémenter selon la logique métier
        ];
    }

    /**
     * Reset password for a coordinator
     */
    public function resetPassword(User $coordinateur)
    {
        abort_unless(auth()->user()?->can('personnel.users.password.reset'), 403);
        $this->authorize('users.manage');
        $this->authorize('update', $coordinateur);

        // Vérifier que l'utilisateur est bien un coordinateur
        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        try {
            $defaultPassword = $this->userService->generateDefaultPassword();

            // Update password AND force password change on first login
            $coordinateur->password = Hash::make($defaultPassword);
            $coordinateur->must_change_password = true; // Force password change
            $coordinateur->save();

            // Log action
            \Log::info('🔑 Password reset for coordinator to default', [
                'coordinateur_id' => $coordinateur->id,
                'coordinateur_name' => $coordinateur->name,
                'reset_by' => auth()->user()->name,
                'timestamp' => now(),
                'must_change_password' => true
            ]);

            // Handle AJAX request
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'password' => $defaultPassword,
                    'message' => 'Mot de passe réinitialisé avec succès!'
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Mot de passe réinitialisé à '.$defaultPassword.' avec succès! Le coordinateur devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);

        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed for coordinator', [
                'coordinateur_id' => $coordinateur->id,
                'exception_type' => get_class($e)
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La réinitialisation du mot de passe a échoué. Contactez le support.'
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'La réinitialisation du mot de passe a échoué. Contactez le support.');
        }
    }

    /**
     * Toggle active status of coordinator
     */
    public function toggleStatus(User $coordinateur, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('users.manage');

        if (!$coordinateur->hasRole('coordinateur')) {
            abort(404, 'Coordinateur non trouvé.');
        }

        try {
            $isActive = $lifecycle->toggleUser(
                $coordinateur->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Le changement de statut a échoué. Contactez le support.');
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Coordinateur {$status} avec succès.");
    }
}
