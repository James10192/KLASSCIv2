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

class ESBTPServiceScolariteController extends Controller
{
    use ResetsStaffPassword;

    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        $this->authorize('services_scolarite.view');

        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        $this->authorize('services_scolarite.create');

        return view('esbtp.services-scolarite.create');
    }

    public function store(Request $request)
    {
        $this->authorize('services_scolarite.create');

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
            ], 'serviceScolarite');

            $user->update([
                'telephone' => $validated['telephone'] ?? null,
                'specialite' => $validated['specialite'] ?? null,
                'email_verified_at' => ! empty($validated['email']) ? now() : null,
            ]);
            $user->assignRole('serviceScolarite');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Service scolarite créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erreur lors de la création : '.$e->getMessage())->withInput();
        }
    }

    public function show(User $serviceScolarite)
    {
        $this->authorize('services_scolarite.view');
        $this->assertServiceScolarite($serviceScolarite);

        $performanceScore = app(PersonnelScoringService::class)->latestFor($serviceScolarite)
            ?: app(PersonnelScoringService::class)->calculate($serviceScolarite);

        return view('esbtp.services-scolarite.show', [
            'service' => $serviceScolarite,
            'performanceScore' => $performanceScore,
        ]);
    }

    public function edit(User $serviceScolarite)
    {
        $this->authorize('services_scolarite.edit');
        $this->assertServiceScolarite($serviceScolarite);

        return view('esbtp.services-scolarite.edit', [
            'service' => $serviceScolarite,
        ]);
    }

    public function update(Request $request, User $serviceScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('services_scolarite.edit');
        $this->assertServiceScolarite($serviceScolarite);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,'.$serviceScolarite->id,
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
                $serviceScolarite->id,
                $updateData,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Service scolarite mis à jour avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour : '.$e->getMessage())->withInput();
        }
    }

    public function destroy(User $serviceScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('services_scolarite.delete');
        $this->assertServiceScolarite($serviceScolarite);

        if ($serviceScolarite->id === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        try {
            $lifecycle->deactivateUser(
                $serviceScolarite->id,
                function (User $user): void {
                    $user->removeRole('serviceScolarite');
                    $user->update([
                        'is_active' => false,
                        'email' => $user->email.'_deleted_'.time(),
                    ]);
                },
                authorize: fn (User $user) => $this->authorize('delete', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Service scolarite désactivé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
        }
    }

    public function resetPassword(User $serviceScolarite)
    {
        $this->assertServiceScolarite($serviceScolarite);

        return $this->resetDefaultStaffPassword($serviceScolarite, 'serviceScolarite', 'Le service scolarité');
    }

    public function toggleStatus(User $serviceScolarite, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->authorize('services_scolarite.edit');
        $this->assertServiceScolarite($serviceScolarite);

        try {
            $isActive = $lifecycle->toggleUser(
                $serviceScolarite->id,
                authorize: fn (User $user) => $this->authorize('update', $user),
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return back()->with('success', "Service scolarite {$status} avec succès.");
    }

    private function assertServiceScolarite(User $user): void
    {
        if (! $user->hasRole('serviceScolarite')) {
            abort(404, 'Service scolarite introuvable.');
        }
    }
}
