<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Rules\MotDePasseChoisi;
use App\Services\UserService;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Support\ListeInfinie;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ESBTPSecretaireController extends Controller
{
    /**
     * Constructeur avec middleware d'authentification
     */
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
        $this->middleware('role:superAdmin');
    }

    /**
     * Affiche la liste des secrétaires
     */
    public function index(Request $request)
    {
        // Departage stable : la liste se charge par tranches.
        $secretaires = User::role('secretaire')->orderBy('name')->orderBy('users.id')->paginate(10);

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse($secretaires, fn ($secretaire) => view('esbtp.secretaires._ligne', compact('secretaire'))->render());
        }
        return view('esbtp.secretaires.index', compact('secretaires'));
    }

    /**
     * Affiche le formulaire de création d'un secrétaire
     */
    public function create()
    {
        return view('esbtp.secretaires.create');
    }

    /**
     * Enregistre un nouveau secrétaire
     */
    public function store(Request $request)
    {
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'secretaire'), 403);

        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer uniquement les données validées
        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['telephone'] ?? null,
            ], 'secretaire');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'adresse' => $validated['adresse'] ?? null,
                'email_verified_at' => !empty($validated['email']) ? now() : null,
            ]);

            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'secretaire',
                'Création du secrétaire depuis la route historique.'
            );

            DB::commit();

            $credentials = $this->userService->getCredentialsInfo(
                $user->username,
                $this->userService->generateDefaultPassword()
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Secrétaire créé avec succès')
                ->with('credentials', $credentials);
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création secrétaire annulée', ['type' => get_class($e)]);
            return redirect()->back()
                ->withErrors(['error' => 'La création a échoué. Aucune modification enregistrée.'])
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un secrétaire
     */
    public function show($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        return view('esbtp.secretaires.show', compact('secretaire'));
    }

    /**
     * Affiche le formulaire d'édition d'un secrétaire
     */
    public function edit($id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        return view('esbtp.secretaires.edit', compact('secretaire'));
    }

    /**
     * Met à jour un secrétaire
     */
    public function update(Request $request, $id)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        $this->authorize('update', $secretaire);

        // Capturer les données validées pour éviter la vulnérabilité mass assignment
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'password' => MotDePasseChoisi::facultatifNonConfirme(),
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Récupérer uniquement les données validées
        $validated = $validator->validated();

        // Mettre à jour l'utilisateur avec les données validées uniquement
        $secretaire->name = $validated['name'];
        $secretaire->email = $validated['email'] ?? null;
        $secretaire->username = $validated['username'];
        if (isset($validated['password']) && !empty($validated['password'])) {
            $secretaire->password = Hash::make($validated['password']);
        }
        $secretaire->telephone = $validated['telephone'] ?? null;
        $secretaire->adresse = $validated['adresse'] ?? null;
        $secretaire->save();

        return redirect()->route('secretaires.index')
            ->with('success', 'Secrétaire mis à jour avec succès');
    }

    /**
     * Supprime un secrétaire
     */
    public function destroy($id, SuperAdminLifecycleGuard $lifecycle)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);

        if ($secretaire->id === Auth::id()) {
            return redirect()->back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            app(RoleAssignmentService::class)->change(
                auth()->user(), $secretaire->id, 'remove', 'secretaire',
                'Retrait de la fonction secrétaire depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Fonction secrétaire retirée avec succès');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait secrétaire annulé', ['type' => get_class($e)]);
            return redirect()->back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    /**
     * Toggle secretaire status (active/inactive).
     */
    public function toggleStatus(Request $request, $id, SuperAdminLifecycleGuard $lifecycle)
    {
        $secretaire = User::role('secretaire')->findOrFail($id);
        try {
            $newStatus = $lifecycle->toggleUser(
                $secretaire->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Changement de statut secrétaire impossible', ['type' => get_class($e)]);
            return redirect()->back()->with('error', 'Le changement de statut a échoué. Contactez le support.');
        }

        // Si c'est une requête AJAX, retourner du JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'new_status' => $newStatus ? 'active' : 'inactive'
            ]);
        }

        return redirect()->back()->with('success', 'Statut mis à jour avec succès');
    }

    /**
     * Reset password for a secretaire.
     */
    public function resetPassword(User $secretaire)
    {
        $this->authorize('users.manage');
        $this->authorize('update', $secretaire);

        if (!$secretaire->hasRole('secretaire')) {
            abort(404, 'Secrétaire non trouvé.');
        }

        try {
            $defaultPassword = $this->userService->generateDefaultPassword();

            $secretaire->password = Hash::make($defaultPassword);
            $secretaire->must_change_password = true;
            $secretaire->save();

            \Log::info('🔑 Password reset for secretaire to default', [
                'secretaire_id' => $secretaire->id,
                'secretaire_name' => $secretaire->name,
                'reset_by' => auth()->user()->name,
                'timestamp' => now(),
                'must_change_password' => true
            ]);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'password' => $defaultPassword,
                    'message' => 'Mot de passe réinitialisé avec succès!'
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Mot de passe réinitialisé à '.$defaultPassword.' avec succès! Le secrétaire devra changer son mot de passe à la première connexion.')
                ->with('new_password', $defaultPassword);
        } catch (\Exception $e) {
            \Log::error('❌ Password reset failed for secretaire', [
                'secretaire_id' => $secretaire->id,
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
}
