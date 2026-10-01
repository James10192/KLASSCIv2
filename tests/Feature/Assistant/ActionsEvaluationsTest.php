<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Evaluations\CreerEvaluation;
use App\Domain\Assistant\Actions\Evaluations\PublierNotes;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotActionLog;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Créer une évaluation et publier ses notes : Nanan propose, la personne
 * valide, et l'écriture passe par le modèle (donc par le journal d'audit).
 */
class ActionsEvaluationsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private ESBTPAnneeUniversitaire $annee;
    private ESBTPClasse $classe;
    private ESBTPMatiere $matiere;
    private ChatbotConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);

        Role::findOrCreate('superAdmin', 'web');
        foreach (['evaluations.create', 'evaluations.edit', 'identity.teach', 'identity.coordinate'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $this->annee->id]);
        $this->matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        $this->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
        app(ContexteDEchange::class)->conversation = $this->conversation;
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_' . Str::lower(Str::random(8))]));
    }

    private function argsCreation(array $surcharge = []): array
    {
        return array_merge([
            'classe_id' => $this->classe->id,
            'matiere_id' => $this->matiere->id,
            'titre' => 'Devoir surveillé 1',
            'type' => 'devoir',
            'date' => now()->addWeek()->toDateString(),
            'heure_debut' => '08:00',
            'heure_fin' => '10:00',
            'bareme' => 20,
            'coefficient' => 2,
            'periode' => 'S1',
        ], $surcharge);
    }

    private function valider(array $widget, ?User $qui = null)
    {
        return $this->actingAs($qui ?? $this->admin)->postJson($widget['valider_url'], ['jeton' => $widget['jeton']]);
    }

    // --- Création ---------------------------------------------------------

    public function test_proposer_ne_cree_rien_valider_cree_une_seule_evaluation_auditee(): void
    {
        $resultat = app(CreerEvaluation::class)->executeAuthorized($this->argsCreation(), $this->admin);

        $this->assertSame('approbation', $resultat['widget']['kind']);
        $this->assertSame(0, ESBTPEvaluation::where('classe_id', $this->classe->id)->count());

        $this->valider($resultat['widget'])->assertOk()->assertJson(['statut' => 'executee']);

        $evaluation = ESBTPEvaluation::where('classe_id', $this->classe->id)->sole();
        $this->assertSame('Devoir surveillé 1', $evaluation->titre);
        $this->assertSame('semestre1', $evaluation->periode);
        $this->assertEquals(20, (float) $evaluation->bareme);
        $this->assertEquals(2, (float) $evaluation->coefficient);
        $this->assertSame(120, (int) $evaluation->duree_minutes);
        $this->assertSame((int) $this->annee->id, (int) $evaluation->annee_universitaire_id);
        $this->assertFalse((bool) $evaluation->is_published);
        $this->assertSame(ESBTPEvaluation::STATUS_DRAFT, $evaluation->status);
        $this->assertSame((int) $this->admin->id, (int) $evaluation->created_by);

        $this->assertTrue(Audit::query()->where('auditable_type', ESBTPEvaluation::class)
            ->where('auditable_id', $evaluation->id)->where('event', 'created')->exists());
        $journal = ChatbotActionLog::sole();
        $this->assertSame('executed', $journal->status);
        $this->assertSame((int) $evaluation->id, (int) $journal->model_id);

        // Second clic : rien n'est rejoué.
        $this->valider($resultat['widget'])->assertStatus(409);
        $this->assertSame(1, ESBTPEvaluation::where('classe_id', $this->classe->id)->count());
    }

    public function test_bareme_et_coefficient_ne_sont_jamais_supposes(): void
    {
        $args = $this->argsCreation();
        unset($args['bareme'], $args['coefficient']);

        $p = app(CreerEvaluation::class)->preparer($args, $this->admin);

        $this->assertFalse($p->estComplete());
        $this->assertStringContainsString('Barème non donné', implode(' | ', $p->manques));
        $this->assertStringContainsString('Coefficient non donné', implode(' | ', $p->manques));
    }

    public function test_une_evaluation_identique_n_est_pas_creee_deux_fois(): void
    {
        $premiere = app(CreerEvaluation::class)->executeAuthorized($this->argsCreation(), $this->admin);
        $seconde = app(CreerEvaluation::class)->executeAuthorized($this->argsCreation(), $this->admin);

        $this->valider($premiere['widget'])->assertOk();
        // La seconde a été proposée avant la création : son empreinte ne tient plus.
        $this->valider($seconde['widget'])->assertStatus(409)->assertJson(['statut' => 'perimee']);
        $this->assertSame(1, ESBTPEvaluation::where('classe_id', $this->classe->id)->count());

        $troisieme = app(CreerEvaluation::class)->preparer($this->argsCreation(['titre' => 'devoir SURVEILLÉ 1']), $this->admin);
        $this->assertStringContainsString('existe déjà', implode(' | ', $troisieme->manques));
    }

    public function test_un_enseignant_ne_cree_que_pour_une_matiere_qui_lui_est_confiee(): void
    {
        $enseignant = $this->utilisateur();
        $enseignant->givePermissionTo(['evaluations.create', 'identity.teach']);

        $p = app(CreerEvaluation::class)->preparer($this->argsCreation(), $enseignant);

        $this->assertStringContainsString("n'est pas affecté", implode(' | ', $p->manques));
    }

    public function test_la_creation_exige_la_permission(): void
    {
        $sans = $this->utilisateur();

        $this->assertFalse(app(CreerEvaluation::class)->isAvailableFor($sans));
        $this->assertSame(['error' => 'Outil indisponible.'], app(CreerEvaluation::class)->executeAuthorized($this->argsCreation(), $sans));
    }

    // --- Publication des notes --------------------------------------------

    private function evaluationTerminee(array $surcharge = []): ESBTPEvaluation
    {
        $evaluation = ESBTPEvaluation::factory()->create(array_merge([
            'classe_id' => $this->classe->id, 'matiere_id' => $this->matiere->id,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'bareme' => 20,
            'date_evaluation' => now()->subWeek(), 'is_published' => true, 'notes_published' => false,
            'status' => ESBTPEvaluation::STATUS_COMPLETED,
        ], $surcharge));
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id, 'matiere_id' => $evaluation->matiere_id,
            'classe_id' => $evaluation->classe_id, 'note' => 12, 'valeur' => 12,
        ]);

        return $evaluation;
    }

    public function test_publier_rend_les_notes_visibles_seulement_apres_validation(): void
    {
        $evaluation = $this->evaluationTerminee();

        $resultat = app(PublierNotes::class)->executeAuthorized(['evaluation_ids' => [$evaluation->id]], $this->admin);
        $this->assertSame('approbation', $resultat['widget']['kind']);
        $this->assertFalse((bool) $evaluation->fresh()->notes_published);

        $this->valider($resultat['widget'])->assertOk()->assertJson(['statut' => 'executee']);

        $this->assertTrue((bool) $evaluation->fresh()->notes_published);
        $this->assertTrue(Audit::query()->where('auditable_type', ESBTPEvaluation::class)
            ->where('auditable_id', $evaluation->id)->where('event', 'updated')
            ->where('new_values', 'like', '%notes_published%')->exists());
    }

    public function test_une_evaluation_non_publiable_bloque_avec_sa_raison(): void
    {
        $sansNote = ESBTPEvaluation::factory()->create([
            'classe_id' => $this->classe->id, 'matiere_id' => $this->matiere->id,
            'annee_universitaire_id' => $this->annee->id, 'status' => ESBTPEvaluation::STATUS_SCHEDULED,
        ]);
        $dejaPubliee = $this->evaluationTerminee(['notes_published' => true]);

        $p = app(PublierNotes::class)->preparer(['evaluation_ids' => [$sansNote->id, $dejaPubliee->id, 999999999]], $this->admin);

        $manques = implode(' | ', $p->manques);
        $this->assertStringContainsString('aucune note saisie', $manques);
        $this->assertStringContainsString('déjà publiées', $manques);
        $this->assertStringContainsString('introuvable', $manques);
        $this->assertSame(0, ChatbotActionLog::count());
    }

    public function test_un_enseignant_ne_publie_que_ses_evaluations(): void
    {
        $enseignant = $this->utilisateur();
        $enseignant->givePermissionTo(['evaluations.edit', 'identity.teach']);
        $autre = $this->evaluationTerminee(['enseignant_id' => $this->admin->id, 'created_by' => $this->admin->id]);
        $sienne = $this->evaluationTerminee(['enseignant_id' => $enseignant->id]);

        $refus = app(PublierNotes::class)->preparer(['evaluation_ids' => [$autre->id]], $enseignant);
        $this->assertStringContainsString('vos évaluations', implode(' | ', $refus->manques));

        $ok = app(PublierNotes::class)->preparer(['evaluation_ids' => [$sienne->id]], $enseignant);
        $this->assertTrue($ok->estComplete(), implode(' | ', $ok->manques));
    }

    public function test_publication_perimee_si_publiee_entre_temps(): void
    {
        $evaluation = $this->evaluationTerminee();
        $resultat = app(PublierNotes::class)->executeAuthorized(['evaluation_ids' => [$evaluation->id]], $this->admin);

        // Publiée depuis l'écran avant le clic « Valider ».
        $evaluation->update(['notes_published' => true]);

        $this->valider($resultat['widget'])->assertStatus(409)->assertJson(['statut' => 'perimee']);
        $this->assertSame('expired', ChatbotActionLog::sole()->status);
    }
}
