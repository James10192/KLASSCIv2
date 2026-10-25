<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Lot 8 — Création de rôles custom via UI.
 *
 * Permet au superAdmin (ou tout utilisateur avec personnel.manage) de créer des rôles
 * personnalisés depuis /esbtp/personnel/unified avec :
 * - nom interne (slug) + label FR + icône + description
 * - sélection des permissions (par groupe, labels FR)
 * - assignation d'utilisateurs au rôle
 *
 * Sécurité :
 * - Tout acteur ne peut donner que les permissions QU'IL POSSÈDE,
 *   sans passe-droit implicite lié au nom de rôle.
 * - Un acteur ne peut assigner un rôle qu'à des utilisateurs qu'il peut gérer
 *   (permissions explicites de gestion et délégation du rôle cible)
 * - Modification/suppression interdite sur les rôles système (is_custom = false)
 */
class ESBTPCustomRoleController extends Controller
{
    /**
     * Rôles système éditables depuis /esbtp/personnel/unified par les users avec
     * `personnel.manage` (Lot 17). Les rôles bootstrap superAdmin et
     * serviceTechnique ne sont pas modifiables depuis les modales : les garder
     * immuables évite de verrouiller l'administration de l'instance.
     *
     * Pour ces rôles, seuls label_fr / icon / description / permissions sont modifiables.
     * Le `name` interne reste immuable (sinon casse les @can() et le code applicatif).
     */
    public const EDITABLE_STANDARD_ROLES = [
        'secretaire',
        'responsableScolarite',
        'serviceScolarite',
        'agentInscription',
        'chargeCommunication',
        'comptable',
        'caissier',
        'coordinateur',
        'directeurEtudes',
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
            if (! Auth::check() || ! Auth::user()->can('personnel.manage')) {
                abort(403, 'Vous devez avoir la permission personnel.manage pour gérer les rôles personnalisés.');
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
     * Profil mobile d'un rôle : l'une des valeurs connues, sinon null (pas de shell).
     * Le formulaire envoie '' pour « Aucun » — c'est null en base.
     */
    private function normalizeMobileProfile(?string $profile): ?string
    {
        $profile = trim((string) $profile);

        return MobileProfileResolver::estDeclarable($profile) ? $profile : null;
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
     * Liste des rôles custom, en JSON : la page Personnel la rafraîchit en AJAX.
     * Toute autre demande est renvoyée vers cette page.
     */
    public function index(Request $request, PermissionRegistry $registry)
    {
        // Cette adresse n'a qu'un consommateur : le rafraichissement AJAX de la
        // page Personnel, qui demande du JSON. Un humain qui la tape recevait
        // un fragment de cartes sans mise en page.
        if (! $request->expectsJson()) {
            return redirect()->route('esbtp.personnel.unified.index');
        }

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

        return response()->json([
            'success' => true,
            'roles' => $customRoles,
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
            'mobile_profile' => ['nullable', 'string', Rule::in(array_merge(MobileProfileResolver::PROFILS_DECLARABLES, ['']))],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'name.regex' => 'Le nom interne doit être en snake_case (ex: agent_inscriptions).',
            'name.unique' => 'Un rôle avec ce nom existe déjà.',
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
            'icon.in' => 'Cette icône n\'est pas autorisée. Choisissez parmi les suggestions.',
            'mobile_profile.in' => 'Ce profil mobile n\'existe pas.',
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
        $requestedPerms = $this->applyPermissionDependencies($requestedPerms);
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
            $role->mobile_profile = $this->normalizeMobileProfile($validated['mobile_profile'] ?? null);
            $role->is_custom = true;
            $role->created_by_user_id = Auth::id();
            $role->save();

            if (! empty($requestedPerms)) {
                $role->syncPermissions($requestedPerms);
            }

            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $role, ['permissions' => []], ['permissions' => $requestedPerms], Auth::id(),
                'Création du rôle personnalisé depuis personnel/unified'
            );

            return [
                'success' => true,
                'message' => "Rôle « {$validated['label_fr']} » créé avec succès.",
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $role->label_fr,
                    'icon' => $role->icon,
                    'description' => $role->description,
                    'mobile_profile' => $role->mobile_profile,
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
        $this->assertCustomRolePermissionsManageable($roleModel, $registry);

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
        $this->assertCustomRolePermissionsManageable($roleModel, $registry);

        $validated = $request->validate([
            'label_fr' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64', Rule::in(self::ALLOWED_ICONS)],
            'description' => ['nullable', 'string', 'max:1000'],
            'mobile_profile' => ['nullable', 'string', Rule::in(array_merge(MobileProfileResolver::PROFILS_DECLARABLES, ['']))],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ], [
            'label_fr.required' => 'Le label affiché à l\'utilisateur est obligatoire.',
            'icon.in' => 'Cette icône n\'est pas autorisée. Choisissez parmi les suggestions.',
            'mobile_profile.in' => 'Ce profil mobile n\'existe pas.',
        ]);

        $requestedPerms = $this->normalizePermissionsList($validated['permissions'] ?? []);
        $requestedPerms = $this->applyPermissionDependencies($requestedPerms);
        if ($denial = $this->denyIfPermissionsNotGrantable($requestedPerms, $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'la mise à jour', function () use ($roleModel, $validated, $requestedPerms) {
            $anciennesPermissions = $roleModel->permissions()->pluck('name')->all();
            $roleModel->label_fr = $validated['label_fr'];
            $roleModel->icon = $this->normalizeIcon($validated['icon'] ?? null);
            $roleModel->description = $validated['description'] ?? null;
            $roleModel->mobile_profile = $this->normalizeMobileProfile($validated['mobile_profile'] ?? null);
            $roleModel->save();

            $roleModel->syncPermissions($requestedPerms);
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $roleModel, ['permissions' => $anciennesPermissions],
                ['permissions' => $requestedPerms], Auth::id(),
                'Modification du rôle personnalisé depuis personnel/unified'
            );

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
        $this->assertStandardRoleManageable($roleModel, $registry);
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
        $this->assertStandardRoleManageable($roleModel, $registry);

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
        $requestedPerms = $this->applyPermissionDependencies($requestedPerms);
        if ($denial = $this->denyIfPermissionsNotGrantable($requestedPerms, $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'la mise à jour', function () use ($roleModel, $validated, $requestedPerms, $registry) {
            // Override DB metadata (le registry config sert de fallback)
            $roleModel->label_fr = $validated['label_fr'];
            $roleModel->icon = $this->normalizeIcon($validated['icon'] ?? null);
            $roleModel->description = $validated['description'] ?? null;
            // is_custom reste FALSE (rôle système, pas custom)
            $roleModel->save();

            // La page technique conserve les alias legacy et consigne les
            // extensions volontaires. L'UI personnel doit avoir la même règle,
            // sans quoi le déploiement peut supprimer les droits ajoutés ici.
            $permissionNames = Permission::whereIn('name', $registry->avecAlias($requestedPerms))
                ->pluck('name')->all();
            $roleModel->syncPermissions($permissionNames);
            app(\App\Services\ExtensionsDeRole::class)->enregistrer(
                $roleModel->name,
                $permissionNames,
                'Configuration du rôle depuis la gestion du personnel.',
                Auth::id()
            );

            return [
                'success' => true,
                'message' => "Rôle système « {$validated['label_fr']} » mis à jour.",
                'role' => [
                    'name' => $roleModel->name,
                    'label' => $roleModel->label_fr,
                    'icon' => $roleModel->icon,
                    'description' => $roleModel->description,
                    'permissions_count' => $roleModel->permissions()->count(),
                ],
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
        $this->assertCustomRolePermissionsManageable($roleModel, $registry);

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
        $this->assertCustomRolePermissionsManageable($roleModel, $registry);
        // Le périmètre manage_all ne remplace jamais le droit d'ajouter/retirer.
        abort_unless(
            Auth::user()->checkPermissionTo('personnel.roles.assign', 'web')
                || Auth::user()->checkPermissionTo('personnel.roles.remove', 'web'),
            403, 'Permission d’affectation ou de retrait absente.'
        );

        $manageableRoles = $this->manageableRolesForActor($registry);
        abort_if($manageableRoles === [], 403, 'Aucun périmètre de gestion du personnel.');
        $rolesAdmissibles = array_values(array_unique([...$manageableRoles, $roleModel->name]));

        // Un rôle protégé additionnel exclut le compte. Une permission dédiée
        // permet d'initialiser un compte sans rôle. Un compte désactivé déjà affecté
        // reste visible, faute de quoi la soumission le détacherait en silence.
        $peutInitialiser = Auth::user()->checkPermissionTo('personnel.users.initialize', 'web');
        $assignableUsers = User::query()
            ->where(function ($q) use ($roleModel) {
                $q->where('is_active', true)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', $roleModel->name));
            })
            ->where(function ($q) use ($rolesAdmissibles, $peutInitialiser) {
                $q->whereHas('roles', fn ($roles) => $roles->whereIn('name', $rolesAdmissibles));
                if ($peutInitialiser) {
                    $q->orWhereDoesntHave('roles');
                }
            })
            ->whereDoesntHave('roles', fn ($q) => $q->whereNotIn('name', $rolesAdmissibles))
            ->with(['roles:id,name,label_fr'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active'])
            ->unique('id')
            ->values();

        $allCurrentIds = $roleModel->users()->pluck('users.id')->all();
        abort_if(
            $this->refusAffectation($roleModel, $allCurrentIds, $registry) !== null,
            403,
            'Certaines affectations existantes sont hors de votre périmètre.'
        );
        $currentUserIds = $allCurrentIds;

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

        // La liste envoyée est l'état complet : contrôler les ajouts ET les
        // retraits, sans échapper au contrôle lorsque user_ids est vide.
        $currentIds = $roleModel->users()->pluck('users.id')->all();
        $nouveauxIds = array_values(array_diff($requestedUserIds, $currentIds));
        $retraits = array_values(array_diff($currentIds, $requestedUserIds));
        $operateur = Auth::user();
        $peutAjouter = $operateur->checkPermissionTo('personnel.roles.assign', 'web');
        $peutRetirer = $operateur->checkPermissionTo('personnel.roles.remove', 'web');
        if ((! $peutAjouter && ! $peutRetirer)
            || ($nouveauxIds !== [] && ! $peutAjouter)
            || ($retraits !== [] && ! $peutRetirer)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission explicite d’ajout ou de retrait de rôle absente.',
            ], 403);
        }
        $inactifs = User::whereIn('id', $nouveauxIds)->where('is_active', false)->pluck('id')->all();
        if ($inactifs !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Un compte désactivé ne peut pas recevoir de nouveau rôle.',
                'forbidden_user_ids' => $inactifs,
            ], 403);
        }

        $touchedIds = array_values(array_unique([...$requestedUserIds, ...$currentIds]));
        if ($denial = $this->refusAffectation($roleModel, $touchedIds, $registry)) {
            return $denial;
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

            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $roleModel,
                ['user_ids' => $currentIds],
                ['user_ids' => $requestedUserIds],
                Auth::id(), 'Affectation des utilisateurs du rôle personnalisé'
            );

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

        abort_unless(Auth::user()->checkPermissionTo('personnel.roles.remove', 'web'),
            403, 'Permission de retrait de rôle absente.');

        $userModel = User::findOrFail($user);
        if ($denial = $this->refusAffectation($roleModel, [$userModel->id], $registry)) {
            return $denial;
        }

        return $this->runMutation($registry, 'le détachement', function () use ($userModel, $roleModel) {
            $avant = $roleModel->users()->pluck('users.id')->all();
            $userModel->removeRole($roleModel);
            $apres = array_values(array_diff($avant, [$userModel->id]));
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $roleModel, ['user_ids' => $avant], ['user_ids' => $apres],
                Auth::id(), 'Retrait individuel d\'un rôle personnalisé'
            );

            return [
                'success' => true,
                'message' => "{$userModel->name} détaché du rôle.",
            ];
        });
    }

    // ─────────────── Helpers privés ───────────────

    /**
     * Garde commune aux ajouts, retraits unitaires et synchronisations de
     * membres. Une permission du rôle cible ou un second rôle non gérable
     * doit refuser toute l'opération, y compris les listes vides.
     */
    private function refusAffectation(Role $role, array $userIds, PermissionRegistry $registry): ?\Illuminate\Http\JsonResponse
    {
        // Les comptes de secours sont exclus de TOUTES les modifications de
        // rôles personnalisés : manage_all élargit le périmètre, pas le
        // cycle de vie sensible soumis à une approbation distincte.
        $proteges = User::query()->whereIn('id', $userIds)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['superAdmin', 'serviceTechnique']))
            ->pluck('id')->all();
        if ($proteges !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Compte sensible : approbation distincte obligatoire.',
                'forbidden_user_ids' => $proteges,
            ], 403);
        }

        if ($denial = $this->denyIfPermissionsNotGrantable(
            array_values(array_unique(array_map(
                fn (string $nom) => $registry->canonicalize($nom),
                $role->permissions()->pluck('name')->all()
            ))), $registry
        )) {
            return $denial;
        }

        $manageable = $this->manageableRolesForActor($registry);
        if ($manageable === []) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun périmètre de gestion des utilisateurs ne vous est attribué.',
            ], 403);
        }

        $admissibles = array_values(array_unique([...$manageable, $role->name]));
        $peutInitialiser = Auth::user()->checkPermissionTo('personnel.users.initialize', 'web');
        $interdits = User::query()
            ->whereIn('id', $userIds)
            ->where(function ($q) use ($admissibles, $peutInitialiser) {
                if (! $peutInitialiser) {
                    $q->whereDoesntHave('roles');
                }
                $methode = $peutInitialiser ? 'whereHas' : 'orWhereHas';
                $q->{$methode}('roles', fn ($roles) => $roles->whereNotIn('name', $admissibles));
            })
            ->pluck('id')->all();

        return $interdits === [] ? null : response()->json([
            'success' => false,
            'message' => 'Certains comptes ont des rôles hors de votre périmètre ou ne sont pas encore habilités.',
            'forbidden_user_ids' => $interdits,
        ], 403);
    }

    /**
     * Même invariant pour les rôles personnalisés : contrôler les droits
     * ACTUELS du rôle, pas uniquement les droits proposés. Sans ce garde,
     * un gestionnaire peut retirer des permissions privilégiées qu'il n'a
     * pas lui-même, voire supprimer entièrement le rôle.
     */
    private function assertCustomRolePermissionsManageable(Role $role, PermissionRegistry $registry): void
    {
        $courantes = array_values(array_unique(array_map(
            fn (string $nom) => $registry->canonicalize($nom),
            $role->permissions()->pluck('name')->all()
        )));

        abort_if(
            $this->denyIfPermissionsNotGrantable($courantes, $registry) !== null,
            403,
            'Ce rôle personnalisé contient des permissions hors de votre périmètre.'
        );
    }

    /**
     * Un gestionnaire du personnel ne gère pas automatiquement les rôles
     * de toute l'école : la matrice de gestion s'applique aussi aux rôles
     * standards. Il ne peut pas non plus supprimer des droits qu'il ne
     * possède pas lui-même. Le superadmin reste couvert par sa matrice.
     */
    private function assertStandardRoleManageable(Role $role, PermissionRegistry $registry): void
    {
        abort_unless(
            in_array($role->name, $this->manageableRolesForActor($registry), true),
            403,
            'Ce rôle n’appartient pas à votre périmètre de gestion.'
        );

        $currentPermissions = array_values(array_unique(array_map(
            fn (string $nom) => $registry->canonicalize($nom),
            $role->permissions()->pluck('name')->all()
        )));

        abort_if(
            $this->denyIfPermissionsNotGrantable($currentPermissions, $registry) !== null,
            403,
            'Ce rôle contient des permissions que vous ne pouvez pas administrer.'
        );
    }

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
     * Garde les permissions dependantes coherentes dans les roles editables.
     *
     * @param  array<int, string>  $perms
     * @return array<int, string>
     */
    private function applyPermissionDependencies(array $perms): array
    {
        $set = collect($perms);

        if ($set->contains('personnel.manage') && ! $set->contains('personnel.view')) {
            $set->push('personnel.view');
        }

        foreach (['teachers', 'coordinateurs', 'secretaires', 'comptables', 'caissiers'] as $scope) {
            $view = $scope.'.view';
            foreach (['create', 'edit', 'delete'] as $action) {
                if ($set->contains($scope.'.'.$action) && ! $set->contains($view)) {
                    $set->push($view);
                }
            }
        }

        return $set->unique()->values()->all();
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
            Log::error('Gestion des rôles : mutation annulée', [
                'action' => $action,
                'exception_type' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Erreur lors de {$action}. Aucune modification n'a été enregistrée. Contactez le support.",
            ], 500);
        }
    }

    /**
     * Permissions que l'acteur peut accorder à un nouveau rôle.
     * - tous les utilisateurs → uniquement leurs permissions effectives
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
            // Aucun passe-droit pour un simple nom de rôle
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
     * Périmètre calculé depuis les permissions réelles, pas depuis une matrice
     * de noms de rôles. Une permission directe fonctionne comme une permission
     * provenant d'un rôle personnalisé.
     *
     * Le pouvoir d'affecter un rôle ne permet jamais de transmettre un pouvoir
     * que l'opérateur ne possède pas : vérifier également son contenu Spatie.
     */
    private function manageableRolesForActor(PermissionRegistry $registry): array
    {
        $actor = Auth::user();
        if (! $actor) {
            return [];
        }

        if ($actor->checkPermissionTo('personnel.roles.manage_all', 'web')) {
            return Role::query()->where('guard_name', 'web')->pluck('name')->all();
        }

        if (! $actor->checkPermissionTo('personnel.roles.assign', 'web')) {
            return [];
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', ['superAdmin', 'serviceTechnique'])
            ->with('permissions')
            ->get()
            ->filter(static function (Role $role) use ($actor): bool {
                foreach ($role->permissions as $permission) {
                    if (! $actor->checkPermissionTo($permission->name, 'web')) {
                        return false;
                    }
                }
                return true;
            })
            ->pluck('name')
            ->all();
    }
}
