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

class ESBTPAgentInscriptionController extends Controller
{
    use ResetsStaffPassword;

    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        $this->authorize('agents_inscription.view');

        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        $this->authorize('agents_inscription.create');

        return view('esbtp.agents-inscription.create');
    }

    public function store(Request $request)
    {
        $this->authorize('agents_inscription.create');
        // La création attribue aussi le rôle : même délégation effective que
        // l'écran unique personnel/unified (aucun passe-droit par fonction).
        abort_unless(app(\App\Services\UserManagementService::class)->canAssignRole($request->user(), 'agentInscription'), 403);

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
            ], 'agentInscription');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'email_verified_at' => ! empty($validated['email']) ? now() : null,
            ]);
            app(RoleAssignmentService::class)->change(
                $request->user(), $user->id, 'add', 'agentInscription',
                'Création de la fonction agentInscription depuis la route historique.'
            );

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Agent d inscription créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (AuthorizationException|ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Création de personnel annulée', ['role' => 'agentInscription', 'type' => get_class($e)]);
            return back()->with('error', 'La création a échoué. Aucune modification enregistrée.')->withInput();
        }
    }

    public function show(User $agentInscription)
    {
        $this->authorize('agents_inscription.view');
        $this->assertAgentInscription($agentInscription);

        $activite = app(\App\Services\Personnel\ActiviteDuPersonnel::class)->resumePour($agentInscription, auth()->user());

        return view('esbtp.agents-inscription.show', [
            'agent' => $agentInscription,
            'activite' => $activite,
        ]);
    }

    public function edit(User $agentInscription)
    {
        $this->authorize('agents_inscription.edit');
        $this->assertAgentInscription($agentInscription);

        return view('esbtp.agents-inscription.edit', [
            'agent' => $agentInscription,
        ]);
    }

    public function update(Request $request, User $agentInscription, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('agents_inscription.edit');
        $this->assertAgentInscription($agentInscription);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$agentInscription->id,
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
                $agentInscription->id,
                $updateData,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Agent d inscription mis à jour avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'La mise à jour a échoué. Contactez le support.')->withInput();
        }
    }

    public function destroy(User $agentInscription, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('agents_inscription.delete');
        $this->assertAgentInscription($agentInscription);

        if ($agentInscription->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            // Retirer seulement la fonction : ne jamais désactiver un compte multi-rôles.
            app(RoleAssignmentService::class)->change(
                auth()->user(), $agentInscription->id, 'remove', 'agentInscription',
                'Retrait de la fonction agentInscription depuis la route historique.'
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Fonction agentInscription retirée avec succès.');
        } catch (AuthorizationException|ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Retrait de fonction annulé', ['role' => 'agentInscription', 'type' => get_class($e)]);
            return back()->with('error', 'Le retrait de cette fonction a échoué.');
        }
    }

    public function resetPassword(User $agentInscription)
    {
        $this->assertAgentInscription($agentInscription);

        return $this->resetDefaultStaffPassword($agentInscription, 'agentInscription', 'L\'agent d\'inscription');
    }

    public function toggleStatus(User $agentInscription, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('agents_inscription.edit');
        $this->assertAgentInscription($agentInscription);

        try {
            $isActive = $lifecycle->toggleUser(
                $agentInscription->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return back()->with('error', 'Le changement de statut a échoué. Contactez le support.');
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Agent d inscription {$status} avec succès.");
    }

    private function assertAgentInscription(User $user): void
    {
        if (! $user->hasRole('agentInscription')) {
            abort(404, 'Agent d inscription introuvable.');
        }
    }
}
