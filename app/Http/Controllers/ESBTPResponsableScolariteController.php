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

class ESBTPResponsableScolariteController extends Controller
{
    use ResetsStaffPassword;

    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        $this->authorize('responsables_scolarite.view');

        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        $this->authorize('responsables_scolarite.create');

        return view('esbtp.responsables-scolarite.create');
    }

    public function store(Request $request)
    {
        $this->authorize('responsables_scolarite.create');
        // La création attribue aussi le rôle : même délégation effective que
        // l'écran unique personnel/unified (aucun passe-droit par fonction).
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'responsableScolarite'), 403);

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
            ], 'responsableScolarite');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'email_verified_at' => ! empty($validated['email']) ? now() : null,
            ]);
            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'responsableScolarite',
                'Création de la fonction responsableScolarite depuis la route historique.'
            );

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Responsable scolarite créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création de personnel annulée', ['role' => 'responsableScolarite', 'type' => get_class($e)]);
            return back()->with('error', 'La création a échoué. Aucune modification enregistrée.')->withInput();
        }
    }

    public function show(User $responsableScolarite)
    {
        $this->authorize('responsables_scolarite.view');
        $this->assertResponsableScolarite($responsableScolarite);

        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($responsableScolarite, auth()->user());

        return view('esbtp.responsables-scolarite.show', [
            'responsable' => $responsableScolarite,
            'activite' => $activite,
        ]);
    }

    public function edit(User $responsableScolarite)
    {
        $this->authorize('responsables_scolarite.edit');
        $this->assertResponsableScolarite($responsableScolarite);

        return view('esbtp.responsables-scolarite.edit', [
            'responsable' => $responsableScolarite,
        ]);
    }

    public function update(Request $request, User $responsableScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('responsables_scolarite.edit');
        $this->assertResponsableScolarite($responsableScolarite);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$responsableScolarite->id,
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
                $responsableScolarite->id,
                $updateData,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Responsable scolarite mis à jour avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'La mise à jour a échoué. Contactez le support.')->withInput();
        }
    }

    public function destroy(User $responsableScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('responsables_scolarite.delete');
        $this->assertResponsableScolarite($responsableScolarite);

        if ($responsableScolarite->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            // Retirer seulement la fonction : ne jamais désactiver un compte multi-rôles.
            app(RoleAssignmentService::class)->change(
                auth()->user(), $responsableScolarite->id, 'remove', 'responsableScolarite',
                'Retrait de la fonction responsableScolarite depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Fonction responsableScolarite retirée avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait de fonction annulé', ['role' => 'responsableScolarite', 'type' => get_class($e)]);
            return back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    public function resetPassword(User $responsableScolarite)
    {
        $this->assertResponsableScolarite($responsableScolarite);

        return $this->resetDefaultStaffPassword($responsableScolarite, 'responsableScolarite', 'Le responsable scolarité');
    }

    public function toggleStatus(User $responsableScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('responsables_scolarite.edit');
        $this->assertResponsableScolarite($responsableScolarite);

        try {
            $isActive = $lifecycle->toggleUser(
                $responsableScolarite->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Le changement de statut a échoué. Contactez le support.');
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Responsable scolarite {$status} avec succès.");
    }

    private function assertResponsableScolarite(User $user): void
    {
        if (! $user->hasRole('responsableScolarite')) {
            abort(404, 'Responsable scolarite introuvable.');
        }
    }
}
