<?php

namespace Tests\Feature\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\Chatbot\Tools\SuiviDesNotesTool;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Le suivi des notes d'une classe LMD lit SA maquette, semestre par semestre.
 *
 * Avant, une classe LMD retombait sur les matières « historiques » : celles qui
 * avaient déjà une note. Un élément jamais noté n'existait donc nulle part, et
 * le suivi annonçait « tout est saisi » à ESBTP Abidjan alors que trois
 * éléments du S1 n'avaient aucune note (octobre 2026).
 */
class SuiviDesNotesLmdTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPEtudiant $awa;

    private ESBTPEtudiant $koffi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['module.academic_pilotage.access', 'academic_health.view', 'academic_health.view_own', 'academic_pilotage.view_all', 'lmd.notes.view', 'module.lmd.access'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'name' => '2025-2026']);
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Batiment et Urbanisme', 'code' => 'BU', 'credits_licence' => 180],
            'filiere' => ['name' => 'Batiment', 'code' => 'FBU'],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'BMI1', 'name' => 'Mathematiques', 'credit' => 4, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [
                    ['code' => 'BMI11', 'name' => 'Algèbre', 'credit_ecue' => 2],
                    ['code' => 'BMI12', 'name' => 'Analyse', 'credit_ecue' => 2],
                ],
            ], [
                'code' => 'BDR1', 'name' => 'Droit', 'credit' => 2, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [['code' => 'BDR11', 'name' => 'Droit civil', 'credit_ecue' => 2]],
            ], [
                'code' => 'BPH2', 'name' => 'Physique', 'credit' => 3, 'niveau_year' => 1, 'semestre' => 2,
                'ecues' => [['code' => 'BPH21', 'name' => 'Thermodynamique', 'credit_ecue' => 3]],
            ]],
        ]);
        $bu = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'L1A Batiment', 'parcours_id' => $bu->id, 'filiere_id' => $bu->filiere_id,
            'systeme_academique' => 'LMD', 'is_active' => true,
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id'),
        ]);
        $this->awa = $this->inscrire('BAMBA', 'Awa', 'FL25-001');
        $this->koffi = $this->inscrire('KOFFI', 'Yao', 'FL25-002');

        $this->admin = User::withoutEvents(fn () => User::factory()->create([
            'username' => 'u_'.Str::lower(Str::random(8)), 'must_change_password' => false, 'password_changed_at' => now(),
        ]));
        $this->admin->givePermissionTo(['module.academic_pilotage.access', 'academic_health.view', 'academic_pilotage.view_all', 'lmd.notes.view', 'module.lmd.access']);

        // Algèbre : contrôle et examen, complet. Analyse : examen seul, Koffi manque.
        // Droit civil : rien du tout.
        $this->noter('BMI11', ESBTPEvaluation::TYPE_EXAMEN, [$this->awa, $this->koffi]);
        $this->noter('BMI11', 'devoir', [$this->awa, $this->koffi]);
        $this->noter('BMI12', ESBTPEvaluation::TYPE_EXAMEN, [$this->awa]);
    }

    private function inscrire(string $nom, string $prenoms, string $matricule): ESBTPEtudiant
    {
        $e = ESBTPEtudiant::factory()->create(['nom' => $nom, 'prenoms' => $prenoms, 'matricule' => $matricule]);
        ESBTPInscription::factory()->create(['etudiant_id' => $e->id, 'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);

        return $e;
    }

    private function noter(string $code, string $type, array $eleves): void
    {
        $matiere = ESBTPMatiere::where('code', $code)->firstOrFail();
        $evaluation = ESBTPEvaluation::create(['titre' => $type.' '.$code, 'classe_id' => $this->classe->id,
            'matiere_id' => $matiere->id, 'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
            'type' => $type, 'date_evaluation' => '2026-03-02 08:00:00', 'duree_minutes' => 60, 'coefficient' => 1, 'bareme' => 20,
            'status' => ESBTPEvaluation::STATUS_COMPLETED, 'is_published' => true]);
        foreach ($eleves as $eleve) {
            ESBTPNote::create(['evaluation_id' => $evaluation->id, 'etudiant_id' => $eleve->id, 'matiere_id' => $matiere->id,
                'classe_id' => $this->classe->id, 'note' => 12, 'is_absent' => false]);
        }
    }

    /** @return array<string, array<string, mixed>> */
    private function parCode(array $payload): array
    {
        return collect($payload['subjects'])->keyBy('code')->all();
    }

    public function test_le_suivi_lit_la_maquette_du_semestre_avec_les_elements_jamais_notes(): void
    {
        $payload = app(AcademicNoteCoverageService::class)->summarize($this->annee->id, 'semestre1', null, $this->classe->id);
        $elements = $this->parCode($payload);

        // Le S2 (Thermodynamique) n'entre pas au S1 ; Droit civil y entre, sans aucune note.
        $this->assertSame(['BDR11', 'BMI11', 'BMI12'], collect(array_keys($elements))->sort()->values()->all());
        $this->assertSame('non_evaluee', $elements['BDR11']['statut']);
        $this->assertSame('complete', $elements['BMI11']['statut']);
        $this->assertSame('partielle', $elements['BMI12']['statut']);
        $this->assertSame(1, $elements['BMI12']['missing_count']);

        $this->assertSame('cc_examen', $elements['BMI11']['nature']);
        $this->assertSame('examen', $elements['BMI12']['nature']);
        $this->assertNull($elements['BDR11']['nature']);
        $this->assertStringContainsString('Mathematiques', (string) $elements['BMI11']['groupe']);

        $s2 = $this->parCode(app(AcademicNoteCoverageService::class)->summarize($this->annee->id, 'semestre2', null, $this->classe->id));
        $this->assertSame(['BPH21'], array_keys($s2));
    }

    public function test_l_adresse_rend_les_semestres_de_la_classe_et_ouvre_la_saisie_lmd(): void
    {
        $reponse = $this->actingAs($this->admin)->getJson(route('esbtp.pilotage-academique.classes.couverture', [
            'classe' => $this->classe->id, 'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
        ]))->assertOk();

        $this->assertSame(['annuel', 'semestre1', 'semestre2'], array_column($reponse->json('periodes'), 'valeur'));
        $algebre = collect($reponse->json('subjects'))->firstWhere('code', 'BMI11');
        $this->assertStringContainsString('/esbtp/lmd/notes', $algebre['saisie_url']);
        $this->assertStringContainsString('ecue='.$algebre['id'], $algebre['saisie_url']);
        $this->assertStringContainsString('annee_universitaire_id='.$this->annee->id, $algebre['saisie_url']);
    }

    public function test_les_deux_ecrans_lmd_portent_le_panneau(): void
    {
        $vue = fn (string $chemin) => file_get_contents(resource_path('views/'.$chemin.'.blade.php'));
        foreach (['esbtp/lmd/notes/index', 'esbtp/lmd/bulletins/select'] as $page) {
            $this->assertStringContainsString("esbtp.partials._couverture-notes'", $vue($page), $page);
        }
        $this->assertStringContainsString("esbtp.lmd.notes.partials._suivi-script'", $vue('esbtp/lmd/notes/index'));
        foreach (['esbtp/lmd/notes/partials/_suivi-script', 'esbtp/lmd/bulletins/select'] as $page) {
            $this->assertStringContainsString('couverture:contexte', $vue($page), $page);
            $this->assertStringContainsString('couverture:periode-change', $vue($page), $page);
        }
        // Une note enregistrée dans la fenêtre fait recalculer le panneau.
        $this->assertSame(2, substr_count($vue('esbtp/lmd/notes/index'), 'lmdSuiviApresSauvegarde();'));
        $this->assertStringContainsString('couverture:invalider', $vue('esbtp/lmd/notes/partials/_suivi-script'));
    }

    public function test_un_semestre_sans_unite_dans_la_maquette_se_dit_referentiel_absent(): void
    {
        ESBTPUniteEnseignement::where('code', 'BPH2')->update(['is_active' => false]);

        $s2 = app(AcademicNoteCoverageService::class)->summarize($this->annee->id, 'semestre2', null, $this->classe->id);

        $this->assertSame('referentiel_absent', $s2['summary']['state']);
    }

    public function test_la_grille_des_notes_propose_les_elements_du_suivi(): void
    {
        $this->admin->assignRole('superAdmin');
        $grille = $this->actingAs($this->admin)->getJson(route('esbtp.lmd.notes.classe-data', $this->classe->id))->assertOk();
        $suivi = collect(array_merge(
            app(AcademicNoteCoverageService::class)->summarize($this->annee->id, 'semestre1', null, $this->classe->id)['subjects'],
            app(AcademicNoteCoverageService::class)->summarize($this->annee->id, 'semestre2', null, $this->classe->id)['subjects'],
        ))->pluck('id')->sort()->values()->all();

        $this->assertSame($suivi, collect($grille->json('matieres'))->pluck('id')->sort()->values()->all());
        $this->assertSame([1, 1, 2], collect($grille->json('ues'))->pluck('semestre')->sort()->values()->all());
    }

    public function test_nanan_dit_ce_qui_manque_et_ne_nomme_les_eleves_qu_avec_le_droit(): void
    {
        $r = app(SuiviDesNotesTool::class)->executeAuthorized(['classe' => 'L1A', 'semestre' => 1], $this->admin);
        $constat = $r['diagnostic']['classes'][0];

        $this->assertSame(['BDR11 Droit civil'], $constat['sans_aucune_note']);
        $this->assertSame('BMI12 Analyse', $constat['partiels'][0]['element']);
        $this->assertSame(['KOFFI Yao'], $constat['partiels'][0]['eleves']);
        $this->assertContains('BMI12 Analyse : examen seul', $constat['notes_en']);
        $this->assertTrue($r['diagnostic']['noms_des_eleves_visibles']);

        // Un semestre que la classe n'a pas est dit, pas inventé.
        $s3 = app(SuiviDesNotesTool::class)->executeAuthorized(['classe_id' => $this->classe->id, 'semestre' => 3], $this->admin);
        $this->assertStringContainsString('pas de semestre 3', $s3['diagnostic']['classes'][0]['refus']);

        // Sans le droit global, les élèves ne sont pas nommés.
        $enseignant = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $enseignant->givePermissionTo(['academic_health.view_own', 'academic_pilotage.view_all']);
        $r = app(SuiviDesNotesTool::class)->executeAuthorized(['classe' => 'L1A', 'semestre' => 1], $enseignant);
        $this->assertSame([], $r['diagnostic']['classes'][0]['partiels'][0]['eleves']);
        $this->assertFalse($r['diagnostic']['noms_des_eleves_visibles']);
    }

    public function test_seance_d_entrainement_qu_est_ce_qui_manque_au_s1(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 3, 'assistant.limites.budget_tokens' => 0]);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'suivi_des_notes', ['classe' => 'L1A', 'semestre' => 1]),
            FauxFournisseur::texte('Au S1, Droit civil n\'a aucune note ; Analyse manque pour KOFFI Yao.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        $this->assertStringContainsString('suivi_des_notes', $systeme);

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Qu\'est-ce qui manque au S1 en L1A ?']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['suivi_des_notes'], array_column($r->appels, 'tool'));
        $relaye = json_encode($faux->recues[1]['requete']->messages, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('BDR11 Droit civil', $relaye);
        $this->assertStringContainsString('KOFFI Yao', $relaye);
    }
    public function test_la_grille_lmd_peut_rester_sur_une_ancienne_annee_apres_la_bascule_de_is_current(): void
    {
        $this->admin->assignRole('superAdmin');
        $this->annee->update(['is_current' => false]);

        $courante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2026-2027',
            'is_current' => true,
        ]);

        $nouveau = ESBTPEtudiant::factory()->create([
            'nom' => 'NOUVEAU',
            'prenoms' => 'Etudiant',
            'matricule' => 'FL26-001',
        ]);
        ESBTPInscription::factory()->create([
            'etudiant_id' => $nouveau->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $courante->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        $matiere = ESBTPMatiere::where('code', 'BMI11')->firstOrFail();
        $evaluationCourante = ESBTPEvaluation::create([
            'titre' => 'Devoir 2026-2027',
            'classe_id' => $this->classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $courante->id,
            'periode' => 'semestre1',
            'type' => 'devoir',
            'date_evaluation' => '2026-10-02 08:00:00',
            'duree_minutes' => 60,
            'coefficient' => 1,
            'bareme' => 20,
            'status' => ESBTPEvaluation::STATUS_COMPLETED,
            'is_published' => true,
        ]);

        $historique = $this->actingAs($this->admin)->getJson(route('esbtp.lmd.notes.classe-data', [
            'classe' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
        ]))->assertOk();

        $idsHistoriques = collect($historique->json('etudiants'))->pluck('id');
        $this->assertTrue($idsHistoriques->contains($this->awa->id));
        $this->assertTrue($idsHistoriques->contains($this->koffi->id));
        $this->assertFalse($idsHistoriques->contains($nouveau->id));
        $this->assertFalse(collect($historique->json('evaluations'))->pluck('id')->contains($evaluationCourante->id));

        $fallback = $this->actingAs($this->admin)->getJson(route('esbtp.lmd.notes.classe-data', [
            'classe' => $this->classe->id,
        ]))->assertOk();

        $this->assertTrue(collect($fallback->json('etudiants'))->pluck('id')->contains($nouveau->id));
        $this->assertTrue(collect($fallback->json('evaluations'))->pluck('id')->contains($evaluationCourante->id));
        $this->assertFalse(collect($fallback->json('evaluations'))->pluck('id')->contains(
            ESBTPEvaluation::where('annee_universitaire_id', $this->annee->id)->value('id')
        ));
    }


}
