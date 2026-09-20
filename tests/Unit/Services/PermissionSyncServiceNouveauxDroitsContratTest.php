<?php

namespace Tests\Unit\Services;

use App\Services\PermissionSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le contrat qui manquait le jour ou les droits du module universitaire ont ete
 * livres sans jamais atteindre une seule instance en service.
 *
 * La synchronisation ne redistribue integralement les defauts qu'aux roles
 * VIDES, pour ne pas ecraser la configuration d'une ecole. Tous les roles de
 * toutes les instances deja ouvertes sont peuples : ils sont donc sautes. Le
 * seul rattrapage est la boucle de « missing defaults », et elle n'ajoute que
 * l'intersection entre les defauts manquants et newFeaturePermissions().
 *
 * Autrement dit : un droit ajoute aux role_defaults sans etre inscrit dans
 * newFeaturePermissions() n'arrive nulle part, et rien ne le signale — la
 * synchronisation se termine sans erreur. C'est exactement ce qui s'est passe.
 *
 * Ce test ferme la porte. Il compare les defauts du registre a deux ensembles :
 *  - BASELINE_DEPLOYEE : les droits deja presents dans les role_defaults avant
 *    l'introduction du module universitaire, donc deja en base sur toute
 *    instance en service ;
 *  - newFeaturePermissions() : la liste de rattrapage.
 *
 * Tout droit hors de ces deux ensembles fait echouer le test, en le nommant.
 *
 * ENTRETIEN : quand une entree est retiree de newFeaturePermissions() parce que
 * toutes les instances l'ont recue, la deplacer dans BASELINE_DEPLOYEE.
 */
class PermissionSyncServiceNouveauxDroitsContratTest extends TestCase
{
    /**
     * Droits presents dans les role_defaults avant le decoupage du module
     * universitaire : deja crees et attribues sur les instances en service.
     *
     * @var list<string>
     */
    private const BASELINE_DEPLOYEE = [
        'academic_alerts.acknowledge',
        'academic_alerts.resolve',
        'academic_alerts.view',
        'academic_alerts.view_own',
        'academic_health.recalculate',
        'academic_health.view',
        'academic_health.view_own',
        'academic_pilotage.view',
        'academic_pilotage.view_all',
        'academic_sheets.assign',
        'academic_sheets.control',
        'academic_sheets.create',
        'academic_sheets.enter',
        'academic_sheets.receive',
        'academic_sheets.submit',
        'academic_sheets.validate',
        'academic_sheets.view',
        'academic_sheets.view_own',
        'admin.access',
        'annees.view',
        'annonces.create',
        'annonces.edit',
        'annonces.view',
        'attendances.create',
        'attendances.delete',
        'attendances.edit',
        'attendances.generate_codes',
        'attendances.justify_own',
        'attendances.justify_process',
        'attendances.sign',
        'attendances.view',
        'attendances.view_own',
        'bts_tronc_commun.view',
        'bts_tronc_commun.view_history',
        'bulletins.configure',
        'bulletins.delete',
        'bulletins.edit',
        'bulletins.export.bulk',
        'bulletins.generate',
        'bulletins.publish.bulk',
        'bulletins.regenerate.bulk',
        'bulletins.view',
        'bulletins.view_own',
        'caissiers.create',
        'caissiers.delete',
        'caissiers.edit',
        'caissiers.view',
        'cash_session.manage',
        'classes.create',
        'classes.edit',
        'classes.view',
        'comptabilite.access',
        'comptabilite.analytics.configure',
        'comptabilite.analytics.refresh',
        'comptabilite.analytics.run_now',
        'comptabilite.analytics.view',
        'comptabilite.audit.view',
        'comptabilite.config.manage',
        'comptabilite.dashboard.view',
        'comptabilite.frais.configure',
        'comptabilite.frais.view',
        'comptabilite.journal.view',
        'comptabilite.paiements.validate',
        'comptabilite.paiements.view',
        'comptabilite.reconciliation.approve',
        'comptabilite.reconciliation.export',
        'comptabilite.reconciliation.open',
        'comptabilite.reconciliation.resolve',
        'comptabilite.reconciliation.view',
        'comptabilite.recouvrement.access',
        'comptabilite.relances.send',
        'comptabilite.reports.export',
        'comptabilite.salaires.configure',
        'comptabilite.salaires.create',
        'comptabilite.salaires.export',
        'comptabilite.salaires.pay',
        'comptabilite.salaires.set_rate',
        'comptabilite.salaires.view',
        'comptables.create',
        'comptables.delete',
        'comptables.edit',
        'comptables.view',
        'coordinateurs.create',
        'coordinateurs.delete',
        'coordinateurs.edit',
        'coordinateurs.view',
        'cycles.create',
        'cycles.delete',
        'cycles.edit',
        'cycles.view',
        'dashboard.view',
        'directeurs_etudes.view',
        'documents.approve',
        'documents.print',
        'documents.view',
        'evaluations.create',
        'evaluations.edit',
        'evaluations.view',
        'exams.view',
        'exams.view_own',
        'exports.schedules.manage',
        'exports.schedules.send_external',
        'filieres.create',
        'filieres.edit',
        'filieres.view',
        'finance.unpaid_count.view',
        'frais.configure',
        'frais.create',
        'frais.edit',
        'frais.view',
        'identity.communicate',
        'identity.coordinate',
        'identity.direct_studies',
        'identity.enrollment_officer',
        'identity.registrar',
        'identity.registrar_clerk',
        'identity.school_manager',
        'identity.student',
        'identity.teach',
        'inscriptions.cancel',
        'inscriptions.candidatures.process',
        'inscriptions.candidatures.view',
        'inscriptions.create',
        'inscriptions.edit',
        'inscriptions.fiche.print',
        'inscriptions.in_kind.mark',
        'inscriptions.manage',
        'inscriptions.reject',
        'inscriptions.restore',
        'inscriptions.specialisation.manage',
        'inscriptions.validate',
        'inscriptions.view',
        'lmd.ajournes.view',
        'lmd.credit_wallet.view',
        'lmd.examens.manage',
        'lmd.examens.notes_lock',
        'lmd.examens.view',
        'lmd.jury.deliberate',
        'lmd.jury.documents.reconcile',
        'lmd.jury.preside',
        'lmd.jury.publish',
        'lmd.jury.view',
        'lmd.planning.edit',
        'lmd.planning.view',
        'lmd.pv.export',
        'lmd.rattrapage.manage',
        'lmd.rattrapage.view',
        'mailpulse.send',
        'mailpulse.view',
        'matieres.create',
        'matieres.edit',
        'matieres.view',
        'messages.receive',
        'messages.send',
        'module.academic_pilotage.access',
        'module.academique.access',
        'module.caisse.access',
        'module.communication.access',
        'module.comptabilite.access',
        'module.emploi_temps.access',
        'module.enseignants.access',
        'module.etudiants.access',
        'module.lmd.access',
        'module.notes_evaluations.access',
        'module.presences.access',
        'niveaux.create',
        'niveaux.edit',
        'niveaux.view',
        'notes.create',
        'notes.edit',
        'notes.import_excel',
        'notes.manage_own',
        'notes.view',
        'notes.view_own',
        'notes.window.manage',
        'paiements.avoir',
        'paiements.create',
        'paiements.create.mobile_money',
        'paiements.edit',
        'paiements.export',
        'paiements.restore',
        'paiements.validate',
        'paiements.view',
        'paiements.view_own',
        'parent_chatbot.manage',
        'performance.recalculate',
        'performance.view',
        'performance.view_all',
        'personnel.manage',
        'personnel.view',
        'planning.edit',
        'planning.manage',
        'planning.view',
        'profile.view_own',
        'reinscriptions.demandes.process',
        'reinscriptions.demandes.view',
        'reports.academic.annuel',
        'reports.academic.rentree',
        'reports.academic.trimestre',
        'reports.generate',
        'reports.view',
        'resultats.edit',
        'resultats.export',
        'resultats.view',
        'schedules.create',
        'schedules.edit',
        'schedules.view',
        'schedules.view_own',
        'secretaires.create',
        'secretaires.delete',
        'secretaires.edit',
        'secretaires.view',
        'security.audit.view',
        'services_scolarite.create',
        'services_scolarite.edit',
        'services_scolarite.view',
        'session_reports.view',
        'session_reports.view_own',
        'settings.edit',
        'settings.pdf.manage',
        'settings.view',
        'students.accessibility.edit',
        'students.accessibility.export',
        'students.accessibility.view',
        'students.accessibility.view_full',
        'students.accessibility.view_own',
        'students.create',
        'students.delete',
        'students.edit',
        'students.restore',
        'students.view',
        'students.view_own',
        'system.manage',
        'teachers.create',
        'teachers.delete',
        'teachers.edit',
        'teachers.view',
        'timetables.create',
        'timetables.delete',
        'timetables.edit',
        'timetables.view',
        'timetables.view_all',
        'timetables.view_own',
        'tpe.declare',
        'tpe.validate',
        'tpe.view_all',
        'trash.view',
        'users.manage',
    ];

    /**
     * Roles dont les defauts valent « tout le registre ». Les inclure
     * reviendrait a exiger que newFeaturePermissions() liste chaque permission
     * de KLASSCI. superAdmin passe de toute facon par Gate::before.
     *
     * @var list<string>
     */
    private const ROLES_JOKER = ['superAdmin', 'serviceTechnique'];

    public function test_tout_droit_par_defaut_est_deja_deploye_ou_inscrit_au_rattrapage(): void
    {
        $couvert = array_flip(array_merge(self::BASELINE_DEPLOYEE, $this->listeDeRattrapage()));

        $orphelins = [];
        foreach ((array) config('permissions.role_defaults', []) as $role => $permissions) {
            if (in_array($role, self::ROLES_JOKER, true)) {
                continue;
            }

            foreach ((array) $permissions as $permission) {
                if ($permission === '*' || isset($couvert[$permission])) {
                    continue;
                }

                $orphelins[$permission][] = $role;
            }
        }

        $this->assertSame([], $orphelins, $this->expliquer($orphelins));
    }

    /**
     * @param  array<string, list<string>>  $orphelins
     */
    private function expliquer(array $orphelins): string
    {
        if ($orphelins === []) {
            return '';
        }

        $lignes = [];
        foreach ($orphelins as $permission => $roles) {
            $lignes[] = '  - ' . $permission . ' (defaut de : ' . implode(', ', $roles) . ')';
        }

        return "Ces droits figurent dans les role_defaults mais n'atteindront AUCUNE instance\n"
            . "deja en service : la synchronisation saute les roles non vides, et la boucle de\n"
            . "rattrapage n'ajoute que ce qui est liste dans PermissionSyncService::newFeaturePermissions().\n"
            . "Inscris-les dans cette methode :\n"
            . implode("\n", $lignes);
    }

    public function test_un_role_deja_peuple_recoit_les_droits_du_rattrapage(): void
    {
        $this->preparerBaseVide();

        $enseignant = Role::create(['name' => 'enseignant', 'guard_name' => 'web']);
        $enseignant->givePermissionTo(
            Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web'])
        );
        $comptable = Role::create(['name' => 'comptable', 'guard_name' => 'web']);
        $comptable->givePermissionTo(Permission::findByName('dashboard.view', 'web'));

        app(PermissionSyncService::class)->run();

        $enseignant = Role::findByName('enseignant', 'web');
        foreach (['lmd.structure.view', 'lmd.notes.view', 'lmd.notes.manage', 'lmd.resultats.view', 'lmd.jury.sign'] as $droit) {
            $this->assertTrue(
                $enseignant->hasPermissionTo($droit),
                "L'enseignant d'une instance deja ouverte doit recevoir « {$droit} » : ce droit est ne du decoupage, aucune ecole n'a pu le retirer."
            );
        }

        // Les droits PREEXISTANTS restent hors du rattrapage. `module.lmd.access`
        // et `lmd.jury.view` existaient avant le decoupage et figuraient deja
        // dans les memes defauts : les rattraper ne les ouvrirait a personne,
        // cela ne ferait que re-accorder en silence, a chaque deploiement, ce
        // qu'une ecole a delibrement retire depuis /esbtp/roles-permissions.
        foreach (['module.lmd.access', 'lmd.jury.view'] as $droit) {
            $this->assertFalse(
                $enseignant->hasPermissionTo($droit),
                "« {$droit} » preexiste au decoupage : le rattrapage ne doit pas le re-accorder, sinon il annule le retrait decide par l'ecole."
            );
        }

        // Le rattrapage ne distribue que ce que le role a dans SES defauts.
        $comptable = Role::findByName('comptable', 'web');
        foreach (['lmd.notes.manage', 'lmd.structure.delete', 'lmd.bulletins.publish'] as $droit) {
            $this->assertFalse(
                $comptable->hasPermissionTo($droit),
                "Le comptable ne doit pas recevoir « {$droit} » : ce n'est pas un de ses defauts."
            );
        }
    }

    public function test_le_nettoyage_ne_reprend_pas_les_droits_qu_il_vient_d_accorder(): void
    {
        $this->preparerBaseVide();

        foreach (['directeurEtudes', 'responsableScolarite'] as $nom) {
            $role = Role::create(['name' => $nom, 'guard_name' => 'web']);
            $role->givePermissionTo(
                Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web'])
            );
        }

        app(PermissionSyncService::class)->run();

        foreach (['directeurEtudes' => 'lmd.structure.manage', 'responsableScolarite' => 'lmd.bulletins.generate'] as $nom => $droit) {
            $this->assertTrue(
                Role::findByName($nom, 'web')->hasPermissionTo($droit),
                "Le nettoyage de derive ne doit pas reprendre « {$droit} » a {$nom} : c'est un de ses defauts."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function listeDeRattrapage(): array
    {
        $methode = new ReflectionMethod(PermissionSyncService::class, 'newFeaturePermissions');
        $methode->setAccessible(true);

        return $methode->invoke(app(PermissionSyncService::class));
    }

    private function preparerBaseVide(): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        $this->sqliteOuverte = true;
    }

    private bool $sqliteOuverte = false;

    protected function tearDown(): void
    {
        if ($this->sqliteOuverte) {
            DB::disconnect('sqlite');
            $this->sqliteOuverte = false;
        }

        parent::tearDown();
    }
}
