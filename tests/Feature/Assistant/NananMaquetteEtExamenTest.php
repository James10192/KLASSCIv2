<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Lmd\ModifierMaquetteLmd;
use App\Domain\Assistant\Actions\Lmd\RetirerEcueLmd;
use App\Domain\Assistant\Actions\Notes\RequalifierEnExamen;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Notes\RegularisationDeNotesLmd;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
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
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Séance d'entraînement : ce qui a été fait à la main pour ESBTP Abidjan en
 * octobre 2026, que Nanan et l'écran savent désormais refaire.
 *
 *  - aligner la maquette L1 S1 sur le bulletin officiel (codes, crédits, ordre,
 *    élément manquant) ;
 *  - retirer un élément de sa dernière maquette sans le verser au BTS ;
 *  - requalifier en examen des notes saisies en « Régularisation ».
 */
class NananMaquetteEtExamenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPClasse $classe;

    private ESBTPEtudiant $awa;

    private ESBTPLMDParcours $bu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['lmd.notes.manage', 'lmd.notes.view', 'evaluations.edit', 'identity.teach', 'identity.coordinate',
            'lmd.structure.manage', 'lmd.structure.delete', 'lmd.structure.view'] as $p) {
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
                'code' => 'BST4', 'name' => 'Sciences et techniques de base', 'credit' => 2, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [['code' => 'BST413', 'name' => 'Initiation à la topographie', 'credit_ecue' => 2]],
            ]],
        ]);
        $this->bu = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'L1A Batiment', 'parcours_id' => $this->bu->id, 'filiere_id' => $this->bu->filiere_id,
            'annee_universitaire_id' => $this->annee->id,
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id'),
        ]);
        $this->awa = ESBTPEtudiant::factory()->create(['nom' => 'BAMBA', 'prenoms' => 'Awa', 'matricule' => 'FL25-001']);
        ESBTPInscription::factory()->create(['etudiant_id' => $this->awa->id, 'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);

        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
    }

    private function valider(array $r): array
    {
        $this->assertSame('approbation', $r['widget']['kind'] ?? null, json_encode($r, JSON_UNESCAPED_UNICODE));

        return $this->actingAs($this->admin)->postJson($r['widget']['valider_url'], ['jeton' => $r['widget']['jeton']])->json();
    }

    private function manques(array $r): string
    {
        $this->assertArrayNotHasKey('widget', $r, 'une proposition incomplète ne doit pas être présentée');

        return implode(' ', $r['manques'] ?? []);
    }

    private function matiere(string $code): ESBTPMatiere
    {
        return ESBTPMatiere::where('code', $code)->firstOrFail();
    }

    private function regulariser(array $notes): void
    {
        app(RegularisationDeNotesLmd::class)->appliquer([
            'etudiant_id' => $this->awa->id, 'classe_id' => $this->classe->id, 'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1', 'date_regularisation' => '2026-04-30', 'motif' => 'Relevé officiel de la session d\'avril',
            'notes' => $notes,
        ], false, $this->admin->id);
    }

    // ── Requalifier en examen ─────────────────────────────────────────────

    public function test_les_regularisations_deviennent_des_examens_apres_valider_sans_toucher_aux_notes(): void
    {
        $this->regulariser([['matiere_id' => $this->matiere('BMI11')->id, 'note' => 12.5], ['matiere_id' => $this->matiere('BMI12')->id, 'note' => 9]]);

        $r = app(RequalifierEnExamen::class)->executeAuthorized(['classe' => 'L1A Batiment'], $this->admin);
        $this->assertStringContainsString('Examen SEMESTRE1 — Algèbre', json_encode($r, JSON_UNESCAPED_UNICODE));
        $this->assertSame(['controle'], ESBTPEvaluation::distinct()->pluck('type')->all(), 'rien avant Valider');

        $this->assertSame('executee', $this->valider($r)['statut']);

        $this->assertSame(['examen'], ESBTPEvaluation::distinct()->pluck('type')->all());
        $this->assertSame(['Examen SEMESTRE1 — Algèbre', 'Examen SEMESTRE1 — Analyse'], ESBTPEvaluation::orderBy('titre')->pluck('titre')->all());
        $this->assertSame(['examen'], ESBTPNote::distinct()->pluck('type_evaluation')->all());
        $this->assertEqualsCanonicalizing([12.5, 9.0], ESBTPNote::pluck('note')->map(fn ($n) => (float) $n)->all(), 'aucune note ne bouge');

        $deja = app(RequalifierEnExamen::class)->executeAuthorized(['classe' => 'L1A Batiment'], $this->admin);
        $this->assertTrue($deja['sans_objet'] ?? false, json_encode($deja, JSON_UNESCAPED_UNICODE));
    }

    public function test_un_examen_deja_saisi_pour_le_meme_element_bloque_la_requalification(): void
    {
        $this->regulariser([['matiere_id' => $this->matiere('BMI11')->id, 'note' => 12.5]]);
        ESBTPEvaluation::create(['titre' => 'Examen SEMESTRE1 — Algèbre', 'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere('BMI11')->id, 'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1',
            'type' => 'examen', 'date_evaluation' => '2026-04-30 08:00:00', 'duree_minutes' => 60, 'coefficient' => 1, 'bareme' => 20,
            'status' => ESBTPEvaluation::STATUS_COMPLETED, 'created_by' => $this->admin->id]);

        $manques = $this->manques(app(RequalifierEnExamen::class)->executeAuthorized(['classe' => 'L1A Batiment'], $this->admin));

        $this->assertStringContainsString('existe déjà', $manques);
        $this->assertSame(1, ESBTPEvaluation::where('type', 'controle')->count());
    }

    public function test_l_ecran_requalifie_en_deux_temps_et_refuse_un_enseignant(): void
    {
        $this->regulariser([['matiere_id' => $this->matiere('BMI11')->id, 'note' => 14]]);
        $url = route('esbtp.lmd.notes.requalifier-examen', $this->classe);

        $this->actingAs($this->admin)->getJson(route('esbtp.lmd.notes.requalification', $this->classe))
            ->assertOk()->assertJsonPath('lignes.0.nouveau_titre', 'Examen SEMESTRE1 — Algèbre');
        $this->actingAs($this->admin)->postJson($url, ['dry_run' => true])->assertOk()->assertJsonPath('dry_run', true);
        $this->assertSame('controle', ESBTPEvaluation::value('type'), 'l\'aperçu n\'écrit rien');

        $enseignant = $this->utilisateur();
        $enseignant->givePermissionTo(['lmd.notes.manage', 'evaluations.edit', 'identity.teach']);
        $this->actingAs($enseignant)->postJson($url, ['dry_run' => false])->assertForbidden();

        $this->actingAs($this->admin)->postJson($url, ['dry_run' => false])->assertOk()
            ->assertJsonPath('message', '1 évaluation(s) requalifiée(s) en examen, 1 note(s) inchangée(s).');
        $this->assertSame('examen', ESBTPEvaluation::value('type'));
    }

    // ── Aligner la maquette ───────────────────────────────────────────────

    public function test_la_maquette_s_aligne_sur_le_bulletin_apres_valider(): void
    {
        $args = ['parcours' => 'BU',
            'ues' => [['ue' => 'BMI1', 'code' => 'BMIB1', 'rang' => 2], ['ue' => 'BST4', 'credit' => 3, 'rang' => 1]],
            'elements' => [
                ['ue' => 'BMI1', 'element' => 'Analyse', 'ordre' => 1],
                ['ue' => 'BST4', 'element' => 'BST413', 'credit' => 1],
                ['ue' => 'BST4', 'code' => 'BST414', 'intitule' => 'Initiation au génie civil', 'credit' => 1, 'ordre' => 2],
            ]];
        $r = app(ModifierMaquetteLmd::class)->executeAuthorized($args, $this->admin);
        $this->assertArrayHasKey('widget', $r, json_encode($r, JSON_UNESCAPED_UNICODE));
        $this->assertNull(ESBTPUniteEnseignement::where('code', 'BMIB1')->first(), 'rien avant Valider');

        $this->assertSame('executee', $this->valider($r)['statut']);

        $math = ESBTPUniteEnseignement::where('code', 'BMIB1')->firstOrFail();
        $st = ESBTPUniteEnseignement::where('code', 'BST4')->firstOrFail();
        $this->assertSame(3, (int) $st->credit);
        $rang = fn ($ue) => (int) DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $this->bu->id])->value('ordre');
        $this->assertSame([1, 2], [$rang($st), $rang($math)]);
        $pivot = fn ($ue, $code) => DB::table('esbtp_ue_matiere')->where(['unite_enseignement_id' => $ue->id, 'matiere_id' => $this->matiere($code)->id])->first();
        $this->assertSame(1, (int) $pivot($math, 'BMI12')->ordre_bulletin);
        $this->assertSame(2, (int) $pivot($math, 'BMI12')->credit_ecue, 'un champ non demandé garde sa valeur');
        $this->assertSame(1, (int) $pivot($st, 'BST413')->credit_ecue);
        $this->assertSame('Initiation au génie civil', $this->matiere('BST414')->name);
        $this->assertSame($st->id, (int) $this->matiere('BST414')->unite_enseignement_id, 'un élément ajouté reste LMD');
    }

    public function test_des_credits_d_elements_au_dela_de_l_ue_sont_une_question(): void
    {
        $manques = $this->manques(app(ModifierMaquetteLmd::class)->executeAuthorized(['parcours' => 'BU', 'elements' => [
            ['ue' => 'BST4', 'code' => 'BST414', 'intitule' => 'Initiation au génie civil', 'credit' => 1],
        ]], $this->admin));

        $this->assertStringContainsString('dépasseraient ceux de l\'UE (3 > 2)', $manques);
        $this->assertFalse(ESBTPMatiere::where('code', 'BST414')->exists());
    }

    public function test_deux_hausses_qui_depassent_ensemble_sont_une_question(): void
    {
        $manques = $this->manques(app(ModifierMaquetteLmd::class)->executeAuthorized(['parcours' => 'BU', 'elements' => [
            ['ue' => 'BMI1', 'element' => 'BMI11', 'credit' => 3],
            ['ue' => 'BMI1', 'element' => 'BMI12', 'credit' => 3],
        ]], $this->admin));

        $this->assertStringContainsString('dépasseraient ceux de l\'UE (6 > 4)', $manques, 'chaque hausse passe seule, pas les deux');
    }

    /** BU et TP partagent l'UE : nommer BU ne doit rien changer à TP en silence. */
    private function partagerAvecTp(string $codeUe): ESBTPLMDParcours
    {
        $tp = ESBTPLMDParcours::create(['name' => 'Travaux publics', 'code' => 'TP', 'mention_id' => $this->bu->mention_id, 'filiere_id' => $this->bu->filiere_id]);
        DB::table('esbtp_lmd_parcours_ue')->insert(['parcours_id' => $tp->id, 'unite_enseignement_id' => ESBTPUniteEnseignement::where('code', $codeUe)->value('id'),
            'semestre' => 1, 'ordre' => 0, 'is_optional' => false, 'created_at' => now(), 'updated_at' => now()]);

        return $tp;
    }

    public function test_le_credit_d_une_ue_partagee_se_pose_sur_la_maquette_du_parcours_nomme(): void
    {
        $tp = $this->partagerAvecTp('BST4');
        $st = ESBTPUniteEnseignement::where('code', 'BST4')->firstOrFail();

        $this->valider(app(ModifierMaquetteLmd::class)->executeAuthorized(['parcours' => 'BU', 'ues' => [['ue' => 'BST4', 'credit' => 3]]], $this->admin));

        $credit = fn ($p) => DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $st->id, 'parcours_id' => $p])->value('credit');
        $this->assertSame(3, (int) $credit($this->bu->id));
        $this->assertNull($credit($tp->id), 'TP garde le crédit de la fiche');
        $this->assertSame(2, (int) $st->fresh()->credit);
    }

    public function test_renommer_une_ue_partagee_le_dit(): void
    {
        $this->partagerAvecTp('BMI1');

        $r = app(ModifierMaquetteLmd::class)->executeAuthorized(['parcours' => 'BU', 'ues' => [['ue' => 'BMI1', 'code' => 'BMIB1']]], $this->admin);

        $this->assertStringContainsString('L\'UE BMI1 est partagée : son code change aussi pour TP', json_encode($r, JSON_UNESCAPED_UNICODE));
    }

    public function test_un_element_commun_est_controle_dans_chaque_maquette_qui_le_lit(): void
    {
        $tp = $this->partagerAvecTp('BST4');
        $st = ESBTPUniteEnseignement::where('code', 'BST4')->firstOrFail();
        DB::table('esbtp_ue_matiere')->where('matiere_id', $this->matiere('BST413')->id)->update(['parcours_id' => 0, 'credit_ecue' => 1]);
        // TP n'a que 1 crédit pour cette UE ; BU garde les 2 de la fiche.
        DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $st->id, 'parcours_id' => $tp->id])->update(['credit' => 1]);

        $manques = $this->manques(app(ModifierMaquetteLmd::class)->executeAuthorized(['parcours' => 'BU', 'elements' => [
            ['ue' => 'BST4', 'element' => 'BST413', 'credit' => 2],
        ]], $this->admin));

        $this->assertStringContainsString('BST4 (maquette TP) dépasseraient ceux de l\'UE (2 > 1)', $manques);
    }

    public function test_un_element_commun_ne_se_retire_pas_d_un_seul_parcours(): void
    {
        $this->partagerAvecTp('BST4');
        DB::table('esbtp_ue_matiere')->where('matiere_id', $this->matiere('BST413')->id)->update(['parcours_id' => 0]);

        $manques = $this->manques(app(RetirerEcueLmd::class)->executeAuthorized(['ue' => 'BST4', 'element' => 'BST413', 'parcours' => 'BU', 'devenir' => 'archiver'], $this->admin));

        $this->assertStringContainsString('commun à tous les parcours de l\'UE BST4 (aussi TP)', $manques);
        $this->assertTrue(DB::table('esbtp_ue_matiere')->where('matiere_id', $this->matiere('BST413')->id)->exists());
    }

    // ── Retirer un élément ────────────────────────────────────────────────

    public function test_retirer_un_element_de_sa_derniere_maquette_demande_ce_qu_il_devient(): void
    {
        $args = ['ue' => 'BST4', 'element' => 'BST413'];

        $manques = $this->manques(app(RetirerEcueLmd::class)->executeAuthorized($args, $this->admin));
        $this->assertStringContainsString('Que doit-il devenir', $manques);
        $this->assertStringContainsString('archiver', $manques);

        $this->assertSame('executee', $this->valider(app(RetirerEcueLmd::class)->executeAuthorized($args + ['devenir' => 'archiver'], $this->admin))['statut']);

        $topo = $this->matiere('BST413');
        $this->assertFalse((bool) $topo->is_active);
        $this->assertNotNull($topo->unite_enseignement_id, 'archivé dans le LMD, jamais versé au BTS');
        $this->assertFalse(DB::table('esbtp_ue_matiere')->where('matiere_id', $topo->id)->exists());
    }

    public function test_supprimer_un_element_qui_a_servi_n_est_pas_propose(): void
    {
        $this->regulariser([['matiere_id' => $this->matiere('BST413')->id, 'note' => 10]]);

        $manques = $this->manques(app(RetirerEcueLmd::class)->executeAuthorized(['ue' => 'BST4', 'element' => 'BST413', 'devenir' => 'supprimer'], $this->admin));

        $this->assertStringContainsString('Impossible : il porte déjà', $manques);
        $this->assertTrue((bool) $this->matiere('BST413')->is_active);
    }

    public function test_le_rang_d_une_ue_se_regle_depuis_lier_a_des_parcours(): void
    {
        $ue = ESBTPUniteEnseignement::where('code', 'BST4')->firstOrFail();

        $this->actingAs($this->admin)->getJson(route('esbtp.lmd.ue.parcours-disponibles', $ue))->assertOk()->assertJsonPath('lies.0.ordre', 0);
        $this->actingAs($this->admin)->postJson(route('esbtp.lmd.ue.sync-parcours', $ue), [
            'parcours' => [['id' => $this->bu->id, 'semestres' => [1], 'ordre' => 4]],
        ])->assertOk();

        $this->assertSame(4, (int) DB::table('esbtp_lmd_parcours_ue')->where(['unite_enseignement_id' => $ue->id, 'parcours_id' => $this->bu->id])->value('ordre'));
        $this->actingAs($this->admin)->postJson(route('esbtp.lmd.ue.sync-parcours', $ue), [
            'parcours' => [['id' => $this->bu->id, 'semestres' => [1]]],
        ])->assertOk();
        $this->assertSame(4, (int) DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->value('ordre'), 'sans rang envoyé, le rang est gardé');
    }

    // ── La vraie boucle ───────────────────────────────────────────────────

    public function test_seance_d_entrainement_requalification_et_maquette(): void
    {
        $this->regulariser([['matiere_id' => $this->matiere('BMI11')->id, 'note' => 12.5]]);
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 4, 'assistant.limites.budget_tokens' => 0]);
        $faux->scripts['m'] = [
            // Une question d'abord (le devenir de l'élément), puis une proposition,
            // qui clôt le tour : la personne la lit et valide.
            FauxFournisseur::outil('t1', 'proposer_retrait_ecue_lmd', ['ue' => 'BST4', 'element' => 'BST413']),
            FauxFournisseur::outil('t2', 'proposer_requalification_examen', ['classe' => 'L1A Batiment']),
            FauxFournisseur::texte('Validez la requalification. Pour la topographie : la supprimer, l\'archiver, ou en faire une matière BTS ?'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        $this->assertStringContainsString('proposer_requalification_examen', $systeme);
        $this->assertStringContainsString('proposer_modification_maquette_lmd', $systeme);
        $this->assertStringContainsString('ne choisis jamais', $systeme);

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Les régularisations de L1A Batiment étaient les examens. Et retire la topographie de BST4.']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        // Une proposition incomplète revient au modèle comme une question, pas comme un appel abouti.
        $this->assertSame(['proposer_requalification_examen'], array_column($r->appels, 'tool'));
        $relaye = json_encode($faux->recues[1]['requete']->messages, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Que doit-il devenir', $relaye);
        $this->assertStringContainsString('archiver', $relaye);
        $this->assertSame('controle', ESBTPEvaluation::value('type'));
        $this->assertTrue((bool) $this->matiere('BST413')->is_active, 'la question du devenir est posée, rien n\'est retiré');
        $this->assertSame(1, DB::table('chatbot_actions_log')->where('action_type', 'requalification_examen')->where('status', 'proposed')->count());
    }
}
