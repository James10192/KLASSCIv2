<?php

namespace App\Http\Controllers;

use App\Exceptions\LastActiveSuperAdminException;
use App\Models\ESBTPPersonnelScoreSnapshot;
use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\UserService;
use App\Services\UserLifecycle\SuperAdminLifecycleGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ESBTPPersonnelUnifiedController extends Controller
{
    private const TAB_PERMISSIONS = [
        'directeurs_etudes' => [
            'role' => 'directeurEtudes',
            'view' => 'directeurs_etudes.view',
            'create' => 'directeurs_etudes.create',
            'edit' => 'directeurs_etudes.edit',
            'delete' => 'directeurs_etudes.delete',
        ],
        'coordinateurs' => [
            'role' => 'coordinateur',
            'view' => 'coordinateurs.view',
            'create' => 'coordinateurs.create',
            'edit' => 'coordinateurs.edit',
            'delete' => 'coordinateurs.delete',
        ],
        'enseignants' => [
            'role' => 'enseignant',
            'view' => 'teachers.view',
            'create' => 'teachers.create',
            'edit' => 'teachers.edit',
            'delete' => 'teachers.delete',
        ],
        'secretaires' => [
            'role' => 'secretaire',
            'view' => 'secretaires.view',
            'create' => 'secretaires.create',
            'edit' => 'secretaires.edit',
            'delete' => 'secretaires.delete',
        ],
        'responsables_scolarite' => [
            'role' => 'responsableScolarite',
            'view' => 'responsables_scolarite.view',
            'create' => 'responsables_scolarite.create',
            'edit' => 'responsables_scolarite.edit',
            'delete' => 'responsables_scolarite.delete',
        ],
        'services_scolarite' => [
            'role' => 'serviceScolarite',
            'view' => 'services_scolarite.view',
            'create' => 'services_scolarite.create',
            'edit' => 'services_scolarite.edit',
            'delete' => 'services_scolarite.delete',
        ],
        'comptables' => [
            'role' => 'comptable',
            'view' => 'comptables.view',
            'create' => 'comptables.create',
            'edit' => 'comptables.edit',
            'delete' => 'comptables.delete',
        ],
        'caissiers' => [
            'role' => 'caissier',
            'view' => 'caissiers.view',
            'create' => 'caissiers.create',
            'edit' => 'caissiers.edit',
            'delete' => 'caissiers.delete',
        ],
        'agents_inscription' => [
            'role' => 'agentInscription',
            'view' => 'agents_inscription.view',
            'create' => 'agents_inscription.create',
            'edit' => 'agents_inscription.edit',
            'delete' => 'agents_inscription.delete',
        ],
    ];

    /**
     * Champs searchables additionnels par rôle (au-delà de name/email/telephone).
     */
    private const ROLE_SEARCH_FIELDS = [
        'directeurEtudes' => ['specialite'],
        'coordinateur' => ['specialite'],
        'comptable' => ['department'],
        'secretaire' => [],
        'responsableScolarite' => [],
        'serviceScolarite' => [],
        'caissier' => [],
        'agentInscription' => [],
    ];

    public function __construct(protected UserService $userService)
    {
    }

    /**
     * Display a listing of all personnel with sliders.
     */
    public function index(Request $request)
    {
        if (! auth()->user()->can('personnel.view')) {
            abort(403, 'Accès non autorisé');
        }

        // Rôle principal de l'utilisateur connecté — on cache le tab de son propre rôle
        // (un secretaire ne gère pas d'autres secretaires, idem coordinateur).
        $userRole = auth()->user()->getRoleNames()->first();

        // Coordinateurs/secrétaires masqués si l'utilisateur a ce rôle lui-même.
        $personnelAccess = $this->personnelAccessMatrix();
        $visiblePersonnelTabs = collect(array_keys(self::TAB_PERMISSIONS))
            ->filter(fn ($tab) => ($personnelAccess[$tab]['view'] ?? false)
                && ! ($tab === 'directeurs_etudes' && $userRole === 'directeurEtudes')
                && ! ($tab === 'coordinateurs' && $userRole === 'coordinateur')
                && ! ($tab === 'secretaires' && $userRole === 'secretaire')
                && ! ($tab === 'responsables_scolarite' && $userRole === 'responsableScolarite')
                && ! ($tab === 'services_scolarite' && $userRole === 'serviceScolarite')
                && ! ($tab === 'agents_inscription' && $userRole === 'agentInscription'))
            ->values()
            ->all();

        $scolariteSettings = app(\App\Services\TenantScolariteSettings::class);
        if (! $scolariteSettings->splitRolesEnabled()) {
            $visiblePersonnelTabs = array_values(array_filter(
                $visiblePersonnelTabs,
                fn ($tab) => ! in_array($tab, ['responsables_scolarite', 'services_scolarite'], true)
            ));
        }
        if (! $scolariteSettings->agentInscriptionRoleEnabled()) {
            $visiblePersonnelTabs = array_values(array_filter(
                $visiblePersonnelTabs,
                fn ($tab) => $tab !== 'agents_inscription'
            ));
        }

        $directeursEtudes = in_array('directeurs_etudes', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('directeurEtudes') : collect();
        $coordinateurs = in_array('coordinateurs', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('coordinateur') : collect();
        $secretaires = in_array('secretaires', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('secretaire') : collect();
        $responsablesScolarite = in_array('responsables_scolarite', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('responsableScolarite') : collect();
        $servicesScolarite = in_array('services_scolarite', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('serviceScolarite') : collect();
        $comptables = in_array('comptables', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('comptable') : collect();
        $caissiers = in_array('caissiers', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('caissier') : collect();
        $agentsInscription = in_array('agents_inscription', $visiblePersonnelTabs, true) ? $this->loadActiveByRole('agentInscription') : collect();

        $enseignants = in_array('enseignants', $visiblePersonnelTabs, true)
            ? ESBTPTeacher::with(['user'])
                ->whereHas('user', fn ($q) => $q->where('is_active', true))
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $stats = [
            'directeurs_etudes' => $directeursEtudes->count(),
            'coordinateurs' => $coordinateurs->count(),
            'enseignants' => $enseignants->count(),
            'secretaires' => $secretaires->count(),
            'responsables_scolarite' => $responsablesScolarite->count(),
            'services_scolarite' => $servicesScolarite->count(),
            'comptables' => $comptables->count(),
            'caissiers' => $caissiers->count(),
            'agents_inscription' => $agentsInscription->count(),
            'total' => $directeursEtudes->count() + $coordinateurs->count() + $enseignants->count() + $secretaires->count()
                + $responsablesScolarite->count() + $servicesScolarite->count()
                + $comptables->count() + $caissiers->count() + $agentsInscription->count(),
        ];

        $isCoordinateur = ($userRole === 'coordinateur');

        // Lot 8/17 — Rôles custom + standards éditables (visibles si users.manage)
        $customRoles = collect();
        $standardRoles = collect();
        // Lot 19 — users assignés à chaque rôle custom, indexé par roleName, pour générer un tab par rôle
        $customRoleUsers = collect();

        if (auth()->user()->can('personnel.manage')) {
            try {
                $registry = app(PermissionRegistry::class);

                $customRolesQuery = Role::query()
                    ->where('is_custom', true)
                    ->withCount(['users', 'permissions'])
                    ->orderBy('label_fr')
                    ->orderBy('name')
                    ->get();

                $customRoles = $customRolesQuery
                    ->map(fn (Role $role) => $this->buildRoleCardData($role, $registry));

                // Lot 19 — Charger les users de TOUS les rôles custom en une seule requête
                // (évite le N+1 d'une query par rôle).
                $customRoleNames = $customRolesQuery->pluck('name')->all();
                $usersByRole = collect();
                if (! empty($customRoleNames)) {
                    $usersByRole = User::role($customRoleNames)
                        ->where('is_active', true)
                        ->with(['roles:id,name'])
                        ->orderBy('name')
                        ->get(['id', 'name', 'email', 'phone', 'is_active', 'created_at'])
                        ->groupBy(fn ($u) => $u->roles
                            ->whereIn('name', $customRoleNames)
                            ->first()?->name);
                }

                foreach ($customRolesQuery as $role) {
                    // Slug ASCII-safe pour les sélecteurs HTML/JS (data-tab, id, etc.).
                    $customRoleUsers[$role->name] = [
                        'role' => $role,
                        'slug' => Str::slug($role->name) ?: 'role-'.$role->id,
                        'users' => $usersByRole->get($role->name, collect()),
                        'meta' => $registry->roleMeta($role->name) ?? [
                            'label' => $role->name,
                            'icon' => 'fa-user-tag',
                            'description' => '',
                        ],
                    ];
                }

                // Standard roles éditables (Lot 17c) — whitelist depuis le controller.
                $standardRoleNames = ESBTPCustomRoleController::EDITABLE_STANDARD_ROLES;
                $standardRoles = Role::query()
                    ->whereIn('name', $standardRoleNames)
                    ->withCount(['users', 'permissions'])
                    ->get()
                    ->sortBy(fn ($r) => array_search($r->name, $standardRoleNames))
                    ->values()
                    ->map(fn (Role $role) => $this->buildRoleCardData($role, $registry));
            } catch (\Throwable $e) {
                // La page continue de s'afficher sans les cartes de roles : elle sert
                // d'abord a gerer le personnel, et la perdre entierement serait pire.
                //
                // Mais ce rattrapage ne doit plus etre MUET. Une seule colonne mal
                // nommee dans la requete ci-dessus a fait disparaitre les trois
                // cartes — dont celle des roles standards — sur les seules instances
                // ayant des roles personnalises, sans message ni trace, pendant des
                // semaines. Un incident invisible est un incident qu'on ne corrige
                // jamais.
                \Illuminate\Support\Facades\Log::error('[personnel-unified] Cartes de roles indisponibles', [
                    'exception' => $e->getMessage(),
                    'fichier' => $e->getFile().':'.$e->getLine(),
                ]);

                $customRoles = collect();
                $standardRoles = collect();
                $customRoleUsers = collect();
            }
        }

        $performanceSnapshots = ESBTPPersonnelScoreSnapshot::query()
            ->where('period_type', 'month')
            ->latest('period_end')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return view('esbtp.personnel.unified-index', compact(
            'directeursEtudes',
            'coordinateurs',
            'enseignants',
            'secretaires',
            'responsablesScolarite',
            'servicesScolarite',
            'comptables',
            'caissiers',
            'agentsInscription',
            'stats',
            'isCoordinateur',
            'userRole',
            'personnelAccess',
            'visiblePersonnelTabs',
            'customRoles',
            'standardRoles',
            'performanceSnapshots',
            'customRoleUsers'
        ));
    }

    /**
     * Construit le payload affiché dans une cr-role-card pour un Role donné.
     */
    private function buildRoleCardData(Role $role, PermissionRegistry $registry): array
    {
        $meta = $registry->roleMeta($role->name) ?? [];

        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => $meta['label'] ?? $role->name,
            'icon' => $meta['icon'] ?? 'fa-user-tag',
            'description' => $meta['description'] ?? '',
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
        ];
    }

    private function personnelAccessMatrix(): array
    {
        $access = [];
        foreach (self::TAB_PERMISSIONS as $tab => $permissions) {
            $access[$tab] = [
                'view' => $this->canViewPersonnelTab($tab),
                'create' => $this->canManagePersonnelTab($tab, 'create'),
                'edit' => $this->canManagePersonnelTab($tab, 'edit'),
                'delete' => $this->canManagePersonnelTab($tab, 'delete'),
            ];
        }

        return $access;
    }

    private function canViewPersonnelTab(string $tab): bool
    {
        $user = auth()->user();
        $permission = self::TAB_PERMISSIONS[$tab]['view'] ?? null;

        if (! $user || ! $permission) {
            return false;
        }

        if (in_array($tab, ['directeurs_etudes', 'responsables_scolarite', 'services_scolarite', 'agents_inscription'], true)) {
            return $user->can($permission);
        }

        return $user->can('personnel.manage') || $user->can($permission);
    }

    private function canManagePersonnelTab(string $tab, string $action): bool
    {
        $user = auth()->user();
        $permission = self::TAB_PERMISSIONS[$tab][$action] ?? null;

        if (! $user || ! $permission) {
            return false;
        }

        if (in_array($tab, ['directeurs_etudes', 'responsables_scolarite', 'services_scolarite', 'agents_inscription'], true)) {
            return $user->can($permission);
        }

        return $user->can('personnel.manage') || $user->can($permission);
    }

    private function tabForPersonnelType(string $type): ?string
    {
        if ($type === 'enseignant') {
            return 'enseignants';
        }

        foreach (self::TAB_PERMISSIONS as $tab => $permissions) {
            if (($permissions['role'] ?? null) === $type) {
                return $tab;
            }
        }

        return null;
    }

    /**
     * Charge tous les utilisateurs actifs d'un rôle, retourne collect() vide
     * si le rôle n'existe pas (degrade silencieusement).
     */
    private function loadActiveByRole(string $roleName)
    {
        try {
            if (! Role::where('name', $roleName)->exists()) {
                return collect();
            }

            return User::role($roleName)
                ->with(['roles'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Recherche filtrée d'utilisateurs par rôle (utilisée par getData).
     * Retourne collect() vide si le rôle n'existe pas.
     */
    private function searchByRole(string $roleName, ?string $search, ?string $status)
    {
        try {
            if (! Role::where('name', $roleName)->exists()) {
                return collect();
            }

            $query = User::role($roleName)->with(['roles']);

            if ($status) {
                $query->where('is_active', $status === 'active');
            }

            if ($search) {
                $extraFields = self::ROLE_SEARCH_FIELDS[$roleName] ?? [];
                $query->where(function (Builder $q) use ($search, $extraFields) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%");
                    foreach ($extraFields as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                });
            }

            return $query->orderBy('name')->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Garde-fou commun pour la lecture du personnel.
     */
    private function ensureCanViewPersonnel(): void
    {
        $user = auth()->user();
        if ($user && $user->can('personnel.view')) {
            return;
        }

        abort(403, 'AccÃ¨s non autorisÃ©');
    }

    /**
     * Garde-fou commun pour les mutations du personnel.
     */
    private function ensureCanManagePersonnel(): void
    {
        $user = auth()->user();
        if ($user && $user->can('personnel.manage')) {
            return;
        }

        abort(403, 'Accès non autorisé');
    }

    private function ensureCanViewPersonnelType(?string $type): void
    {
        $tab = $type ? $this->tabForPersonnelType($type) : null;
        if ($tab && $this->canViewPersonnelTab($tab)) {
            return;
        }

        abort(403, 'Accès non autorisé');
    }

    private function ensureCanManagePersonnelType(?string $type, string $action): void
    {
        $tab = $type ? $this->tabForPersonnelType($type) : null;
        if ($tab && $this->canManagePersonnelTab($tab, $action)) {
            return;
        }

        abort(403, 'Accès non autorisé');
    }

    /**
     * Get personnel data via AJAX for dynamic loading.
     */
    public function getData(Request $request)
    {
        $this->ensureCanViewPersonnel();

        $type = $request->get('type'); // coordinateur, enseignant, secretaire, comptable, caissier
        $this->ensureCanViewPersonnelType($type);
        $search = $request->get('search');
        $status = $request->get('status');

        if ($type === 'enseignant') {
            $query = ESBTPTeacher::with(['user']);

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('specialization', 'like', "%{$search}%");
            }

            $data = $query->orderBy('created_at', 'desc')->get();
        } elseif (in_array($type, ['directeurEtudes', 'coordinateur', 'secretaire', 'responsableScolarite', 'serviceScolarite', 'agentInscription', 'comptable', 'caissier'], true)) {
            $data = $this->searchByRole($type, $search, $status);
        } else {
            $data = collect();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ]);
    }

    /**
     * Store a newly created personnel in storage.
     */
    public function store(Request $request)
    {
        $type = $request->get('type');
        $this->ensureCanManagePersonnelType($type, 'create');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'nullable|string|max:20',
            'type' => 'required|in:directeurEtudes,coordinateur,enseignant,secretaire,responsableScolarite,serviceScolarite,agentInscription,comptable,caissier',
        ];

        // Règles spécifiques selon le type
        if (in_array($type, ['directeurEtudes', 'coordinateur'], true)) {
            $rules['specialite'] = 'nullable|string|max:255';
        } elseif ($type === 'enseignant') {
            $rules['specialization'] = 'nullable|string|max:255';
            $rules['qualification'] = 'nullable|string|max:255';
        } elseif ($type === 'secretaire') {
            $rules['service'] = 'nullable|string|max:255';
        } elseif ($type === 'comptable') {
            $rules['department'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        try {
            DB::beginTransaction();

            $defaultPassword = $this->userService->generateDefaultPassword();
            $user = $this->userService->createUserWithAutoCredentials([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['telephone'] ?? null,
            ], $validated['type']);

            // Mettre à jour les champs spécifiques au type.
            $user->update(array_filter([
                'specialite' => $validated['specialite'] ?? null,
                'service' => $validated['service'] ?? null,
                'department' => $validated['department'] ?? null,
                'email_verified_at' => now(),
            ]));

            $user->assignRole($validated['type']);

            if ($type === 'enseignant') {
                ESBTPTeacher::create([
                    'user_id' => $user->id,
                    'specialization' => $validated['specialization'] ?? null,
                    'qualification' => $validated['qualification'] ?? null,
                    'status' => 'active',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['type']).' créé avec succès.',
                'data' => $user,
                'credentials' => [
                    'username' => $user->username,
                    'password' => $defaultPassword,
                    'must_change_password' => true,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve the user (and teacher when applicable) for a given personnel type.
     */
    private function resolvePersonnel(string $type, $id): array
    {
        if ($type === 'enseignant') {
            $teacher = ESBTPTeacher::findOrFail($id);

            return [$teacher->user, $teacher];
        }

        return [User::findOrFail($id), null];
    }

    /**
     * Update the specified personnel in storage.
     */
    public function update(Request $request, $type, $id, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManagePersonnelType($type, 'edit');

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ];

        if (in_array($type, ['directeurEtudes', 'coordinateur'], true)) {
            $rules['specialite'] = 'nullable|string|max:255';
        } elseif ($type === 'enseignant') {
            $rules['specialization'] = 'nullable|string|max:255';
            $rules['qualification'] = 'nullable|string|max:255';
        } elseif ($type === 'secretaire') {
            $rules['service'] = 'nullable|string|max:255';
        } elseif ($type === 'comptable') {
            $rules['department'] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            if (in_array($type, ['directeurEtudes', 'coordinateur'], true)) {
                $updateData['specialite'] = $validated['specialite'] ?? null;
            } elseif ($type === 'secretaire') {
                $updateData['service'] = $validated['service'] ?? null;
            } elseif ($type === 'comptable') {
                $updateData['department'] = $validated['department'] ?? null;
            }

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $updatedUser = $lifecycle->updateUser(
                $user->id,
                $updateData,
                function (User $lockedUser) use ($teacher, $validated): void {
                    $teacher?->update([
                    'specialization' => $validated['specialization'] ?? null,
                    'qualification' => $validated['qualification'] ?? null,
                        'status' => $lockedUser->is_active ? 'active' : 'inactive',
                    ]);
                },
                authorize: fn (User $lockedUser) => $this->authorize('update', $lockedUser),
            );

            return response()->json([
                'success' => true,
                'message' => 'Personnel mis à jour avec succès.',
                'data' => $updatedUser,
            ]);
        } catch (LastActiveSuperAdminException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (AuthorizationException) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée sur ce personnel.'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Personnel introuvable.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified personnel from storage.
     */
    public function destroy($type, $id, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManagePersonnelType($type, 'delete');

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 403);
        }

        try {
            $lifecycle->deactivateUser($user->id, function (User $lockedUser) use ($teacher): void {
                $lockedUser->update([
                    'is_active' => false,
                    'email' => $lockedUser->email.'_deleted_'.time(),
                ]);

                $teacher?->update(['status' => 'inactive']);
            }, authorize: fn (User $lockedUser) => $this->authorize('delete', $lockedUser));

            return response()->json([
                'success' => true,
                'message' => 'Personnel supprimé avec succès.',
            ]);
        } catch (LastActiveSuperAdminException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (AuthorizationException) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée sur ce personnel.'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Personnel introuvable.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status of personnel.
     */
    public function toggleStatus($type, $id, SuperAdminLifecycleGuard $lifecycle)
    {
        $this->ensureCanManagePersonnelType($type, 'edit');

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        try {
            $isActive = $lifecycle->toggleUser(
                $user->id,
                function (User $lockedUser) use ($teacher): void {
                    $teacher?->update(['status' => $lockedUser->is_active ? 'active' : 'inactive']);
                },
                authorize: fn (User $lockedUser) => $this->authorize('update', $lockedUser),
            );
        } catch (LastActiveSuperAdminException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (AuthorizationException) {
            return response()->json(['success' => false, 'message' => 'Action non autorisée sur ce personnel.'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['success' => false, 'message' => 'Personnel introuvable.'], 404);
        }

        $status = $isActive ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Personnel {$status} avec succès.",
            'is_active' => $isActive,
        ]);
    }

    /**
     * Get personnel statistics.
     */
    public function getStats()
    {
        $this->ensureCanViewPersonnel();

        $stats = [
            'directeurs_etudes' => $this->roleStats('directeurEtudes'),
            'coordinateurs' => $this->roleStats('coordinateur'),
            'enseignants' => [
                'total' => ESBTPTeacher::count(),
                'actifs' => ESBTPTeacher::where('status', 'active')->count(),
                'inactifs' => ESBTPTeacher::where('status', 'inactive')->count(),
                'nouveau_ce_mois' => ESBTPTeacher::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'secretaires' => $this->roleStats('secretaire'),
        ];

        return response()->json($stats);
    }

    /**
     * Stats actifs/inactifs/nouveaux pour un rôle Spatie.
     */
    private function roleStats(string $roleName): array
    {
        return [
            'total' => User::role($roleName)->count(),
            'actifs' => User::role($roleName)->where('is_active', true)->count(),
            'inactifs' => User::role($roleName)->where('is_active', false)->count(),
            'nouveau_ce_mois' => User::role($roleName)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}
