<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Notes\SaisirReleveLmd;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
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
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
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
 * Séance d'entraînement : saisir le relevé d'une classe LMD d'une année passée,
 * comme pour les L1 d'ESBTP Abidjan en octobre 2026. Rien n'est écrit avant
 * « Valider » ; une colonne, un zéro ou un étudiant douteux est une question.
 */
class NananReleveLmdTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ESBTPAnneeUniversitaire $passee;

    private ESBTPClasse $classe;

    private ESBTPEtudiant $awa;

    private ESBTPEtudiant $koffi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['notes.edit', 'evaluations.create', 'notes.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->passee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false, 'name' => '2025-2026']);
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'name' => '2026-2027']);
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Batiment et Urbanisme', 'code' => 'BU', 'credits_licence' => 180],
            'filiere' => ['name' => 'Batiment', 'code' => 'FBU'],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'BMIB1', 'name' => 'Mathematiques', 'credit' => 4, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [
                    ['code' => 'BMIB111', 'name' => 'Algèbre', 'credit_ecue' => 2],
                    ['code' => 'BMIB112', 'name' => 'Analyse', 'credit_ecue' => 2],
                ],
            ], [
                'code' => 'BLSH5', 'name' => 'Langues', 'credit' => 2, 'niveau_year' => 1, 'semestre' => 1,
                'ecues' => [['code' => 'BLSH511', 'name' => 'Anglais', 'credit_ecue' => 2]],
            ]],
        ]);
        // Comme sur ESBTP Abidjan : éléments rattachés par la seule clé étrangère.
        DB::table('esbtp_ue_matiere')->delete();

        $bu = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->classe = ESBTPClasse::factory()->create([
            'name' => 'L1A Batiment', 'parcours_id' => $bu->id, 'filiere_id' => $bu->filiere_id,
            // Sinon la fabrique crée une année au nom aléatoire, parfois « 2025-2026 » : deux années du même nom.
            'annee_universitaire_id' => $this->passee->id,
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 1)->where('type', 'Licence')->value('id'),
        ]);
        $this->assertSame('LMD', $this->classe->fresh()->systeme_academique);
        $this->awa = $this->inscrire('BAMBA', 'Awa Marie', 'FL25-001');
        $this->koffi = $this->inscrire('KOFFI', 'Jean', 'ML25-002');

        $this->admin = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $this->admin->assignRole('superAdmin');
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function inscrire(string $nom, string $prenoms, string $matricule): ESBTPEtudiant
    {
        $e = ESBTPEtudiant::factory()->create(['nom' => $nom, 'prenoms' => $prenoms, 'matricule' => $matricule]);
        ESBTPInscription::factory()->create(['etudiant_id' => $e->id, 'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->passee->id, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);

        return $e;
    }

    private function args(array $etudiants, array $plus = []): array
    {
        return $plus + ['classe' => 'L1A Batiment', 'annee' => '2025-2026', 'semestre' => 'S1', 'date' => '2026-04-30',
            'motif' => 'Relevé officiel du semestre 1 transmis par la direction des études', 'etudiants' => $etudiants];
    }

    private function proposer(array $args): array
    {
        return app(SaisirReleveLmd::class)->executeAuthorized($args, $this->admin);
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

    public function test_le_releve_n_ecrit_rien_avant_valider_puis_cree_les_evaluations_et_les_notes(): void
    {
        $r = $this->proposer($this->args([
            ['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 12.5], ['element' => 'Anglais', 'note' => 15]]],
            ['etudiant' => 'Jean KOFFI', 'notes' => [['element' => 'BLSH511', 'note' => 9]]],
        ]));
        $this->assertArrayHasKey('widget', $r, json_encode($r, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, ESBTPNote::count(), 'rien avant Valider');
        $this->assertSame(0, ESBTPEvaluation::count());

        $this->assertSame('executee', $this->valider($r)['statut']);

        $this->assertSame(12.5, (float) ESBTPNote::where('etudiant_id', $this->awa->id)->whereHas('matiere', fn ($q) => $q->where('code', 'BMIB111'))->value('note'));
        $this->assertSame(3, ESBTPNote::count());
        $this->assertSame(2, ESBTPEvaluation::where('classe_id', $this->classe->id)->where('annee_universitaire_id', $this->passee->id)
            ->where('periode', 'semestre1')->where('is_published', false)->count(), 'une évaluation par élément, en brouillon, sur l\'année du relevé');
        $this->assertSame(['2025-2026'], ESBTPNote::distinct()->pluck('annee_universitaire')->all(), 'l\'année est écrite en clair sur la note');
        // Le bulletin LMD ne lit que les évaluations terminées : sans cela, ces notes n'y entreraient pas.
        $this->assertSame(0, ESBTPEvaluation::where('status', '!=', ESBTPEvaluation::STATUS_COMPLETED)->count());

        $this->assertStringContainsString('déjà enregistré', $this->manques($this->proposer($this->args([
            ['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 12.5]]],
        ]))));
    }

    public function test_une_colonne_qui_ne_designe_pas_un_seul_element_est_une_question(): void
    {
        $manques = $this->manques($this->proposer($this->args([
            ['etudiant' => 'FL25-001', 'notes' => [['element' => 'Analyse / Algèbre', 'note' => 11], ['element' => 'Initiation au génie civil', 'note' => 10]]],
        ])));

        $this->assertStringContainsString('« Analyse / Algèbre » n\'est pas un élément de la maquette S1', $manques);
        $this->assertStringContainsString('Initiation au génie civil', $manques);
        $this->assertStringContainsString('BMIB112 Analyse', $manques, 'la question liste les éléments possibles');
    }

    public function test_un_zero_attend_la_confirmation_de_la_personne(): void
    {
        $lignes = [['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 0]]]];

        $this->assertStringContainsString('épreuves non composées', $this->manques($this->proposer($this->args($lignes))));
        $this->assertArrayHasKey('widget', $this->proposer($this->args($lignes, ['zeros_confirmes' => true])));
    }

    public function test_un_etudiant_hors_de_la_classe_cette_annee_la_n_est_pas_devine(): void
    {
        ESBTPEtudiant::factory()->create(['nom' => 'BAMBA', 'prenoms' => 'Awa Marie Grâce', 'matricule' => 'XX-9']);

        $manques = $this->manques($this->proposer($this->args([
            ['etudiant' => 'BAMBA Awa', 'notes' => [['element' => 'BMIB111', 'note' => 12]]],
            ['etudiant' => 'YESSOTCHE Grace', 'notes' => [['element' => 'BMIB111', 'note' => 12]]],
        ])));

        $this->assertStringContainsString('BAMBA Awa Marie (FL25-001)', $manques, 'un proche est proposé, jamais retenu');
        $this->assertStringContainsString('« YESSOTCHE Grace » : aucun étudiant', $manques);
        $this->assertStringNotContainsString('XX-9', $manques, 'un étudiant non inscrit dans la classe cette année-là n\'est pas proposé');
    }

    public function test_une_note_saisie_entre_temps_rend_la_proposition_perimee(): void
    {
        $lignes = [['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 14]]]];
        $r = $this->proposer($this->args($lignes));
        $this->valider($this->proposer($this->args([['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 8]]]])));

        $this->assertNotSame('executee', $this->valider($r)['statut'] ?? null);
        $this->assertSame(8.0, (float) ESBTPNote::where('etudiant_id', $this->awa->id)->value('note'));
    }

    public function test_sans_le_droit_de_creer_des_evaluations_l_action_n_est_pas_proposee(): void
    {
        $saisie = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $saisie->givePermissionTo(Permission::findOrCreate('notes.edit', 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $action = app(SaisirReleveLmd::class);
        $this->assertFalse($action->isAvailableFor($saisie));
        $this->assertNotContains($action->name(), array_column(app(CatalogueOutils::class)->schemas($saisie), 'name'));
        $this->assertTrue($action->isAvailableFor($this->admin));
    }

    public function test_une_l2_saisit_son_s3_et_n_a_pas_de_s1(): void
    {
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Batiment et Urbanisme', 'code' => 'BU', 'credits_licence' => 180],
            'filiere' => ['name' => 'Batiment', 'code' => 'FBU'],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]],
            'ues' => [['code' => 'BHYD3', 'name' => 'Hydraulique', 'credit' => 3, 'niveau_year' => 2, 'semestre' => 3,
                'ecues' => [['code' => 'BHYD311', 'name' => 'Hydraulique générale', 'credit_ecue' => 3]]]],
        ]);
        DB::table('esbtp_ue_matiere')->delete();
        $this->classe->update([
            'name' => 'L2A Batiment',
            'niveau_etude_id' => ESBTPNiveauEtude::where('year', 2)->where('type', 'Licence')->value('id'),
        ]);
        $lignes = [['etudiant' => 'FL25-001', 'notes' => [['element' => 'BHYD311', 'note' => 11]]]];

        $this->assertStringContainsString('S3 ou S4', $this->manques($this->proposer($this->args($lignes, ['classe' => 'L2A Batiment', 'semestre' => 'S1']))));

        $this->valider($this->proposer($this->args($lignes, ['classe' => 'L2A Batiment', 'semestre' => 'S3'])));
        $this->assertSame(['semestre3'], ESBTPEvaluation::pluck('periode')->all(), 'la période est celle que lit le bulletin LMD de L2');
    }

    public function test_plus_de_quarante_notes_pour_un_etudiant_est_une_question(): void
    {
        $notes = array_fill(0, 41, ['element' => 'BMIB111', 'note' => 10]);

        $this->assertStringContainsString('41 notes', $this->manques($this->proposer($this->args([['etudiant' => 'FL25-001', 'notes' => $notes]]))));
    }

    public function test_une_colonne_qui_nomme_une_ue_dit_laquelle_et_ses_elements(): void
    {
        $manques = $this->manques($this->proposer($this->args([
            ['etudiant' => 'FL25-001', 'notes' => [['element' => 'Mathematiques', 'note' => 11]]],
        ])));

        $this->assertStringContainsString('« Mathematiques » est une UE (BMIB1), pas un élément : elle compte BMIB111 Algèbre, BMIB112 Analyse', $manques);
    }

    /**
     * Constat du 2 octobre sur presentation : Nanan n'a posé que la question du 0,
     * la colonne « Béton armé » (une UE) est passée sous silence. L'outil rend
     * TOUTES les questions d'un relevé en une fois, pour qu'aucune ne se perde.
     */
    public function test_toutes_les_questions_d_un_releve_sont_rendues_ensemble(): void
    {
        $r = $this->proposer($this->args([
            ['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 0], ['element' => 'Mathematiques', 'note' => 11]]],
            ['etudiant' => 'YESSOTCHE Grace', 'notes' => [['element' => 'BMIB111', 'note' => 12]]],
        ]));

        $manques = $this->manques($r);
        $this->assertStringContainsString('est une UE (BMIB1)', $manques);
        $this->assertStringContainsString('« YESSOTCHE Grace » : aucun étudiant', $manques);
        $this->assertStringContainsString('épreuves non composées', $manques);
    }

    public function test_une_date_future_est_une_question(): void
    {
        $this->assertStringContainsString('dans le futur', $this->manques($this->proposer($this->args(
            [['etudiant' => 'FL25-001', 'notes' => [['element' => 'BMIB111', 'note' => 12]]]],
            ['date' => now()->addDay()->toDateString()],
        ))));
    }

    public function test_seance_d_entrainement_releve(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 3, 'assistant.limites.budget_tokens' => 0]);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'proposer_releve_notes_lmd', $this->args([
                ['etudiant' => 'BAMBA Awa Marie', 'notes' => [['element' => 'Algèbre', 'note' => 13]]],
            ])),
            FauxFournisseur::texte('Relisez le tableau puis validez : rien n\'est enregistré avant.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        $this->assertStringContainsString('proposer_releve_notes_lmd', $systeme);
        $this->assertStringContainsString('épreuve non composée', $systeme);
        $this->assertStringContainsString('Appelle l\'outil AVANT toute question', $systeme);
        $this->assertStringContainsString('Relaie alors TOUTES ses questions', $systeme);

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Voici le relevé du S1 2025-2026 de L1A Batiment : BAMBA Awa Marie a 13 en Algèbre.']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['proposer_releve_notes_lmd'], array_column($r->appels, 'tool'));
        $this->assertSame(0, ESBTPNote::count());
        $this->assertSame(1, DB::table('chatbot_actions_log')->where('action_type', 'releve_notes_lmd')->where('status', 'proposed')->count());
    }
}
