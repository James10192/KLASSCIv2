<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lot 8 — Création de rôles custom via UI.
 *
 * Permet au superAdmin (ou tout utilisateur avec users.manage) de créer des rôles
 * personnalisés depuis /esbtp/personnel/unified avec :
 * - nom interne (slug) + label FR + icône + description
 * - sélection des permissions (par groupe, labels FR)
 * - assignation d'utilisateurs au rôle
 *
 * Sécurité :
 * - Un acteur non-superAdmin ne peut donner que les permissions QU'IL POSSÈDE
 *   (Gate::before couvre superAdmin pour l'octroi total)
 * - Un acteur ne peut assigner un rôle qu'à des utilisateurs qu'il peut gérer
 *   (matrice role_management — Lot 5)
 * - Modification/suppression interdite sur les rôles système (is_custom = false)
 */
class ESBTPCustomRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (! Auth::check() || ! Auth::user()->can('users.manage')) {
                abort(403, 'Vous devez avoir la permission users.manage pour gérer les rôles personnalisés.');
            }
            return $next($request);
        });
    }

    /**
     * Liste des rôles custom (JSON pour AJAX, vue partial pour HTML).
     */
    public function index(Request $request, PermissionRegistry $registry)
    {
        $customRoles = $this->customRolesQuery()
            ->withCount('users')
            ->with('permissions:id,name')
            ->get()
            ->map(function (Role $role) use ($registry) {
                $meta = $registry->roleMeta($role->name) ?? [];
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $meta['label'] ?? $role->name,
                    'icon' => $meta['icon'] ?? 'fa-user-tag',
                    'description' => $meta['description'] ?? '',
                    'users_count' => $role->users_count,
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name')->all(),
                ];
            });

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'roles' => $customRoles,
            ]);
        }

        return view('esbtp.custom-roles._role-card', [
            'customRoles' => $customRoles,
        ]);
    }

    /**
     * Retourne le HTML du modal de création (chargé en AJAX).
     */
    public function create(PermissionRegistry $registry)
    {
        $grantablePermissions = $this->grantablePermissionsForActor($registry);

        return view('esbtp.custom-roles._create-modal', [
            'grantablePermissions' => $grantablePermissions,
        ]);
    }

    /**
     * Crée un rôle custom + sync permissions.
     */
    public function store(Request $request, PermissionRegistry $registry)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',  // snake_case ASCII, commence par lettre
                Rule::unique('roles', 'name'),
            ],
            'label_fr' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.regex' => 'Le nom interne doit être en snake_case (ex: agent_inscriptions).',
            'name.unique' => 'Un rôle avec ce nom existe déjà.',
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
        ]);

        // Garde-fou : empêcher la création d'un rôle réservé
        $reservedNames = array_keys(config('permissions.roles', []));
        if (in_array($validated['name'], $reservedNames, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce nom est réservé à un rôle système.',
            ], 422);
        }

        // Sécurité : restreindre aux permissions que l'acteur possède
        $requestedPerms = collect($validated['permissions'] ?? [])->unique()->values()->all();
        $allowedPerms = $this->grantablePermissionsForActor($registry)
            ->flatten(1)
            ->pluck('name')
            ->all();
        $forbidden = array_diff($requestedPerms, $allowedPerms);
        if (! empty($forbidden)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez accorder que des permissions que vous possédez. Refusées : ' . implode(', ', $forbidden),
                'forbidden' => array_values($forbidden),
            ], 403);
        }

        DB::beginTransaction();
        try {
            $role = new Role();
            $role->name = $validated['name'];
            $role->guard_name = config('auth.defaults.guard', 'web');
            $role->label_fr = $validated['label_fr'];
            $role->icon = $validated['icon'] ?? 'fa-user-tag';
            $role->description = $validated['description'] ?? null;
            $role->is_custom = true;
            $role->created_by_user_id = Auth::id();
            $role->save();

            if (! empty($requestedPerms)) {
                $role->syncPermissions($requestedPerms);
            }

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json([
                'success' => true,
                'message' => "Rôle « {$validated['label_fr']} » créé avec succès.",
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label_fr,
                    'icon' => $role->icon,
                    'description' => $role->description,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retourne le HTML du modal d'édition (chargé en AJAX).
     */
    public function edit(string $role, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            abort(403, 'Les rôles système ne peuvent pas être modifiés ici.');
        }

        $grantablePermissions = $this->grantablePermissionsForActor($registry);
        $assignedPermissions = $roleModel->permissions->pluck('name')->all();

        return view('esbtp.custom-roles._edit-modal', [
            'role' => $roleModel,
            'grantablePermissions' => $grantablePermissions,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    /**
     * Met à jour un rôle custom.
     */
    public function update(Request $request, string $role, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Les rôles système ne peuvent pas être modifiés ici.',
            ], 403);
        }

        $validated = $request->validate([
            'label_fr' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $requestedPerms = collect($validated['permissions'] ?? [])->unique()->values()->all();
        $allowedPerms = $this->grantablePermissionsForActor($registry)
            ->flatten(1)
            ->pluck('name')
            ->all();
        $forbidden = array_diff($requestedPerms, $allowedPerms);
        if (! empty($forbidden)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez accorder que des permissions que vous possédez. Refusées : ' . implode(', ', $forbidden),
                'forbidden' => array_values($forbidden),
            ], 403);
        }

        DB::beginTransaction();
        try {
            $roleModel->label_fr = $validated['label_fr'];
            $roleModel->icon = $validated['icon'] ?? 'fa-user-tag';
            $roleModel->description = $validated['description'] ?? null;
            $roleModel->save();

            $roleModel->syncPermissions($requestedPerms);

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json([
                'success' => true,
                'message' => "Rôle « {$validated['label_fr']} » mis à jour.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprime un rôle custom (refusé si des utilisateurs y sont attachés).
     */
    public function destroy(string $role, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Les rôles système ne peuvent pas être supprimés.',
            ], 403);
        }

        $usersCount = $roleModel->users()->count();
        if ($usersCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Ce rôle est attribué à {$usersCount} utilisateur(s). Détachez-les d'abord avant de supprimer.",
                'users_count' => $usersCount,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $label = $roleModel->label_fr ?? $roleModel->name;
            $roleModel->syncPermissions([]);
            $roleModel->delete();

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json([
                'success' => true,
                'message' => "Rôle « {$label} » supprimé.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retourne le HTML du modal d'assignation users (chargé en AJAX).
     */
    public function assignUsersForm(string $role, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            abort(403, 'L\'assignation se fait via les outils dédiés pour les rôles système.');
        }

        $manageableRoles = $this->manageableRolesForActor($registry);
        $manageableRoles[] = $roleModel->name;  // user déjà attribué = OK

        $assignableUsers = User::query()
            ->whereHas('roles', function ($q) use ($manageableRoles) {
                $q->whereIn('name', $manageableRoles);
            })
            ->orWhere('id', Auth::id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Filtrer doublons et exclure les users qui n'ont QUE des rôles non-gérables
        $assignableUsers = $assignableUsers->unique('id')->values();

        $currentUserIds = $roleModel->users()->pluck('users.id')->all();

        return view('esbtp.custom-roles._assign-users-modal', [
            'role' => $roleModel,
            'assignableUsers' => $assignableUsers,
            'currentUserIds' => $currentUserIds,
        ]);
    }

    /**
     * Synchronise les utilisateurs assignés à un rôle custom.
     */
    public function assignUsers(Request $request, string $role, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée pour les rôles système.',
            ], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $requestedUserIds = collect($validated['user_ids'] ?? [])->unique()->values()->all();

        // Sécurité : vérifier que l'acteur peut gérer ces users
        $manageableRoles = $this->manageableRolesForActor($registry);
        if (! empty($requestedUserIds) && ! empty($manageableRoles)) {
            $forbidden = User::whereIn('id', $requestedUserIds)
                ->whereDoesntHave('roles', function ($q) use ($manageableRoles, $roleModel) {
                    $q->whereIn('name', array_merge($manageableRoles, [$roleModel->name]));
                })
                ->pluck('id')
                ->all();
            if (! empty($forbidden)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas gérer certains utilisateurs sélectionnés (rôle hors de votre périmètre).',
                    'forbidden_user_ids' => $forbidden,
                ], 403);
            }
        }

        DB::beginTransaction();
        try {
            // Récupérer les users actuels pour calculer le diff
            $currentIds = $roleModel->users()->pluck('users.id')->all();
            $toAttach = array_diff($requestedUserIds, $currentIds);
            $toDetach = array_diff($currentIds, $requestedUserIds);

            foreach ($toAttach as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->assignRole($roleModel);
                }
            }
            foreach ($toDetach as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->removeRole($roleModel);
                }
            }

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Affectations mises à jour.',
                'attached' => count($toAttach),
                'detached' => count($toDetach),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Détache un seul user d'un rôle custom (action ligne).
     */
    public function detachUser(string $role, int $user, PermissionRegistry $registry)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();

        if (! ($roleModel->is_custom ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée pour les rôles système.',
            ], 403);
        }

        $userModel = User::findOrFail($user);

        DB::beginTransaction();
        try {
            $userModel->removeRole($roleModel);

            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json([
                'success' => true,
                'message' => "{$userModel->name} détaché du rôle.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────── Helpers privés ───────────────

    /**
     * Query builder pour les rôles custom uniquement (is_custom = true).
     */
    private function customRolesQuery()
    {
        return Role::query()->where('is_custom', true)->orderBy('label_fr')->orderBy('name');
    }

    /**
     * Permissions que l'acteur peut accorder à un nouveau rôle.
     * - superAdmin (via Gate::before) → toutes
     * - autre → uniquement celles qu'il possède
     *
     * Retourne un Collection groupée par "group" du registry.
     */
    private function grantablePermissionsForActor(PermissionRegistry $registry)
    {
        $actor = Auth::user();
        $registryPerms = $registry->all();

        // Toutes les permissions DB (pour avoir id + name)
        $dbPerms = Permission::query()->orderBy('name')->get();

        $grantable = $dbPerms->filter(function (Permission $p) use ($actor) {
            // superAdmin via Gate::before passe toujours
            return $actor->can($p->name);
        });

        // Garder uniquement les canoniques (registry) — masquer les aliases legacy
        $grantable = $grantable->filter(function (Permission $p) use ($registryPerms) {
            return $registryPerms->has($p->name);
        });

        // Grouper par "group" du registry et trier
        $grouped = $grantable
            ->map(function (Permission $p) use ($registryPerms) {
                $meta = $registryPerms[$p->name] ?? [];
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $meta['label'] ?? $p->name,
                    'group' => $meta['group'] ?? 'Autres',
                    'icon' => $meta['icon'] ?? 'fa-key',
                ];
            })
            ->groupBy('group');

        $groupOrder = [
            'Tableau de bord', 'Administration', 'Étudiants', 'Inscriptions',
            'Académique', 'Notes & Évaluations', 'Bulletins', 'Présences',
            'Planning', 'Paiements', 'Frais', 'Comptabilité', 'Personnel',
            'Communication', 'Rapports', 'Résultats', 'Identité',
            'Modules', 'Sécurité', 'Système', 'Autres',
        ];

        $sorted = collect();
        foreach ($groupOrder as $group) {
            if ($grouped->has($group)) {
                $sorted->put($group, $grouped[$group]->sortBy('label')->values());
            }
        }
        foreach ($grouped as $group => $items) {
            if (! $sorted->has($group)) {
                $sorted->put($group, $items->sortBy('label')->values());
            }
        }

        return $sorted;
    }

    /**
     * Liste des rôles que l'acteur peut gérer (matrice Lot 5).
     */
    private function manageableRolesForActor(PermissionRegistry $registry): array
    {
        $actor = Auth::user();

        // superAdmin / serviceTechnique → tous les rôles existants
        if ($actor->hasAnyRole(['superAdmin', 'serviceTechnique'])) {
            return Role::pluck('name')->all();
        }

        $primaryRole = $actor->getRoleNames()->first();
        if (! $primaryRole) {
            return [];
        }

        return $registry->manageableRoles($primaryRole);
    }
}
