<?php

namespace Tests\Feature\LMD;

use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\EnseignantDeClasseLmd;
use App\Services\LMD\EnseignantDePlanificationLmd;
use App\Services\LMD\LMDImportService;
use App\Services\Notes\MatieresSaisissables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EnseignantPlanificationEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;
    private ESBTPClasse $classe;
    private ESBTPMatiere $ecue;
    private ESBTPUniteEnseignement $ue;
    private User $admin;
    private User $enseignantPlanning;
    private User $autreEnseignant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);

        Role::findOrCreate('enseignant', 'web');
        Role::findOrCreate('superAdmin', 'web');
        foreach (['admin.access', 'module.lmd.access', 'lmd.planning.edit', 'lmd.planning.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'name' => '2025-2026']);
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Génie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Bâtiment et Urbanisme', 'code' => 'BU', 'credits_licence' => 180],
            'filiere' => ['name' => 'Bâtiment', 'code' => 'FBU'],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'BMI1', 'name' => 'Mathématiques', 'credit' => 4, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [['code' => 'BMI11', 'name' => 'Algèbre', 'credit_ecue' => 4]],
            ]],
        ]);

        $parcours = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'L1A Bâtiment',
            'parcours_id' => $parcours->id,
            'filiere_id' => $parcours->filiere_id,
            'systeme_academique' => 'LMD',
            'is_active' => true,
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id'),
        ]);
        $this->ecue = ESBTPMatiere::where('code', 'BMI11')->firstOrFail();
        $this->ue = ESBTPUniteEnseignement::where('code', 'BMI1')->firstOrFail();

        $this->admin = $this->user('admin');
        $this->admin->assignRole('superAdmin');
        // La route LMD exige aussi un droit d'identité. Le nom superAdmin seul
        // ne doit plus contourner cette garde (ancien Gate::before supprimé).
        $this->admin->givePermissionTo(['admin.access', 'module.lmd.access', 'lmd.planning.edit', 'lmd.planning.view']);

        $this->enseignantPlanning = $this->user('planning');
        $this->enseignantPlanning->assignRole('enseignant');
        $this->autreEnseignant = $this->user('autre');
        $this->autreEnseignant->assignRole('enseignant');

        // L'import de maquette a déjà créé cette ligne de planification.
        ESBTPPlanificationAcademique::updateOrCreate([
            'annee_universitaire_id' => $this->annee->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_etude_id' => $this->classe->niveau_etude_id,
            'matiere_id' => $this->ecue->id,
            'semestre' => 1,
        ], [
            'volume_horaire_total' => 30,
            'coefficient' => 1,
            'credits_ects' => 4,
            'enseignant_principal_id' => $this->enseignantPlanning->id,
            'statut' => ESBTPPlanificationAcademique::STATUT_PLANIFIE,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_une_evaluation_lmd_prend_l_enseignant_du_planning_quand_le_pool_est_unique(): void
    {
        $this->actingAs($this->admin);
        $evaluation = ESBTPEvaluation::create($this->evaluationData(['enseignant_id' => $this->autreEnseignant->id]));

        $this->assertSame($this->enseignantPlanning->id, (int) $evaluation->fresh()->enseignant_id);
    }

    public function test_une_evaluation_utilisateur_est_refusee_si_le_planning_n_a_pas_d_enseignant(): void
    {
        ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->update(['enseignant_principal_id' => null]);
        $this->actingAs($this->admin);

        $this->expectException(ValidationException::class);
        ESBTPEvaluation::create($this->evaluationData());
    }

    public function test_une_regularisation_historique_ne_se_bloque_pas_sur_l_affectation(): void
    {
        ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->update(['enseignant_principal_id' => null]);
        $this->actingAs($this->admin);

        $evaluation = ESBTPEvaluation::create($this->evaluationData(['titre' => 'Régularisation SEMESTRE1 — Algèbre']));
        $this->assertNotNull($evaluation->id);
    }

    public function test_l_affectation_rapide_ecrit_dans_le_planning_et_pas_dans_une_config_parallele(): void
    {
        $service = app(EnseignantDePlanificationLmd::class);
        $service->assignerPrincipal($this->classe, $this->ecue->id, $this->annee->id, 'semestre1', $this->autreEnseignant->id, $this->admin->id);

        $this->assertDatabaseHas('esbtp_planifications_academiques', [
            'matiere_id' => $this->ecue->id,
            'semestre' => 1,
            'enseignant_principal_id' => $this->autreEnseignant->id,
        ]);
    }

    public function test_modifier_le_principal_du_pool_ne_recrit_plus_une_classe_deja_confirmee(): void
    {
        $this->actingAs($this->admin);
        $evaluation = ESBTPEvaluation::create($this->evaluationData());
        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();

        $planning->enseignant_principal_id = $this->autreEnseignant->id;
        $planning->save();

        $this->assertSame($this->enseignantPlanning->id, (int) $evaluation->fresh()->enseignant_id);
        $resolution = app(EnseignantDeClasseLmd::class)->resoudre($this->classe, $this->ecue->id, $this->annee->id, 1);
        $this->assertSame('classe', $resolution['source']);
        $this->assertSame($this->enseignantPlanning->id, (int) $resolution['enseignant_id']);
    }

    public function test_deux_classes_du_meme_parcours_peuvent_avoir_deux_professeurs_du_meme_pool(): void
    {
        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();
        $planning->enseignants_secondaires = [$this->autreEnseignant->id];
        $planning->save();

        $classeB = ESBTPClasse::factory()->create([
            'name' => 'L1B Bâtiment',
            'parcours_id' => $this->classe->parcours_id,
            'filiere_id' => $this->classe->filiere_id,
            'systeme_academique' => 'LMD',
            'is_active' => true,
            'niveau_etude_id' => $this->classe->niveau_etude_id,
        ]);

        $this->actingAs($this->admin);
        $evalA = ESBTPEvaluation::create($this->evaluationData(['enseignant_id' => $this->enseignantPlanning->id]));
        $evalB = ESBTPEvaluation::create($this->evaluationData([
            'classe_id' => $classeB->id,
            'titre' => 'Examen Algèbre B',
            'enseignant_id' => $this->autreEnseignant->id,
        ]));

        $service = app(EnseignantDeClasseLmd::class);
        $a = $service->resoudre($this->classe, $this->ecue->id, $this->annee->id, 1);
        $b = $service->resoudre($classeB, $this->ecue->id, $this->annee->id, 1);

        $this->assertSame($this->enseignantPlanning->id, (int) $evalA->fresh()->enseignant_id);
        $this->assertSame($this->autreEnseignant->id, (int) $evalB->fresh()->enseignant_id);
        $this->assertSame($this->enseignantPlanning->id, (int) $a['enseignant_id']);
        $this->assertSame($this->autreEnseignant->id, (int) $b['enseignant_id']);
    }

    public function test_un_conflit_sur_une_classe_est_detecte_puis_la_confirmation_harmonise_les_evaluations(): void
    {
        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();
        $planning->enseignants_secondaires = [$this->autreEnseignant->id];
        $planning->save();
        $this->actingAs($this->admin);

        ESBTPEvaluation::create($this->evaluationData(['enseignant_id' => $this->enseignantPlanning->id]));
        ESBTPEvaluation::withoutEvents(fn () => ESBTPEvaluation::create($this->evaluationData([
            'titre' => 'Deuxième examen historique',
            'enseignant_id' => $this->autreEnseignant->id,
        ])));

        $service = app(EnseignantDeClasseLmd::class);
        $avant = $service->resoudre($this->classe, $this->ecue->id, $this->annee->id, 1);
        $this->assertTrue($avant['conflit']);
        $this->assertTrue($avant['confirmation_requise']);

        $service->confirmer($this->classe, $this->ecue->id, $this->annee->id, 1, $this->enseignantPlanning->id, $this->admin->id);

        $this->assertSame(1, ESBTPEvaluation::where('classe_id', $this->classe->id)->distinct()->count('enseignant_id'));
        $this->assertSame($this->enseignantPlanning->id, (int) ESBTPEvaluation::where('classe_id', $this->classe->id)->value('enseignant_id'));
    }

    public function test_brouillon_et_snapshot_bulletin_utilisent_le_professeur_reel_de_la_classe(): void
    {
        $this->actingAs($this->admin);
        ESBTPEvaluation::create($this->evaluationData());

        $etudiant = ESBTPEtudiant::factory()->create();
        $bulletin = ESBTPLMDBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'parcours_id' => $this->classe->parcours_id,
            'annee_universitaire_id' => $this->annee->id,
            'semestre' => 1,
            'credits_totaux' => 4,
        ]);
        $resultatUe = ESBTPLMDResultatUE::create([
            'bulletin_id' => $bulletin->id,
            'unite_enseignement_id' => $this->ue->id,
            'etudiant_id' => $etudiant->id,
            'credit' => 4,
        ]);
        $resultat = ESBTPLMDResultatECUE::create([
            'bulletin_id' => $bulletin->id,
            'resultat_ue_id' => $resultatUe->id,
            'matiere_id' => $this->ecue->id,
            'etudiant_id' => $etudiant->id,
            'credit' => 4,
        ]);
        $this->assertSame($this->enseignantPlanning->name, $resultat->fresh()->enseignant_affiche);

        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();
        $planning->enseignant_principal_id = $this->autreEnseignant->id;
        $planning->save();
        $this->assertSame($this->enseignantPlanning->name, $resultat->fresh()->enseignant_affiche);

        $bulletin->is_published = true;
        $bulletin->save();
        $this->assertSame($this->enseignantPlanning->name, $resultat->fresh()->enseignant_snapshot_nom);
    }

    public function test_api_metier_des_matieres_lit_la_maquette_lmd_sans_catalogue_bts(): void
    {
        $resultat = app(MatieresSaisissables::class)->pour($this->classe, $this->annee->id);
        $this->assertSame('maquette_lmd', $resultat['source']);
        $this->assertSame([$this->ecue->id], collect($resultat['matieres'])->pluck('id')->all());
        $this->assertFalse($resultat['matieres'][0]['hors_maquette']);
    }

    public function test_le_pool_est_visible_dans_le_patch_et_apres_un_refresh_ajax(): void
    {
        $this->actingAs($this->admin);
        $response = $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => $this->enseignantPlanning->id,
            'enseignants_secondaires' => [$this->autreEnseignant->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('planification.enseignant_ids.0', $this->enseignantPlanning->id)
            ->assertJsonPath('planification.enseignant_ids.1', $this->autreEnseignant->id)
            ->assertJsonPath('planification.enseignants.1.name', $this->autreEnseignant->name);

        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))
            ->assertOk()
            ->assertJsonPath('ids.1', $this->autreEnseignant->id);

        $partial = $this->getJson(route('esbtp.lmd.planning.partial', [
            'parcours_id' => $this->classe->parcours_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
        ]));
        $partial->assertOk();
        $this->assertStringContainsString($this->enseignantPlanning->name, $partial->json('listing'));
        $this->assertStringContainsString($this->autreEnseignant->name, $partial->json('listing'));
    }

    public function test_trois_enseignants_affichent_un_resume_plus_deux_apres_refresh(): void
    {
        $troisieme = $this->user('troisieme');
        $troisieme->assignRole('enseignant');
        $this->actingAs($this->admin);
        $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => $this->enseignantPlanning->id,
            'enseignants_secondaires' => [$this->autreEnseignant->id, $troisieme->id],
        ])->assertOk()->assertJsonCount(3, 'planification.enseignants');

        $partial = $this->getJson(route('esbtp.lmd.planning.partial', [
            'parcours_id' => $this->classe->parcours_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
        ]));
        $partial->assertOk();
        $this->assertStringContainsString($this->enseignantPlanning->name.' +2', $partial->json('listing'));
        $this->assertStringContainsString($troisieme->name, $partial->json('listing'));
    }

    public function test_pool_et_annee_sont_isoles_par_contexte(): void
    {
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025',
            'is_current' => false,
        ]);
        $this->actingAs($this->admin);
        $this->getJson(route('esbtp.lmd.planning.teacher-pool', [
            ...$this->poolQuery(),
            'annee_universitaire_id' => $autreAnnee->id,
        ]))->assertOk()->assertJsonPath('ids', []);
        $this->getJson(route('esbtp.lmd.planning.teacher-pool', [
            ...$this->poolQuery(),
            'semestre' => 2,
        ]))->assertOk()->assertJsonPath('ids', []);
        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))
            ->assertOk()->assertJsonPath('ids.0', $this->enseignantPlanning->id);
    }

    public function test_le_pivot_historique_est_lu_puis_nettoye_apres_une_desaffectation_explicite(): void
    {
        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();
        $planning->enseignant_principal_id = null;
        $planning->enseignants_secondaires = null;
        $planning->save();

        $profil = ESBTPTeacher::create([
            'user_id' => $this->autreEnseignant->id,
            'matricule' => 'LMD-LEGACY-1',
            'status' => 'active',
            'is_active' => true,
        ]);
        $planning->teachers()->attach($profil->id);

        $this->actingAs($this->admin);
        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))
            ->assertOk()->assertJsonPath('ids.0', $this->autreEnseignant->id);

        $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => null,
            'enseignants_secondaires' => [],
        ])->assertOk()->assertJsonPath('planification.enseignant_ids', []);

        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))
            ->assertOk()->assertJsonPath('ids', []);
        $this->assertDatabaseMissing('esbtp_planification_teachers', [
            'planification_id' => $planning->id,
            'teacher_id' => $profil->id,
        ]);
    }

    public function test_un_patch_identique_nettoie_un_ancien_lien_pivot_obsolete(): void
    {
        $planning = ESBTPPlanificationAcademique::where('matiere_id', $this->ecue->id)->firstOrFail();
        $planning->enseignants_secondaires = [];
        $planning->save();

        $profil = ESBTPTeacher::create([
            'user_id' => $this->autreEnseignant->id,
            'matricule' => 'LMD-GHOST-2',
            'status' => 'active',
            'is_active' => true,
        ]);
        $planning->teachers()->attach($profil->id);

        $this->actingAs($this->admin);
        $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => $this->enseignantPlanning->id,
            'enseignants_secondaires' => [],
        ])->assertOk();

        $this->assertDatabaseMissing('esbtp_planification_teachers', [
            'planification_id' => $planning->id,
            'teacher_id' => $profil->id,
        ]);
        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))
            ->assertOk()->assertJsonPath('ids', [$this->enseignantPlanning->id]);
    }

    public function test_la_lecture_est_permise_mais_l_edition_exige_lmd_planning_edit(): void
    {
        $reader = $this->user('lecteur');
        $reader->givePermissionTo(['module.lmd.access', 'lmd.planning.view']);
        $this->actingAs($reader);

        $this->getJson(route('esbtp.lmd.planning.teacher-pool', $this->poolQuery()))->assertOk();
        $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => $this->autreEnseignant->id,
            'enseignants_secondaires' => [],
        ])->assertForbidden();
        $this->patchJson(route('esbtp.lmd.ues.update-responsable', ['ueId' => $this->ue->id]), [
            'responsable_ue_id' => $this->autreEnseignant->id,
        ])->assertForbidden();
    }

    public function test_un_non_enseignant_est_refuse_et_le_responsable_ue_reapparait_dans_le_partial(): void
    {
        $nonEnseignant = $this->user('nonprof');
        $this->actingAs($this->admin);
        $this->patchJson(route('esbtp.lmd.planifications.update', ['ecueId' => $this->ecue->id]), [
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
            'enseignant_principal_id' => $nonEnseignant->id,
            'enseignants_secondaires' => [],
        ])->assertUnprocessable();

        $this->patchJson(route('esbtp.lmd.ues.update-responsable', ['ueId' => $this->ue->id]), [
            'responsable_ue_id' => $this->autreEnseignant->id,
        ])->assertOk()->assertJsonPath('ue.responsable_name', $this->autreEnseignant->name);

        $partial = $this->getJson(route('esbtp.lmd.planning.partial', [
            'parcours_id' => $this->classe->parcours_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
        ]));
        $partial->assertOk();
        $this->assertStringContainsString($this->autreEnseignant->name, $partial->json('listing'));
    }

    private function poolQuery(): array
    {
        return [
            'matiere_id' => $this->ecue->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_id' => $this->classe->niveau_etude_id,
            'semestre' => 1,
            'annee_universitaire_id' => $this->annee->id,
        ];
    }

    private function evaluationData(array $override = []): array
    {
        return array_merge([
            'titre' => 'Examen Algèbre', 'classe_id' => $this->classe->id, 'matiere_id' => $this->ecue->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'type' => ESBTPEvaluation::TYPE_EXAMEN,
            'date_evaluation' => '2026-03-02 08:00:00', 'duree_minutes' => 120, 'coefficient' => 1, 'bareme' => 20,
            'status' => ESBTPEvaluation::STATUS_SCHEDULED, 'is_published' => true, 'created_by' => $this->admin->id,
        ], $override);
    }

    private function user(string $prefix): User
    {
        return User::withoutEvents(fn () => User::factory()->create([
            'name' => ucfirst($prefix).' Test',
            'username' => $prefix.'_'.Str::lower(Str::random(8)),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]));
    }
}
