<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\MotDePasseChoisi;
use App\Http\Controllers\Concerns\ResetsStaffPassword;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Services\UserService;
use App\Services\Personnel\RoleAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ESBTPDirecteurEtudesController extends Controller
{
    use ResetsStaffPassword;

    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        $this->authorize('directeurs_etudes.view');

        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        $this->authorize('directeurs_etudes.create');

        return view('esbtp.directeurs-etudes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('directeurs_etudes.create');
        // La création attribue aussi le rôle : même délégation effective que
        // l'écran unique personnel/unified (aucun passe-droit par fonction).
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'directeurEtudes'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'phone' => $validated['telephone'] ?? null,
            ], 'directeurEtudes');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'email_verified_at' => ! empty($validated['email']) ? now() : null,
            ]);
            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'directeurEtudes',
                'Création de la fonction directeurEtudes depuis la route historique.'
            );

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Directeur des études créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création de personnel annulée', ['role' => 'directeurEtudes', 'type' => get_class($e)]);
            return back()->with('error', 'La création a échoué. Aucune modification enregistrée.')->withInput();
        }
    }

    public function show(User $directeurEtude)
    {
        $this->authorize('directeurs_etudes.view');
        $this->assertDirecteurEtudes($directeurEtude);

        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($directeurEtude, auth()->user());

        return view('esbtp.directeurs-etudes.show', [
            'directeur' => $directeurEtude,
            'activite' => $activite,
        ]);
    }

    public function edit(User $directeurEtude)
    {
        $this->authorize('directeurs_etudes.edit');
        $this->assertDirecteurEtudes($directeurEtude);

        return view('esbtp.directeurs-etudes.edit', [
            'directeur' => $directeurEtude,
        ]);
    }

    public function update(Request $request, User $directeurEtude, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('directeurs_etudes.edit');
        $this->assertDirecteurEtudes($directeurEtude);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$directeurEtude->id,
            'password' => MotDePasseChoisi::facultatif(),
            'telephone' => 'nullable|string|max:20',
            'specialite' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $lifecycle->updateUser(
                $directeurEtude->id,
                $updateData,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Directeur des études mis à jour avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'La mise à jour a échoué. Contactez le support.')->withInput();
        }
    }

    public function destroy(User $directeurEtude, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('directeurs_etudes.delete');
        $this->assertDirecteurEtudes($directeurEtude);

        if ($directeurEtude->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            // Retirer seulement la fonction : ne jamais désactiver un compte multi-rôles.
            app(RoleAssignmentService::class)->change(
                auth()->user(), $directeurEtude->id, 'remove', 'directeurEtudes',
                'Retrait de la fonction directeurEtudes depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Fonction directeurEtudes retirée avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait de fonction annulé', ['role' => 'directeurEtudes', 'type' => get_class($e)]);
            return back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    public function resetPassword(User $directeurEtude)
    {
        $this->assertDirecteurEtudes($directeurEtude);

        return $this->resetDefaultStaffPassword($directeurEtude, 'directeurEtudes', 'Le directeur des études');
    }

    public function toggleStatus(User $directeurEtude, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('directeurs_etudes.edit');
        $this->assertDirecteurEtudes($directeurEtude);

        try {
            $isActive = $lifecycle->toggleUser(
                $directeurEtude->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Le changement de statut a échoué. Contactez le support.');
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Directeur des études {$status} avec succès.");
    }

    private function assertDirecteurEtudes(User $user): void
    {
        if (! $user->hasRole('directeurEtudes')) {
            abort(404, 'Directeur des études introuvable.');
        }
    }
}
