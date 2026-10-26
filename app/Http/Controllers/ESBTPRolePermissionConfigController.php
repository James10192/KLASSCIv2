<?php

namespace App\Http\Controllers;

use App\Services\PermissionRegistry;
use App\Services\Personnel\RolePermissionMutationGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ESBTPRolePermissionConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:serviceTechnique']);
    }

    public function index()
    {
        // La gestion des rôles est intégrée aux modales de Personnel/unified.
        // Les POST et l'audit techniques restent protégés et disponibles.
        return redirect()->route('esbtp.personnel.unified.index', ['section' => 'roles']);
    }

    /**
     * Lance permissions:audit et retourne le résultat en JSON pour affichage UI.
     */
    public function audit()
    {
        \Artisan::call('permissions:audit', ['--json' => true]);

        $path = storage_path('app/permissions-audit.json');
        if (! file_exists($path)) {
            return response()->json(['error' => 'Audit non disponible'], 500);
        }

        return response()->json(json_decode(file_get_contents($path), true));
    }

    public function update(Request $request, PermissionRegistry $registry)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $roleName = $validated['role'];
        // Les rôles de secours ne sont jamais modifiables, même via un
        // ancien endpoint technique ou une requête forgée.
        abort_if(in_array($roleName, ['superAdmin', 'serviceTechnique'], true), 403,
            'Les permissions des rôles de secours ne sont pas modifiables.');
        $permissionNames = array_values(array_unique($validated['permissions'] ?? []));

        // Anciens noms masqués à l'écran : chaque nom coché garde ses alias,
        // sinon enregistrer retirerait l'accès aux écrans qui testent encore
        // l'ancien nom, jusqu'au déploiement suivant.
        if (! $request->boolean('show_legacy')) {
            $permissionNames = Permission::where('guard_name', 'web')->whereIn('name', $registry->avecAlias($permissionNames))->pluck('name')->all();
        }

        try {
            [$avant, $apres] = $this->appliquer($roleName, $permissionNames, 'Accordé depuis la configuration des rôles.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Configuration des rôles : enregistrement refusé', ['role' => $roleName, 'exception_type' => get_class($e)]);

            return $this->repondre($request, false, 'Les permissions n\'ont pas été enregistrées. Contactez le support.', $roleName, null);
        }

        Log::info('Configuration des rôles : permissions enregistrées', [
            'role' => $roleName, 'avant' => $avant, 'apres' => count($apres), 'par' => auth()->id(),
        ]);

        return $this->repondre($request, true, "Permissions enregistrées : {$roleName} en a désormais " . count($apres) . " (avant : {$avant}).", $roleName, $apres);
    }

    /**
     * Restaure les permissions par défaut d'un rôle depuis le registry.
     */
    public function restoreDefaults(Request $request, PermissionRegistry $registry)
    {
        $validated = $request->validate(['role' => 'required|exists:roles,name']);
        $roleName = $validated['role'];
        abort_if(in_array($roleName, ['superAdmin', 'serviceTechnique'], true), 403,
            'Les rôles de secours ne peuvent pas être réinitialisés.');

        $expanded = $registry->avecAlias($registry->defaultPermissionsFor($roleName));

        // Filtrer pour ne garder que les permissions qui existent en DB
        $existing = Permission::where('guard_name', 'web')->whereIn('name', $expanded)->pluck('name')->all();

        try {
            // Revenir aux défauts vide aussi les ajouts inscrits pour ce rôle :
            // sans cela, la synchronisation du prochain déploiement remettrait
            // ce que l'école vient de retirer.
            [, $apres] = $this->appliquer($roleName, $existing, 'Défauts restaurés depuis la configuration des rôles.');
        } catch (AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Configuration des rôles : restauration refusée', ['role' => $roleName, 'exception_type' => get_class($e)]);

            return $this->repondre($request, false, 'La restauration des défauts a échoué. Contactez le support.', $roleName, null);
        }

        return $this->repondre($request, true, "Permissions par défaut restaurées pour {$roleName} (" . count($apres) . ' permissions).', $roleName, $apres);
    }

    /**
     * Pose l'ensemble complet des permissions d'un rôle, et inscrit ce qui sort
     * des défauts pour que la synchronisation des déploiements le respecte.
     *
     * @return array{0:int, 1:list<string>} nombre avant, permissions après
     */
    private function appliquer(string $roleName, array $permissionNames, string $motif): array
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $resultat = DB::transaction(function () use ($roleName, $permissionNames, $motif) {
            $role = Role::findByName($roleName, 'web');
            app(RolePermissionMutationGuard::class)->lockAndAssertSafe($role);
            $avantPermissions = $role->permissions()->pluck('name')->all();
            $avant = count($avantPermissions);

            $role->syncPermissions($permissionNames);
            app(\App\Listeners\AuditPermissionChange::class)->enregistrerDecisionDeRole(
                $role, ['permissions' => $avantPermissions], ['permissions' => $permissionNames],
                auth()->id(), $motif
            );
            app(\App\Services\ExtensionsDeRole::class)->enregistrer($role->name, $permissionNames, $motif, auth()->id());

            return [$avant, $role->id];
        });

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $apres = Permission::whereIn('id', DB::table('role_has_permissions')->where('role_id', $resultat[1])->pluck('permission_id'))
            ->orderBy('name')->pluck('name')->values()->all();

        return [$resultat[0], $apres];
    }

    /**
     * JSON pour l'écran (aucun rechargement), redirection pour un envoi classique.
     */
    private function repondre(Request $request, bool $ok, string $message, string $roleName, ?array $permissions)
    {
        if ($request->expectsJson()) {
            return response()->json(array_filter([
                'success' => $ok,
                'message' => $message,
                'role' => $roleName,
                'permissions' => $permissions,
            ], fn ($v) => $v !== null), $ok ? 200 : 500);
        }

        return $ok
            ? redirect()->route('esbtp.personnel.unified.index', ['section' => 'roles'])->with('success', $message)
            : redirect()->back()->with('error', $message);
    }
}
