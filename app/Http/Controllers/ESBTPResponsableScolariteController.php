<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\MotDePasseChoisi;
use App\Services\Scoring\PersonnelScoringService;
use App\Http\Controllers\Concerns\ResetsStaffPassword;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Services\UserService;
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
            $user->assignRole('responsableScolarite');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Responsable scolarite créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erreur lors de la création : '.$e->getMessage())->withInput();
        }
    }

    public function show(User $responsableScolarite)
    {
        $this->authorize('responsables_scolarite.view');
        $this->assertResponsableScolarite($responsableScolarite);

        $performanceScore = app(PersonnelScoringService::class)->latestFor($responsableScolarite)
            ?: app(PersonnelScoringService::class)->calculate($responsableScolarite);

        return view('esbtp.responsables-scolarite.show', [
            'responsable' => $responsableScolarite,
            'performanceScore' => $performanceScore,
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
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour : '.$e->getMessage())->withInput();
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
            $lifecycle->deactivateUser(
                $responsableScolarite->id,
                function (User $user): void {
                    $user->removeRole('responsableScolarite');
                    $user->update([
                        'is_active' => false,
                        'email' => $user->email.'_deleted_'.time(),
                    ]);
                },
                authorize: fn (User $user) => $this->authorize('delete', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Responsable scolarite désactivé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
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
            return back()->with('error', $e->getMessage());
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
