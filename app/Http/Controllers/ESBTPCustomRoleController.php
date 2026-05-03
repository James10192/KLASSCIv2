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
    /**
     * Rôles système éditables depuis /esbtp/personnel/unified par les users avec
     * `users.manage` (Lot 17). superAdmin et serviceTechnique restent gérés
     * exclusivement via /esbtp/roles-permissions (Service Technique).
     *
     * Pour ces rôles, seuls label_fr / icon / description / permissions sont modifiables.
     * Le `name` interne reste immuable (sinon casse les @can() et le code applicatif).
     */
    public const EDITABLE_STANDARD_ROLES = [
        'secretaire',
        'comptable',
        'caissier',
        'coordinateur',
        'enseignant',
        'etudiant',
    ];

    /**
     * Whitelist des icônes Font Awesome autorisées pour les rôles custom.
     *
     * Empêche un acteur de saisir une classe arbitraire (ex: `fa-skull` ou `' onclick=...`)
     * qui pourrait casser l'UI ou permettre une injection. Toute valeur hors liste est
     * remplacée par le défaut `fa-user-tag`.
     *
     * Pour ajouter une icône : la déclarer ici ET dans `_icon-suggestions.blade.php`
     * pour qu'elle soit suggérée à l'utilisateur.
     */
    private const ALLOWED_ICONS = [
        // Personnel & rôles
        'fa-user-tag', 'fa-user-shield', 'fa-user-tie', 'fa-user-cog', 'fa-user-check',
        'fa-user-plus', 'fa-user-graduate', 'fa-user-md', 'fa-user-secret', 'fa-user-clock',
        'fa-users', 'fa-users-cog', 'fa-id-badge', 'fa-id-card', 'fa-address-card',
        // Métiers
        'fa-cash-register', 'fa-calculator', 'fa-pen-fancy', 'fa-hands-helping',
        'fa-headset', 'fa-magnifying-glass', 'fa-clipboard-list', 'fa-clipboard-check',
        'fa-graduation-cap', 'fa-chalkboard-teacher', 'fa-school', 'fa-book-open',
        'fa-briefcase', 'fa-laptop', 'fa-tools', 'fa-handshake', 'fa-microscope',
        // Sécurité & administration
        'fa-shield-alt', 'fa-shield-halved', 'fa-key', 'fa-lock', 'fa-cog', 'fa-cogs',
        'fa-tasks', 'fa-bullhorn', 'fa-flag', 'fa-star',
    ];

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
     * Normalise une icône utilisateur : si absente de la whitelist → fallback `fa-user-tag`.
     */
    private function normalizeIcon(?string $icon): string
    {
        $icon = trim((string) $icon);
        if ($icon === '' || ! in_array($icon, self::ALLOWED_ICONS, true)) {
            return 'fa-user-tag';
        }
        return $icon;
    }

    /**
     * Retourne la whitelist d'icônes (utilisable depuis les vues pour la cohérence).
     *
     * @return string[]
     */
    public static function allowedIcons(): array
    {
        return self::ALLOWED_ICONS;
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
            'icon' => ['nullable', 'string', 'max:64', Rule::in(self::ALLOWED_ICONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.regex' => 'Le nom interne doit être en snake_case (ex: agent_inscriptions).',
            'name.unique' => 'Un rôle avec ce nom existe déjà.',
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
            'icon.in' => 'Cette icône n\'est pas autorisée. Choisissez parmi les suggestions.',
        ]);

        // Garde-fou : empêcher la création d'un rôle réservé
        $reservedNames = array_keys(config('permissions.roles', []));
        if (in_array($validated['name'], $reservedNames, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce nom est réservé à un rôle système.',
            ], 422);
        }

        $requestedPerms = $this->normalizePermissionsList($validated['permissions'] ?? []);
        if ($denial = $this->denyIfPermissionsNotGrantable($requestedPerms, $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'la création', function () use ($validated, $requestedPerms) {
            $role = new Role();
            $role->name = $validated['name'];
            $role->guard_name = config('auth.defaults.guard', 'web');
            $role->label_fr = $validated['label_fr'];
            $role->icon = $this->normalizeIcon($validated['icon'] ?? null);
            $role->description = $validated['description'] ?? null;
            $role->is_custom = true;
            $role->created_by_user_id = Auth::id();
            $role->save();

            if (! empty($requestedPerms)) {
                $role->syncPermissions($requestedPerms);
            }

            return [
                'success' => true,
                'message' => "Rôle « {$validated['label_fr']} » créé avec succès.",
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label_fr,
                    'icon' => $role->icon,
                    'description' => $role->description,
                ],
            ];
        });
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
            'icon' => ['nullable', 'string', 'max:64', Rule::in(self::ALLOWED_ICONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
            'icon.in' => 'Cette icône n\'est pas autorisée. Choisissez parmi les suggestions.',
        ]);

        $requestedPerms = $this->normalizePermissionsList($validated['permissions'] ?? []);
        if ($denial = $this->denyIfPermissionsNotGrantable($requestedPerms, $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'la mise à jour', function () use ($roleModel, $validated, $requestedPerms) {
            $roleModel->label_fr = $validated['label_fr'];
            $roleModel->icon = $this->normalizeIcon($validated['icon'] ?? null);
            $roleModel->description = $validated['description'] ?? null;
            $roleModel->save();

            $roleModel->syncPermissions($requestedPerms);

            return [
                'success' => true,
                'message' => "Rôle « {$validated['label_fr']} » mis à jour.",
            ];
        });
    }

    /**
     * Modal d'édition d'un rôle standard (système éditable).
     *
     * Ne permet pas de changer le `name` interne. Permet d'override :
     * - label_fr, icon, description (DB metadata, fallback registry config)
     * - permissions assignées (sync, avec garde-fou grantable)
     */
    public function editStandard(string $role, PermissionRegistry $registry)
    {
        if (! in_array($role, self::EDITABLE_STANDARD_ROLES, true)) {
            abort(403, 'Ce rôle système n\'est pas éditable depuis cette page. Contactez le Service Technique.');
        }

        $roleModel = Role::where('name', $role)->firstOrFail();
        $configMeta = config('permissions.roles', [])[$role] ?? [];

        $grantablePermissions = $this->grantablePermissionsForActor($registry);
        $assignedPermissions = $roleModel->permissions->pluck('name')->all();

        return view('esbtp.custom-roles._edit-standard-modal', [
            'role' => $roleModel,
            'configMeta' => $configMeta,
            'grantablePermissions' => $grantablePermissions,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    /**
     * Met à jour un rôle standard (label_fr/icon/description en DB + permissions).
     */
    public function updateStandard(Request $request, string $role, PermissionRegistry $registry)
    {
        if (! in_array($role, self::EDITABLE_STANDARD_ROLES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rôle système n\'est pas éditable depuis cette page. Contactez le Service Technique.',
            ], 403);
        }

        $roleModel = Role::where('name', $role)->firstOrFail();

        $validated = $request->validate([
            'label_fr' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64', Rule::in(self::ALLOWED_ICONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
            'icon.in' => 'Cette icône n\'est pas autorisée. Choisissez parmi les suggestions.',
        ]);

        // Garde-fou : un acteur ne peut donner que les permissions qu'il possède
        // (sauf superAdmin via Gate::before)
        $requestedPerms = $this->normalizePermissionsList($validated['permissions'] ?? []);
        if ($denial = $this->denyIfPermissionsNotGrantable($requestedPerms, $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'la mise à jour', function () use ($roleModel, $validated, $requestedPerms) {
            // Override DB metadata (le registry config sert de fallback)
            $roleModel->label_fr = $validated['label_fr'];
            $roleModel->icon = $this->normalizeIcon($validated['icon'] ?? null);
            $roleModel->description = $validated['description'] ?? null;
            // is_custom reste FALSE (rôle système, pas custom)
            $roleModel->save();

            $roleModel->syncPermissions($requestedPerms);

            return [
                'success' => true,
                'message' => "Rôle système « {$validated['label_fr']} » mis à jour.",
            ];
        });
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

        return $this->runMutation($registry, 'la suppression', function () use ($roleModel) {
            $label = $roleModel->label_fr ?? $roleModel->name;
            $roleModel->syncPermissions([]);
            $roleModel->delete();

            return [
                'success' => true,
                'message' => "Rôle « {$label} » supprimé.",
            ];
        });
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
            ->where('is_active', true)
            ->where(function ($q) use ($manageableRoles) {
                $q->whereHas('roles', function ($sub) use ($manageableRoles) {
                    $sub->whereIn('name', $manageableRoles);
                })->orWhere('id', Auth::id());
            })
            ->with(['roles:id,name,label_fr'])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->unique('id')
            ->values();

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

        return $this->runMutation($registry, 'l\'affectation', function () use ($roleModel, $requestedUserIds) {
            $currentIds = $roleModel->users()->pluck('users.id')->all();
            $toAttach = array_diff($requestedUserIds, $currentIds);
            $toDetach = array_diff($currentIds, $requestedUserIds);

            // Batch operations : 2 queries au lieu de 2*N (cf revue perf simplify)
            if (! empty($toAttach)) {
                $roleModel->users()->attach($toAttach);
            }
            if (! empty($toDetach)) {
                $roleModel->users()->detach($toDetach);
            }

            return [
                'success' => true,
                'message' => 'Affectations mises à jour.',
                'attached' => count($toAttach),
                'detached' => count($toDetach),
            ];
        });
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

        return $this->runMutation($registry, 'le détachement', function () use ($userModel, $roleModel) {
            $userModel->removeRole($roleModel);

            return [
                'success' => true,
                'message' => "{$userModel->name} détaché du rôle.",
            ];
        });
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
     * Normalise un tableau brut de noms de permission : déduplique + valeurs réindexées.
     *
     * @param  array<int, string>  $perms
     * @return array<int, string>
     */
    private function normalizePermissionsList(array $perms): array
    {
        return collect($perms)->unique()->values()->all();
    }

    /**
     * Vérifie que toutes les permissions demandées sont accordables par l'acteur courant.
     * Retourne une JsonResponse 403 listant les refusées si KO, ou null si OK.
     *
     * @param  array<int, string>  $requestedPerms
     */
    private function denyIfPermissionsNotGrantable(array $requestedPerms, PermissionRegistry $registry): ?\Illuminate\Http\JsonResponse
    {
        $allowedPerms = $this->grantablePermissionsForActor($registry)
            ->flatten(1)
            ->pluck('name')
            ->all();

        $forbidden = array_diff($requestedPerms, $allowedPerms);
        if (empty($forbidden)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Vous ne pouvez accorder que des permissions que vous possédez. Refusées : ' . implode(', ', $forbidden),
            'forbidden' => array_values($forbidden),
        ], 403);
    }

    /**
     * Exécute une mutation DB dans une transaction + invalide les caches Spatie + registry.
     * Retourne la JsonResponse construite à partir du payload renvoyé par le callback,
     * ou une JsonResponse 500 en cas d'exception (rollback automatique).
     *
     * @param  string  $action  Nom de l'action en français (ex: "la création"), utilisé dans le message d'erreur.
     * @param  callable  $callback  Closure qui doit retourner un array<string, mixed> à JSONifier.
     */
    private function runMutation(PermissionRegistry $registry, string $action, callable $callback): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();
        try {
            $payload = $callback();
            DB::commit();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $registry->clearCache();

            return response()->json($payload);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => "Erreur lors de {$action} : " . $e->getMessage(),
            ], 500);
        }
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
