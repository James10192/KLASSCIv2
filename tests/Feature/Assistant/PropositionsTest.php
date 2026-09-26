<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\ExecutionDesPropositions;
use App\Domain\Assistant\Actions\Notes\SaisirNotes;
use App\Domain\Assistant\Outils\ResumeOutil;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * L'assistant propose, la personne valide : rien n'est écrit avant le clic, et
 * le serveur revérifie tout au moment d'écrire.
 */
class PropositionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private ESBTPEvaluation $evaluation;
    /** @var ESBTPEtudiant[] */
    private array $etudiants;
    private ChatbotConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);

        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]));
        $this->user->assignRole('superAdmin');

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        $ids = ESBTPInscription::factory()->count(3)->create([
            'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
            'filiere_id' => $classe->filiere_id, 'niveau_id' => $classe->niveau_etude_id,
            'status' => 'active', 'workflow_step' => 'etudiant_cree',
        ])->pluck('etudiant_id')->all();

        $noms = [['KOUASSI', 'Aya Marie', 'MAT-001'], ['KONAN', 'Jean', 'MAT-002'], ['KONAN', 'Paul', 'MAT-003']];
        foreach ($ids as $i => $id) {
            ESBTPEtudiant::whereKey($id)->update(['nom' => $noms[$i][0], 'prenoms' => $noms[$i][1], 'matricule' => $noms[$i][2]]);
        }
        $this->etudiants = ESBTPEtudiant::whereIn('id', $ids)->orderBy('matricule')->get()->all();

        $this->evaluation = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id, 'bareme' => 20, 'periode' => 'semestre1',
            'is_published' => true,
        ]);

        $this->conversation = ChatbotConversation::create([
            'user_id' => $this->user->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
        app(ContexteDEchange::class)->conversation = $this->conversation;
    }

    private function proposer(array $notes, bool $valider = false): array
    {
        return app(SaisirNotes::class)->executeAuthorized(
            ['evaluation_id' => $this->evaluation->id, 'notes' => $notes, 'valider' => $valider],
            $this->user
        );
    }

    public function test_les_etudiants_sont_reconnus_par_matricule_ou_nom_dans_les_deux_ordres(): void
    {
        $p = app(SaisirNotes::class)->preparer(['evaluation_id' => $this->evaluation->id, 'notes' => [
            ['etudiant' => 'mat-001', 'note' => 14],
            ['etudiant' => 'Jean Konan', 'note' => 9.5],
            ['etudiant' => 'KONAN Paul', 'absent' => true],
        ]], $this->user);

        $this->assertTrue($p->estComplete(), implode(' | ', $p->manques));
        $this->assertSame(
            [[$this->etudiants[0]->id, 14.0, false], [$this->etudiants[1]->id, 9.5, false], [$this->etudiants[2]->id, null, true]],
            array_map(fn ($e) => [$e['etudiant_id'], $e['note'], $e['is_absent']], $p->donnees['entrees'])
        );
    }

    public function test_rien_n_est_devine_nom_ambigu_inconnu_note_hors_bareme_doublon(): void
    {
        $p = app(SaisirNotes::class)->preparer(['evaluation_id' => $this->evaluation->id, 'notes' => [
            ['etudiant' => 'Konan', 'note' => 10],        // deux KONAN : jamais choisi
            ['etudiant' => 'Aya', 'note' => 10],          // un seul proche : jamais choisi non plus
            ['etudiant' => 'Traoré Ali', 'note' => 10],   // hors de la classe
            ['etudiant' => 'MAT-001', 'note' => 21],      // barème 20
            ['etudiant' => 'MAT-002'],                    // ni note ni absent
            ['etudiant' => 'MAT-003', 'note' => 8],
            ['etudiant' => 'Paul Konan', 'note' => 9],    // même étudiant
        ]], $this->user);

        $m = $p->manques;
        $this->assertCount(6, $m);
        $this->assertStringContainsString('KONAN Jean (MAT-002), KONAN Paul (MAT-003)', $m[0]);
        $this->assertStringContainsString('KOUASSI Aya Marie (MAT-001)', $m[1]);
        $this->assertStringContainsString('aucun étudiant', $m[2]);
        $this->assertStringContainsString('barème', $m[3]);
        $this->assertStringContainsString('note absente', $m[4]);
        $this->assertStringContainsString('deux fois', $m[5]);
    }

    public function test_une_proposition_incomplete_n_est_ni_enregistree_ni_presentee(): void
    {
        $resultat = $this->proposer([['etudiant' => 'Inconnu', 'note' => 10]]);

        $this->assertArrayHasKey('error', $resultat);
        $this->assertSame(0, ChatbotActionLog::count());
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());
    }

    public function test_proposer_n_ecrit_rien_valider_ecrit_une_seule_fois(): void
    {
        $resultat = $this->proposer([['etudiant' => 'MAT-001', 'note' => 14], ['etudiant' => 'MAT-002', 'note' => 12]]);

        $widget = $resultat['widget'];
        $this->assertSame('approbation', $widget['kind']);
        $this->assertCount(2, $widget['lignes']);
        $this->assertStringContainsString('1 étudiant(s) de la classe resteront sans note', implode(' ', $widget['avertissements']));
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());

        // Le modèle ne voit ni le jeton ni le tableau.
        $pourModele = ResumeOutil::pourModele('proposer_saisie_notes', $resultat, true);
        $this->assertStringNotContainsString($widget['jeton'], $pourModele);
        $this->assertStringContainsString('en_attente_de_validation', $pourModele);

        $this->actingAs($this->user)
            ->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])
            ->assertOk()->assertJson(['statut' => 'executee']);

        $notes = ESBTPNote::where('evaluation_id', $this->evaluation->id)->pluck('note', 'etudiant_id');
        $this->assertEquals([$this->etudiants[0]->id => 14, $this->etudiants[1]->id => 12], $notes->map(fn ($n) => (float) $n)->all());
        $journal = ChatbotActionLog::sole();
        $this->assertSame('executed', $journal->status);
        $this->assertSame($this->user->id, (int) $journal->approved_by);
        $this->assertSame(1, ChatbotMessage::where('conversation_id', $this->conversation->id)->where('content', 'like', '✓%')->count());

        // Un second clic ne rejoue rien.
        $this->actingAs($this->user)
            ->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])
            ->assertStatus(409)->assertJson(['statut' => 'traitee']);
        $this->assertSame(2, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());
    }

    public function test_un_jeton_faux_ou_une_autre_personne_ne_valident_pas(): void
    {
        $widget = $this->proposer([['etudiant' => 'MAT-001', 'note' => 14]])['widget'];

        $this->actingAs($this->user)->postJson($widget['valider_url'], ['jeton' => str_repeat('0', 64)])->assertStatus(409);

        $autre = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]));
        $autre->assignRole('superAdmin');
        $this->actingAs($autre)->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])->assertStatus(409);

        $this->assertSame('proposed', ChatbotActionLog::sole()->status);
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());
    }

    public function test_si_les_donnees_changent_entre_temps_rien_n_est_ecrit(): void
    {
        $widget = $this->proposer([['etudiant' => 'MAT-001', 'note' => 14]])['widget'];

        // Quelqu'un saisit la note par l'écran pendant ce temps.
        ESBTPNote::create([
            'etudiant_id' => $this->etudiants[0]->id, 'evaluation_id' => $this->evaluation->id,
            'classe_id' => $this->evaluation->classe_id, 'matiere_id' => $this->evaluation->matiere_id,
            'note' => 11, 'is_absent' => 0, 'semestre' => $this->evaluation->periode, 'created_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])
            ->assertStatus(409)->assertJson(['statut' => 'perimee']);

        $this->assertSame(11.0, (float) ESBTPNote::where('evaluation_id', $this->evaluation->id)->value('note'));
        $this->assertSame('expired', ChatbotActionLog::sole()->status);
    }

    public function test_un_bareme_change_entre_temps_perime_la_proposition(): void
    {
        $widget = $this->proposer([['etudiant' => 'MAT-001', 'note' => 15]])['widget'];
        $this->evaluation->update(['bareme' => 40]);

        $this->actingAs($this->user)->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])
            ->assertStatus(409)->assertJson(['statut' => 'perimee']);
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());
    }

    public function test_tout_ou_rien_un_refus_ou_un_ecart_a_l_ecriture_n_ecrit_aucune_note(): void
    {
        $saisie = app(\App\Domain\Notes\SaisieGroupeeDeNotes::class);
        $entree = fn (ESBTPEtudiant $e, $note) => ['etudiant_id' => $e->id, 'evaluation_id' => $this->evaluation->id, 'note' => $note, 'is_absent' => false];

        // Un élève hors de la classe en dernier : les deux premières lignes, valides, ne sont pas écrites.
        $horsClasse = ['etudiant_id' => 999999, 'evaluation_id' => $this->evaluation->id, 'note' => 10, 'is_absent' => false];
        try {
            $saisie->enregistrerToutOuRien([$entree($this->etudiants[0], 12), $entree($this->etudiants[1], 13), $horsClasse], $this->user, false, []);
            $this->fail('La saisie aurait dû être interrompue.');
        } catch (\App\Domain\Notes\Exceptions\SaisieInterrompue $e) {
            $this->assertStringContainsString('hors de la classe', $e->getMessage());
        }
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $this->evaluation->id)->count());

        // Ce qui avait été montré (« pas de note ») n'est plus vrai : rien n'est écrit.
        $saisie->enregistrer([$entree($this->etudiants[0], 8)], $this->user, false);
        $this->expectException(\App\Domain\Notes\Exceptions\SaisieInterrompue::class);
        try {
            $saisie->enregistrerToutOuRien([$entree($this->etudiants[0], 12)], $this->user, false, [$this->etudiants[0]->id => null]);
        } finally {
            $this->assertSame(8.0, (float) ESBTPNote::where('evaluation_id', $this->evaluation->id)->value('note'));
        }
    }

    public function test_une_note_identique_n_est_pas_reecrite_sauf_pour_etre_validee(): void
    {
        app(\App\Domain\Notes\SaisieGroupeeDeNotes::class)->enregistrer([
            ['etudiant_id' => $this->etudiants[0]->id, 'evaluation_id' => $this->evaluation->id, 'note' => 14, 'is_absent' => false],
        ], $this->user, false);

        $brouillon = app(SaisirNotes::class)->preparer(['evaluation_id' => $this->evaluation->id, 'notes' => [
            ['etudiant' => 'MAT-001', 'note' => 14], ['etudiant' => 'MAT-002', 'note' => 11],
        ]], $this->user);
        $this->assertSame([$this->etudiants[1]->id], array_column($brouillon->donnees['entrees'], 'etudiant_id'));
        $this->assertSame('Inchangée', $brouillon->tableau['lignes'][0][4]);

        $validation = app(SaisirNotes::class)->preparer(['evaluation_id' => $this->evaluation->id, 'valider' => true, 'notes' => [
            ['etudiant' => 'MAT-001', 'note' => 14],
        ]], $this->user);
        $this->assertSame('Validée', $validation->tableau['lignes'][0][4]);
        $this->assertCount(1, $validation->donnees['entrees']);

        $rien = app(SaisirNotes::class)->preparer(['evaluation_id' => $this->evaluation->id, 'notes' => [['etudiant' => 'MAT-001', 'note' => 14]]], $this->user);
        $this->assertFalse($rien->estComplete());
    }

    public function test_refuser_apres_validation_ne_dit_pas_refusee(): void
    {
        $widget = $this->proposer([['etudiant' => 'MAT-001', 'note' => 14]])['widget'];
        $this->actingAs($this->user)->postJson($widget['valider_url'], ['jeton' => $widget['jeton']])->assertOk();

        $this->actingAs($this->user)->postJson($widget['refuser_url'])->assertStatus(409)->assertJson(['statut' => 'traitee']);
        $this->assertSame('executed', ChatbotActionLog::sole()->status);
        $this->assertSame(0, ChatbotMessage::where('content', 'like', 'Proposition refusée%')->count());
    }

    public function test_refuser_ferme_la_proposition_et_l_historique_montre_l_etat_reel(): void
    {
        $widget = $this->proposer([['etudiant' => 'MAT-001', 'note' => 14]])['widget'];

        $this->actingAs($this->user)->postJson($widget['refuser_url'])->assertOk()->assertJson(['statut' => 'refusee']);
        $this->assertSame('rejected', ChatbotActionLog::sole()->status);
        $this->assertSame('refusee', ExecutionDesPropositions::etat(ChatbotActionLog::sole()));

        ChatbotMessage::create([
            'conversation_id' => $this->conversation->id, 'role' => 'assistant', 'content' => 'Voici ma proposition.',
            'metadata' => ['parties' => [['type' => 'widget', 'id' => 'w1', 'data' => $widget]]],
        ]);
        $historique = $this->actingAs($this->user)->getJson(route('chatbot.history', $this->conversation->session_id))->json('messages');
        $partie = collect($historique)->pluck('parties')->filter()->flatten(1)->firstWhere('type', 'widget');
        $this->assertSame('refusee', $partie['data']['etat']);
    }

    public function test_sans_droit_de_saisie_l_outil_n_est_pas_propose(): void
    {
        $sansDroit = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]));

        $this->assertFalse(app(SaisirNotes::class)->isAvailableFor($sansDroit));
        config(['assistant.actions.actives' => false]);
        $this->assertFalse(app(SaisirNotes::class)->isAvailableFor($this->user));
    }
}
