<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ESBTPRolePermissionConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:serviceTechnique']);
    }

    /**
     * Mapping permission → [label français, groupe, icône du groupe]
     * Les permissions non listées tombent dans "Autres".
     */
    private function getPermissionCatalog(): array
    {
        return [
            // ── Tableau de bord ──
            'view_dashboard'          => ['Accéder au tableau de bord', 'Tableau de bord', 'fa-home'],
            'access_admin'            => ['Accéder à l\'administration', 'Tableau de bord', 'fa-home'],

            // ── Étudiants ──
            'view_students'           => ['Voir les étudiants', 'Étudiants', 'fa-user-graduate'],
            'create_students'         => ['Créer un étudiant', 'Étudiants', 'fa-user-graduate'],
            'edit_students'           => ['Modifier un étudiant', 'Étudiants', 'fa-user-graduate'],
            'delete_students'         => ['Supprimer un étudiant', 'Étudiants', 'fa-user-graduate'],
            'view_own_students'       => ['Voir ses propres étudiants', 'Étudiants', 'fa-user-graduate'],
            'view_own_profile'        => ['Voir son profil', 'Étudiants', 'fa-user-graduate'],

            // ── Inscriptions ──
            'view_inscriptions'       => ['Voir les inscriptions', 'Inscriptions', 'fa-file-signature'],
            'create_inscriptions'     => ['Créer une inscription', 'Inscriptions', 'fa-file-signature'],
            'edit_inscriptions'       => ['Modifier une inscription', 'Inscriptions', 'fa-file-signature'],
            'approve_inscriptions'    => ['Approuver une inscription', 'Inscriptions', 'fa-file-signature'],
            'reject_inscriptions'     => ['Rejeter une inscription', 'Inscriptions', 'fa-file-signature'],
            'inscriptions.view'       => ['Voir les inscriptions', 'Inscriptions', 'fa-file-signature'],
            'inscriptions.create'     => ['Créer une inscription', 'Inscriptions', 'fa-file-signature'],
            'inscriptions.edit'       => ['Modifier une inscription', 'Inscriptions', 'fa-file-signature'],
            'inscriptions.delete'     => ['Supprimer une inscription', 'Inscriptions', 'fa-file-signature'],
            'inscriptions.validate'   => ['Valider une inscription', 'Inscriptions', 'fa-file-signature'],
            'edit inscriptions'       => ['Modifier les inscriptions', 'Inscriptions', 'fa-file-signature'],
            'valider inscriptions'    => ['Valider les inscriptions', 'Inscriptions', 'fa-file-signature'],
            'annuler inscriptions'    => ['Annuler les inscriptions', 'Inscriptions', 'fa-file-signature'],
            'delete inscriptions'     => ['Supprimer les inscriptions', 'Inscriptions', 'fa-file-signature'],

            // ── Classes & Filières ──
            'view_classes'            => ['Voir les classes', 'Classes & Filières', 'fa-school'],
            'create_classes'          => ['Créer une classe', 'Classes & Filières', 'fa-school'],
            'edit_classes'            => ['Modifier une classe', 'Classes & Filières', 'fa-school'],
            'delete_classes'          => ['Supprimer une classe', 'Classes & Filières', 'fa-school'],
            'view_filieres'           => ['Voir les filières', 'Classes & Filières', 'fa-school'],
            'create_filieres'         => ['Créer une filière', 'Classes & Filières', 'fa-school'],
            'edit_filieres'           => ['Modifier une filière', 'Classes & Filières', 'fa-school'],
            'view_niveaux_etudes'     => ['Voir les niveaux d\'études', 'Classes & Filières', 'fa-school'],
            'create_niveaux_etudes'   => ['Créer un niveau d\'études', 'Classes & Filières', 'fa-school'],
            'edit_niveaux_etudes'     => ['Modifier un niveau d\'études', 'Classes & Filières', 'fa-school'],
            'delete_niveaux_etudes'   => ['Supprimer un niveau d\'études', 'Classes & Filières', 'fa-school'],
            'view cycles'             => ['Voir les cycles', 'Classes & Filières', 'fa-school'],
            'create cycles'           => ['Créer un cycle', 'Classes & Filières', 'fa-school'],
            'edit cycles'             => ['Modifier un cycle', 'Classes & Filières', 'fa-school'],
            'delete cycles'           => ['Supprimer un cycle', 'Classes & Filières', 'fa-school'],
            'restore cycles'          => ['Restaurer un cycle', 'Classes & Filières', 'fa-school'],
            'force delete cycles'     => ['Supprimer définitivement un cycle', 'Classes & Filières', 'fa-school'],

            // ── Matières ──
            'view_matieres'           => ['Voir les matières', 'Matières', 'fa-book-open'],
            'create_matieres'         => ['Créer une matière', 'Matières', 'fa-book-open'],
            'edit_matieres'           => ['Modifier une matière', 'Matières', 'fa-book-open'],
            'delete_matieres'         => ['Supprimer une matière', 'Matières', 'fa-book-open'],

            // ── Notes & Évaluations ──
            'view_notes'              => ['Voir les notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'create_notes'            => ['Saisir des notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'edit_notes'              => ['Modifier des notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'edit_existing_notes'     => ['Modifier des notes déjà saisies', 'Notes & Évaluations', 'fa-pen-to-square'],
            'view_own_notes'          => ['Voir ses propres notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'manage_own_notes'        => ['Gérer ses propres notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'view_grades'             => ['Voir les notes (grades)', 'Notes & Évaluations', 'fa-pen-to-square'],
            'view_own_grades'         => ['Voir ses propres notes (grades)', 'Notes & Évaluations', 'fa-pen-to-square'],
            'create_grade'            => ['Saisir une note', 'Notes & Évaluations', 'fa-pen-to-square'],
            'edit_grades'             => ['Modifier des notes (grades)', 'Notes & Évaluations', 'fa-pen-to-square'],
            'delete_grades'           => ['Supprimer des notes', 'Notes & Évaluations', 'fa-pen-to-square'],
            'view_evaluations'        => ['Voir les évaluations', 'Notes & Évaluations', 'fa-pen-to-square'],
            'view_own_exams'          => ['Voir ses propres examens', 'Notes & Évaluations', 'fa-pen-to-square'],
            'create_evaluations'      => ['Créer une évaluation', 'Notes & Évaluations', 'fa-pen-to-square'],
            'edit_evaluations'        => ['Modifier une évaluation', 'Notes & Évaluations', 'fa-pen-to-square'],

            // ── Bulletins & Résultats ──
            'view_bulletins'          => ['Voir les bulletins', 'Bulletins & Résultats', 'fa-file-alt'],
            'generate_bulletins'      => ['Générer les bulletins', 'Bulletins & Résultats', 'fa-file-alt'],
            'edit_bulletins'          => ['Modifier les bulletins', 'Bulletins & Résultats', 'fa-file-alt'],
            'view_own_bulletin'       => ['Voir son propre bulletin', 'Bulletins & Résultats', 'fa-file-alt'],
            'view_resultats'          => ['Voir les résultats', 'Bulletins & Résultats', 'fa-file-alt'],
            'edit_resultats'          => ['Modifier les résultats', 'Bulletins & Résultats', 'fa-file-alt'],
            'view_reports'            => ['Voir les rapports', 'Bulletins & Résultats', 'fa-file-alt'],
            'generate_reports'        => ['Générer des rapports', 'Bulletins & Résultats', 'fa-file-alt'],

            // ── Présences & Émargements ──
            'view_attendances'        => ['Voir les présences', 'Présences', 'fa-calendar-check'],
            'create_attendance'       => ['Enregistrer les présences', 'Présences', 'fa-calendar-check'],
            'create_attendances'      => ['Enregistrer les présences', 'Présences', 'fa-calendar-check'],
            'edit_attendances'        => ['Modifier les présences', 'Présences', 'fa-calendar-check'],
            'delete_attendances'      => ['Supprimer les présences', 'Présences', 'fa-calendar-check'],
            'view_own_attendances'    => ['Voir ses propres présences', 'Présences', 'fa-calendar-check'],
            'view_own_attendance'     => ['Voir ses propres présences', 'Présences', 'fa-calendar-check'],
            'sign_attendance'         => ['Faire l\'émargement', 'Présences', 'fa-calendar-check'],
            'generate-attendance-codes' => ['Générer les codes d\'émargement', 'Présences', 'fa-calendar-check'],

            // ── Emploi du temps ──
            'view_timetables'         => ['Voir les emplois du temps', 'Emploi du temps', 'fa-clock'],
            'create_timetable'        => ['Créer un emploi du temps', 'Emploi du temps', 'fa-clock'],
            'edit_timetables'         => ['Modifier les emplois du temps', 'Emploi du temps', 'fa-clock'],
            'delete_timetables'       => ['Supprimer un emploi du temps', 'Emploi du temps', 'fa-clock'],
            'view_own_timetable'      => ['Voir son propre emploi du temps', 'Emploi du temps', 'fa-clock'],
            'view-all-timetables'     => ['Voir tous les emplois du temps', 'Emploi du temps', 'fa-clock'],
            'manage-planning'         => ['Gérer le planning général', 'Emploi du temps', 'fa-clock'],
            'view_planning_general'   => ['Voir le planning général', 'Emploi du temps', 'fa-clock'],
            'edit_planning_general'   => ['Modifier le planning général', 'Emploi du temps', 'fa-clock'],
            'view_schedules'          => ['Voir les emplois du temps', 'Emploi du temps', 'fa-clock'],
            'create_schedules'        => ['Créer un emploi du temps', 'Emploi du temps', 'fa-clock'],
            'edit_schedules'          => ['Modifier un emploi du temps', 'Emploi du temps', 'fa-clock'],
            'view_own_schedule'       => ['Voir son propre emploi du temps', 'Emploi du temps', 'fa-clock'],

            // ── Paiements & Frais ──
            'paiements.view'          => ['Voir les paiements', 'Paiements & Frais', 'fa-money-bill'],
            'paiements.create'        => ['Créer un paiement', 'Paiements & Frais', 'fa-money-bill'],
            'paiements.edit'          => ['Modifier un paiement', 'Paiements & Frais', 'fa-money-bill'],
            'paiements.delete'        => ['Supprimer un paiement', 'Paiements & Frais', 'fa-money-bill'],
            'paiements.validate'      => ['Valider un paiement', 'Paiements & Frais', 'fa-money-bill'],
            'frais.view'              => ['Voir les frais', 'Paiements & Frais', 'fa-money-bill'],
            'frais.create'            => ['Créer des frais', 'Paiements & Frais', 'fa-money-bill'],
            'frais.edit'              => ['Modifier les frais', 'Paiements & Frais', 'fa-money-bill'],
            'frais.delete'            => ['Supprimer des frais', 'Paiements & Frais', 'fa-money-bill'],
            'frais.configure'         => ['Configurer les frais', 'Paiements & Frais', 'fa-money-bill'],
            'view_payments'           => ['Voir les paiements', 'Paiements & Frais', 'fa-money-bill'],
            'create_payments'         => ['Créer un paiement', 'Paiements & Frais', 'fa-money-bill'],
            'edit_payments'           => ['Modifier un paiement', 'Paiements & Frais', 'fa-money-bill'],

            // ── Comptabilité ──
            'view_comptabilite'             => ['Accéder à la comptabilité', 'Comptabilité', 'fa-calculator'],
            'manage_comptabilite'           => ['Gérer la comptabilité', 'Comptabilité', 'fa-calculator'],
            'comptabilite.access'           => ['Accéder au module comptabilité', 'Comptabilité', 'fa-calculator'],
            'comptabilite.dashboard.view'   => ['Voir le tableau de bord comptable', 'Comptabilité', 'fa-calculator'],
            'comptabilite.relances.send'    => ['Envoyer des relances', 'Comptabilité', 'fa-calculator'],
            'comptabilite.reports.export'   => ['Exporter les rapports comptables', 'Comptabilité', 'fa-calculator'],
            'comptabilite.config.manage'    => ['Configurer la comptabilité', 'Comptabilité', 'fa-calculator'],
            'comptabilite.paiements.view'   => ['Voir les paiements (comptabilité)', 'Comptabilité', 'fa-calculator'],
            'comptabilite.paiements.validate' => ['Valider les paiements', 'Comptabilité', 'fa-calculator'],
            'comptabilite.frais.view'       => ['Voir les frais (comptabilité)', 'Comptabilité', 'fa-calculator'],
            'comptabilite.frais.configure'  => ['Configurer les frais', 'Comptabilité', 'fa-calculator'],
            'comptabilite.audit.view'       => ['Voir l\'audit comptable', 'Comptabilité', 'fa-calculator'],

            // ── Enseignants & Personnel ──
            'view_teachers'           => ['Voir les enseignants', 'Personnel', 'fa-chalkboard-teacher'],
            'create_teachers'         => ['Créer un enseignant', 'Personnel', 'fa-chalkboard-teacher'],
            'edit_teachers'           => ['Modifier un enseignant', 'Personnel', 'fa-chalkboard-teacher'],
            'view_personnel'          => ['Voir le personnel', 'Personnel', 'fa-chalkboard-teacher'],
            'manage_personnel'        => ['Gérer le personnel', 'Personnel', 'fa-chalkboard-teacher'],
            'view_coordinateurs'      => ['Voir les coordinateurs', 'Personnel', 'fa-chalkboard-teacher'],
            'create_coordinateurs'    => ['Créer un coordinateur', 'Personnel', 'fa-chalkboard-teacher'],
            'edit_coordinateurs'      => ['Modifier un coordinateur', 'Personnel', 'fa-chalkboard-teacher'],
            'delete_coordinateurs'    => ['Supprimer un coordinateur', 'Personnel', 'fa-chalkboard-teacher'],
            'manage-users'            => ['Gérer les utilisateurs', 'Personnel', 'fa-chalkboard-teacher'],
            'edit_enseignants'        => ['Modifier le profil enseignant', 'Personnel', 'fa-chalkboard-teacher'],

            // ── Communication ──
            'send_messages'           => ['Envoyer des messages', 'Communication', 'fa-bullhorn'],
            'receive_messages'        => ['Recevoir des messages', 'Communication', 'fa-bullhorn'],
            'view_annonces'           => ['Voir les annonces', 'Communication', 'fa-bullhorn'],
            'create_annonces'         => ['Créer une annonce', 'Communication', 'fa-bullhorn'],
            'edit_annonces'           => ['Modifier une annonce', 'Communication', 'fa-bullhorn'],

            // ── Système & Sécurité ──
            'view_settings'           => ['Voir les paramètres', 'Système', 'fa-cog'],
            'edit_settings'           => ['Modifier les paramètres', 'Système', 'fa-cog'],
            'manage_system'           => ['Gérer le système', 'Système', 'fa-cog'],
            'security.audit.view'     => ['Voir l\'audit de sécurité', 'Système', 'fa-cog'],
            'security.audit.export'   => ['Exporter l\'audit de sécurité', 'Système', 'fa-cog'],
            'security.users.monitor'  => ['Surveiller les utilisateurs', 'Système', 'fa-cog'],

            // ── Activation des modules ──
            'module.enseignants.access'        => ['Module Enseignants', 'Activation des modules', 'fa-toggle-on'],
            'module.notes_evaluations.access'  => ['Module Notes & Évaluations', 'Activation des modules', 'fa-toggle-on'],
            'module.emploi_temps.access'       => ['Module Emploi du temps', 'Activation des modules', 'fa-toggle-on'],
            'module.presences.access'          => ['Module Présences', 'Activation des modules', 'fa-toggle-on'],
            'module.lmd.access'                => ['Module LMD', 'Activation des modules', 'fa-toggle-on'],
            'module.academique.access'         => ['Module Académique (filières, classes, niveaux)', 'Activation des modules', 'fa-toggle-on'],
            'module.etudiants.access'          => ['Module Étudiants (inscriptions, réinscriptions)', 'Activation des modules', 'fa-toggle-on'],
            'module.comptabilite.access'       => ['Module Comptabilité (paiements, frais, relances)', 'Activation des modules', 'fa-toggle-on'],
            'module.communication.access'      => ['Module Communication (annonces, messages)', 'Activation des modules', 'fa-toggle-on'],

            // ── Service Technique ──
            'paywall.configure'              => ['Configurer le paywall', 'Service Technique', 'fa-shield-alt'],
            'paywall.manage_subscriptions'   => ['Gérer les abonnements', 'Service Technique', 'fa-shield-alt'],
            'paywall.extend_subscriptions'   => ['Prolonger les abonnements', 'Service Technique', 'fa-shield-alt'],
            'paywall.view_all_stats'         => ['Voir toutes les statistiques', 'Service Technique', 'fa-shield-alt'],
            'system.technical_access'        => ['Accès technique', 'Service Technique', 'fa-shield-alt'],
            'system.emergency_override'      => ['Accès d\'urgence', 'Service Technique', 'fa-shield-alt'],
        ];
    }

    /**
     * Ordre d'affichage des groupes.
     */
    private function getGroupOrder(): array
    {
        return [
            'Tableau de bord',
            'Étudiants',
            'Inscriptions',
            'Classes & Filières',
            'Matières',
            'Notes & Évaluations',
            'Bulletins & Résultats',
            'Présences',
            'Emploi du temps',
            'Paiements & Frais',
            'Comptabilité',
            'Personnel',
            'Communication',
            'Activation des modules',
            'Système',
            'Service Technique',
        ];
    }

    public function index(Request $request)
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allowedRoles = ['superAdmin', 'secretaire', 'comptable', 'coordinateur', 'enseignant', 'etudiant'];
        $roles = Role::with('permissions')
            ->whereIn('name', $allowedRoles)
            ->orderByRaw("FIELD(name, 'superAdmin', 'secretaire', 'comptable', 'coordinateur', 'enseignant', 'etudiant')")
            ->get();

        $permissions = Permission::orderBy('name')->get();
        $catalog = $this->getPermissionCatalog();
        $groupOrder = $this->getGroupOrder();

        // Grouper par le catalogue, permissions non mappées → "Autres"
        $groupedPermissions = $permissions->groupBy(function ($permission) use ($catalog) {
            $entry = $catalog[$permission->name] ?? null;
            return $entry ? $entry[1] : 'Autres';
        });

        // Trier les groupes selon l'ordre défini
        $sortedGroups = collect();
        foreach ($groupOrder as $groupName) {
            if ($groupedPermissions->has($groupName)) {
                $sortedGroups[$groupName] = $groupedPermissions[$groupName];
            }
        }
        // Ajouter les groupes non listés à la fin
        foreach ($groupedPermissions as $groupName => $items) {
            if (! $sortedGroups->has($groupName)) {
                $sortedGroups[$groupName] = $items;
            }
        }

        $rolePermissions = $roles->mapWithKeys(function ($role) {
            return [$role->name => $role->permissions->pluck('name')->values()];
        });

        $selectedRoleName = $request->input('role', $roles->first()?->name);
        if ($selectedRoleName && ! $roles->contains('name', $selectedRoleName)) {
            $selectedRoleName = $roles->first()?->name;
        }

        $roleGroups = collect([
            'Administration' => ['superAdmin', 'secretaire', 'comptable'],
            'Pédagogie' => ['coordinateur', 'enseignant'],
            'Étudiants' => ['etudiant'],
        ]);

        $groupedRoles = collect();
        foreach ($roleGroups as $label => $roleNames) {
            $matchingRoles = $roles->filter(fn ($role) => in_array($role->name, $roleNames, true))->values();
            if ($matchingRoles->isNotEmpty()) {
                $groupedRoles[$label] = $matchingRoles;
            }
        }

        return view('esbtp.roles-permissions.index', compact(
            'roles',
            'permissions',
            'groupedPermissions',
            'groupedRoles',
            'rolePermissions',
            'selectedRoleName',
            'catalog',
            'sortedGroups'
        ));
    }

    /**
     * Debug log helper - écrit directement dans un fichier dédié
     * (les logs INFO sont filtrés en production)
     */
    private function debugLog(string $message): void
    {
        file_put_contents(
            storage_path('logs/permissions-debug.log'),
            '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n",
            FILE_APPEND
        );
    }

    public function update(Request $request)
    {
        $this->debugLog('=== UPDATE CALLED ===');
        $this->debugLog('Role: ' . $request->input('role'));
        $this->debugLog('Permissions count: ' . count($request->input('permissions', [])));

        // 1. Vider le cache Spatie AVANT tout (comme dans fix_permissions.php ligne 37)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->debugLog('Cache Spatie vidé (avant)');

        // 2. Validation
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);
        $this->debugLog('Validation passée');

        $roleName = $validated['role'];
        $permissionNames = $validated['permissions'] ?? [];

        // 3. Transaction explicite pour garantir la persistance
        \DB::beginTransaction();

        try {
            // Trouver le rôle (comme dans fix_permissions.php)
            $role = Role::findByName($roleName);
            $countBefore = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Role trouvé: {$role->name} (id={$role->id}), guard={$role->guard_name}");
            $this->debugLog("Permissions AVANT en DB: {$countBefore}");

            // 4. syncPermissions (exactement comme fix_permissions.php ligne 325)
            $role->syncPermissions($permissionNames);
            $this->debugLog('syncPermissions() exécuté');

            // 5. Commit explicite
            \DB::commit();
            $this->debugLog('DB COMMIT effectué');

            // 6. Vider le cache APRÈS le commit (comme fix_permissions.php ligne 413)
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->debugLog('Cache Spatie vidé (après)');

            // 7. Vérification directe en DB (sans cache Eloquent)
            $countAfter = \DB::table('role_has_permissions')->where('role_id', $role->id)->count();
            $this->debugLog("Permissions APRÈS en DB: {$countAfter}");
            $this->debugLog("Demandées: " . count($permissionNames) . " | Avant: {$countBefore} | Après: {$countAfter}");
            $this->debugLog('=== UPDATE SUCCESS ===');

            return redirect()
                ->route('esbtp.roles-permissions.index', ['role' => $roleName])
                ->with('success', "Permissions mises à jour pour {$roleName}: {$countAfter} permissions (avant: {$countBefore}).");

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->debugLog('❌ ERREUR: ' . $e->getMessage());
            $this->debugLog('Stack: ' . $e->getTraceAsString());
            $this->debugLog('=== UPDATE FAILED ===');

            return redirect()
                ->back()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }
}
