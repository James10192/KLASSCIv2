<?php

namespace App\Http\Controllers;

use App\Models\ESBTPTeacher;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ESBTPPersonnelUnifiedController extends Controller
{
    /**
     * Champs searchables additionnels par rôle (au-delà de name/email/telephone).
     */
    private const ROLE_SEARCH_FIELDS = [
        'coordinateur' => ['specialite'],
        'comptable' => ['department'],
        'secretaire' => [],
        'caissier' => [],
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
        $coordinateurs = $userRole === 'coordinateur' ? collect() : $this->loadActiveByRole('coordinateur');
        $secretaires = $userRole === 'secretaire' ? collect() : $this->loadActiveByRole('secretaire');
        $comptables = $this->loadActiveByRole('comptable');
        $caissiers = $this->loadActiveByRole('caissier');

        $enseignants = ESBTPTeacher::with(['user'])
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'coordinateurs' => $coordinateurs->count(),
            'enseignants' => $enseignants->count(),
            'secretaires' => $secretaires->count(),
            'comptables' => $comptables->count(),
            'caissiers' => $caissiers->count(),
            'total' => $coordinateurs->count() + $enseignants->count() + $secretaires->count()
                + $comptables->count() + $caissiers->count(),
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
                        ->get(['id', 'name', 'email', 'telephone', 'is_active', 'created_at'])
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
                // Migration pas encore lancée ou registry indispo — degrade silencieusement.
                $customRoles = collect();
                $standardRoles = collect();
                $customRoleUsers = collect();
            }
        }

        return view('esbtp.personnel.unified-index', compact(
            'coordinateurs',
            'enseignants',
            'secretaires',
            'comptables',
            'caissiers',
            'stats',
            'isCoordinateur',
            'userRole',
            'customRoles',
            'standardRoles',
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

    /**
     * Get personnel data via AJAX for dynamic loading.
     */
    public function getData(Request $request)
    {
        $this->ensureCanViewPersonnel();

        $type = $request->get('type'); // coordinateur, enseignant, secretaire, comptable, caissier
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
        } elseif (in_array($type, ['coordinateur', 'secretaire', 'comptable', 'caissier'], true)) {
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
        $this->ensureCanManagePersonnel();

        $type = $request->get('type');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telephone' => 'nullable|string|max:20',
            'type' => 'required|in:coordinateur,enseignant,secretaire,comptable,caissier',
        ];

        // Règles spécifiques selon le type
        if ($type === 'coordinateur') {
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
    public function update(Request $request, $type, $id)
    {
        $this->ensureCanManagePersonnel();

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'telephone' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ];

        if ($type === 'coordinateur') {
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

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telephone' => $validated['telephone'] ?? null,
                'is_active' => $validated['is_active'],
            ];

            if ($type === 'coordinateur') {
                $updateData['specialite'] = $validated['specialite'] ?? null;
            } elseif ($type === 'secretaire') {
                $updateData['service'] = $validated['service'] ?? null;
            } elseif ($type === 'comptable') {
                $updateData['department'] = $validated['department'] ?? null;
            }

            if (! empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            if ($teacher) {
                $teacher->update([
                    'specialization' => $validated['specialization'] ?? null,
                    'qualification' => $validated['qualification'] ?? null,
                    'status' => $validated['is_active'] ? 'active' : 'inactive',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Personnel mis à jour avec succès.',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified personnel from storage.
     */
    public function destroy($type, $id)
    {
        $this->ensureCanManagePersonnel();

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        if ($user->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Marquer comme inactif au lieu de supprimer complètement.
            $user->update([
                'is_active' => false,
                'email' => $user->email.'_deleted_'.time(),
            ]);

            if ($teacher) {
                $teacher->update(['status' => 'inactive']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Personnel supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active status of personnel.
     */
    public function toggleStatus($type, $id)
    {
        $this->ensureCanManagePersonnel();

        [$user, $teacher] = $this->resolvePersonnel($type, $id);

        $user->update(['is_active' => ! $user->is_active]);

        if ($teacher) {
            $teacher->update(['status' => $user->is_active ? 'active' : 'inactive']);
        }

        $status = $user->is_active ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "Personnel {$status} avec succès.",
            'is_active' => $user->is_active,
        ]);
    }

    /**
     * Get personnel statistics.
     */
    public function getStats()
    {
        $this->ensureCanViewPersonnel();

        $stats = [
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
