<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise rÃ´les & permissions depuis le registry (config/permissions.php).
 *
 * Source de vÃ©ritÃ© unique pour les deux entrypoints :
 * - bin/deploy/fix_permissions.php (script CLI deploy)
 * - App\Http\Controllers\API\CLI\CLIPermissionController::sync (API klassci-cli)
 *
 * Comportement :
 * - CrÃ©e toutes les permissions canoniques + leurs aliases (rÃ©trocompat)
 * - CrÃ©e tous les rÃ´les canoniques
 * - Synchronise les permissions par dÃ©faut UNIQUEMENT pour les rÃ´les vides
 *   (prÃ©serve les configurations live des tenants en prod)
 * - Healing : pour chaque rÃ´le existant, ajoute les canoniques manquantes
 *   correspondant Ã  ses aliases legacy (migration douce)
 */
class PermissionSyncService
{
    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * Lance la synchronisation. Retourne un payload structurÃ© dÃ©crivant les
     * changements appliquÃ©s (utile pour la rÃ©ponse JSON CLI / le log script).
     *
     * @return array{
     *   permissions_count: int,
     *   roles_count: int,
     *   roles_with_defaults_assigned: array<int, array{role: string, permissions_count: int}>,
     *   roles_preserved: array<int, string>,
     *   aliases_healed: array<int, array{role: string, canonicals_added: int}>,
     *   dependencies_healed: array<int, array{role: string, permissions_added: int}>
     * }
     */
    public function run(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allNames = $this->registry->allNames();
        foreach ($allNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = $this->registry->roles();
        $roleModels = [];
        foreach ($roles as $name => $meta) {
            $roleModels[$name] = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $assignedRoles = [];
        $preservedRoles = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            if ($role->permissions()->count() > 0) {
                $preservedRoles[] = $roleName;
                continue;
            }

            $expanded = $this->expandWithAliases(
                $this->applyPermissionDependencies($this->registry->defaultPermissionsFor($roleName), $roleName)
            );
            $role->syncPermissions($expanded);
            $assignedRoles[] = ['role' => $roleName, 'permissions_count' => count($expanded)];
        }

        $missingDefaultsHealed = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            $defaults = $this->expandWithAliases(
                $this->applyPermissionDependencies($this->registry->defaultPermissionsFor($roleName), $roleName)
            );
            $existingNames = $role->permissions()->pluck('name')->all();
            $toAdd = array_values(array_intersect(
                array_diff($defaults, $existingNames),
                $this->expandWithAliases($this->newFeaturePermissions())
            ));
            if ($toAdd !== []) {
                $role->givePermissionTo($toAdd);
                $missingDefaultsHealed[] = [
                    'role' => $roleName,
                    'permissions_added' => count($toAdd),
                ];
            }

            if ($roleName === 'comptable'
                && in_array('paiements.create', $existingNames, true)
                && ! in_array('paiements.create', $defaults, true)
            ) {
                $role->revokePermissionTo('paiements.create');
            }

            $revoked = $this->revokeCanonicalDrift($role, $roleName, $existingNames, $defaults);
            if ($revoked !== []) {
                $existingNames = array_values(array_diff($existingNames, $revoked));
            }
        }

        $healed = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            $existingNames = $role->permissions->pluck('name')->all();
            $toAdd = [];
            foreach ($existingNames as $name) {
                $canonical = $this->registry->canonicalize($name);
                if ($canonical !== $name && !in_array($canonical, $existingNames, true)) {
                    $toAdd[] = $canonical;
                }
            }
            if (!empty($toAdd)) {
                $role->givePermissionTo($toAdd);
                $healed[] = ['role' => $roleName, 'canonicals_added' => count($toAdd)];
            }
        }

        $dependenciesHealed = [];
        foreach ($roles->keys() as $roleName) {
            $role = $roleModels[$roleName];
            $existingNames = $role->permissions()->pluck('name')->all();
            $withDependencies = $this->expandWithAliases($this->applyPermissionDependencies($existingNames, $roleName));
            $toAdd = array_values(array_diff($withDependencies, $existingNames));
            if (! empty($toAdd)) {
                $role->givePermissionTo($toAdd);
                $dependenciesHealed[] = ['role' => $roleName, 'permissions_added' => count($toAdd)];
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->registry->clearCache();

        return [
            'permissions_count' => $allNames->count(),
            'roles_count' => $roles->count(),
            'roles_with_defaults_assigned' => $assignedRoles,
            'roles_preserved' => $preservedRoles,
            'aliases_healed' => $healed,
            'dependencies_healed' => $dependenciesHealed,
            'missing_defaults_healed' => $missingDefaultsHealed,
        ];
    }

    /**
     * Ã‰tend une liste de permissions canoniques avec leurs aliases legacy
     * (Lot 6 rÃ©trocompat). @can('view_students') doit continuer de marcher
     * tant qu'on n'a pas migrÃ© tout le code vers les canoniques.
     *
     * @param  array<int, string>  $canonicals
     * @return array<int, string>
     */
    private function expandWithAliases(array $canonicals): array
    {
        $expanded = [];
        foreach ($canonicals as $canonical) {
            $expanded[] = $canonical;
            foreach ($this->registry->aliasesOf($canonical) as $alias) {
                $expanded[] = $alias;
            }
        }
        return array_values(array_unique($expanded));
    }

    /**
     * New canonical permissions that must land on existing non-empty roles
     * without overwriting a tenant customization.
     *
     * @return array<int, string>
     */
    private function newFeaturePermissions(): array
    {
        return [
            // Nouvelle : la correction d une erreur de mode au guichet, qui
            // n avait pour seule issue qu une session de reconciliation.
            'paiements.correct_mode',
            'identity.direct_studies',
            'identity.registrar',
            'identity.registrar_clerk',
            'identity.enrollment_officer',
            'identity.communicate',
            'mailpulse.view',
            'mailpulse.send',
            'cash_session.manage',
            'inscriptions.in_kind.mark',
            'documents.view',
            'documents.approve',
            'documents.print',
            'notes.window.manage',
            'paiements.create.mobile_money',
            'finance.unpaid_count.view',
            'reports.academic.rentree',
            'reports.academic.trimestre',
            'reports.academic.annuel',
            'directeurs_etudes.view',
            'directeurs_etudes.create',
            'directeurs_etudes.edit',
            'directeurs_etudes.delete',
            'responsables_scolarite.view',
            'responsables_scolarite.create',
            'responsables_scolarite.edit',
            'responsables_scolarite.delete',
            'services_scolarite.view',
            'services_scolarite.create',
            'services_scolarite.edit',
            'services_scolarite.delete',
            'agents_inscription.view',
            'agents_inscription.create',
            'agents_inscription.edit',
            'agents_inscription.delete',

            // Module universitaire : ces droits sont nés avec le découpage du
            // tout-ou-rien `module.lmd.access` en droits métier par route. Les
            // gardes de route voyagent avec le code et se sont donc refermées au
            // déploiement ; sans cette liste, la synchronisation saute tout rôle
            // déjà peuplé — c'est-à-dire tous les rôles de toutes les instances
            // en service — et les attributions n'arrivent jamais. Résultat
            // silencieux : 403 sur la structure, les notes, les résultats, les
            // bulletins et les relevés pour qui doit y accéder.
            'lmd.structure.view',
            'lmd.structure.manage',
            'lmd.structure.delete',
            'lmd.notes.view',
            'lmd.notes.manage',
            'lmd.resultats.view',
            'lmd.bulletins.view',
            'lmd.bulletins.generate',
            'lmd.bulletins.publish',
            'lmd.bulletins.delete',
            'lmd.releve.view',
            'lmd.releve.issue',
            // Ne figure aujourd'hui dans les defauts d'aucun role : le rattrapage
            // n'accorde que l'intersection defauts ∩ liste, donc cette entree ne
            // distribue rien pour l'instant. Elle est la pour le jour ou une ecole
            // — ou une version — l'inscrira dans un role : le rattrapage suivra
            // sans qu'on ait a y repenser.
            'lmd.rattrapage.notes.saisir',
            'lmd.jury.sign',
            // `module.lmd.access` et `lmd.jury.view` n'ont PAS leur place ici :
            // ils existaient avant ce découpage et figuraient déjà dans les
            // défauts des mêmes rôles. Les remettre dans le rattrapage ne les
            // « ouvre » à personne — cela ne fait que ré-accorder, à chaque
            // déploiement et en silence, ce qu'une école a délibérément retiré
            // depuis /esbtp/roles-permissions. Sur une instance BTS qui a fermé
            // le module universitaire, la section réapparaîtrait à chaque
            // synchronisation. Ce rattrapage ne porte que sur des droits NÉS du
            // découpage, qu'aucune école n'a pu révoquer puisqu'ils n'existaient
            // pas encore.
        ];
    }


    /**
     * Canonical org-chart roles stay on their registry pack.
     * SuperAdmin, service technique and tenant custom roles stay untouched.
     *
     * @param  array<int, string>  $existingNames
     * @param  array<int, string>  $defaults
     * @return array<int, string>
     */
    private function revokeCanonicalDrift($role, string $roleName, array $existingNames, array $defaults): array
    {
        if (! in_array($roleName, ['directeurEtudes', 'responsableScolarite', 'serviceScolarite', 'agentInscription', 'chargeCommunication'], true)) {
            return [];
        }

        // Ce que l'etablissement a deliberement ajoute n'est pas de la derive.
        //
        // Sans cette ligne, le nettoyage traite pareil une permission restee la
        // par accident et une decision d'organisation — « chez nous, la
        // scolarite valide les inscriptions » — et efface la seconde a chaque
        // deploiement, sans rien dire. L'ecole voyait son role revenir a l'etat
        // d'usine sans comprendre pourquoi.
        $allowed = array_flip(array_merge($defaults, app(ExtensionsDeRole::class)->pour($roleName)));

        $revoked = [];
        foreach ($existingNames as $name) {
            $canonical = $this->registry->canonicalize($name);
            if (! isset($allowed[$name]) && ! isset($allowed[$canonical])) {
                $role->revokePermissionTo($name);
                $revoked[] = $name;
            }
        }

        return $revoked;
    }

    /**
     * Maintient les permissions dependantes dans les roles synchronises.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function applyPermissionDependencies(array $permissions, ?string $roleName = null): array
    {
        if (in_array('personnel.manage', $permissions, true) && ! in_array('personnel.view', $permissions, true)) {
            $permissions[] = 'personnel.view';
        }

        foreach (['teachers', 'coordinateurs', 'directeurs_etudes', 'secretaires', 'comptables', 'caissiers', 'agents_inscription'] as $scope) {
            $view = $scope.'.view';
            foreach (['create', 'edit', 'delete'] as $action) {
                if (in_array($scope.'.'.$action, $permissions, true) && ! in_array($view, $permissions, true)) {
                    $permissions[] = $view;
                }
            }
        }

        // Self-service baseline : tout rÃ´le portant l'identitÃ© Ã©tudiant DOIT pouvoir
        // consulter ses propres donnÃ©es (notes, bulletin, EDT, absences, profil...).
        // Ã‰vite la dÃ©rive multi-tenant oÃ¹ un rÃ´le etudiant seedÃ© avant l'ajout d'une
        // permission view_own (ex: notes.view_own) la garde manquante (403 silencieux),
        // car le sync prÃ©serve les rÃ´les non vides. Healing idempotent.
        if ($roleName === 'etudiant' || in_array('identity.student', $permissions, true)) {
            foreach ([
                'identity.student',
                'dashboard.view',
                'annonces.view',
                'messages.receive',
                'notes.view_own',
                'bulletins.view_own',
                'attendances.view_own',
                'attendances.justify_own',
                'schedules.view_own',
                'timetables.view_own',
                'profile.view_own',
                'exams.view_own',
            ] as $selfServicePerm) {
                if (! in_array($selfServicePerm, $permissions, true)) {
                    $permissions[] = $selfServicePerm;
                }
            }
        }

        return array_values(array_unique($permissions));
    }
}

