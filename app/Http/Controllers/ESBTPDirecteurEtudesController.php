<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Scoring\PersonnelScoringService;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ESBTPDirecteurEtudesController extends Controller
{
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
            $user->assignRole('directeurEtudes');

            DB::commit();

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Directeur des études créé avec succès.')
                ->with('credentials', $this->userService->getCredentialsInfo(
                    $user->username,
                    $this->userService->generateDefaultPassword()
                ));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Erreur lors de la création : '.$e->getMessage())->withInput();
        }
    }

    public function show(User $directeurEtude)
    {
        $this->authorize('directeurs_etudes.view');
        $this->assertDirecteurEtudes($directeurEtude);

        $performanceScore = app(PersonnelScoringService::class)->latestFor($directeurEtude)
            ?: app(PersonnelScoringService::class)->calculate($directeurEtude);

        return view('esbtp.directeurs-etudes.show', [
            'directeur' => $directeurEtude,
            'performanceScore' => $performanceScore,
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
            'password' => 'nullable|string|min:8|confirmed',
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
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la mise à jour : '.$e->getMessage())->withInput();
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
            $lifecycle->deactivateUser(
                $directeurEtude->id,
                function (User $user): void {
                    $user->removeRole('directeurEtudes');
                    $user->update([
                        'is_active' => false,
                        'email' => $user->email.'_deleted_'.time(),
                    ]);
                },
                authorize: fn (User $user) => $this->authorize('delete', $user),
            );

            return redirect()->route('esbtp.personnel.unified.index')
                ->with('success', 'Directeur des études désactivé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : '.$e->getMessage());
        }
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
            return back()->with('error', $e->getMessage());
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
