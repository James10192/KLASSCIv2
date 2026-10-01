<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Frais\AjusterMontantSouscription;
use App\Domain\Comptabilite\Souscriptions\AjustementMontantSouscription;
use App\Domain\Comptabilite\Souscriptions\AjustementRefuse;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\Chatbot\Tools\DiagnostiquerReinscriptionTool;
use App\Services\Inscriptions\NormalisationTypeInscription;
use App\Services\Reinscription\SoldeDeReinscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Réinscription bloquée par un impayé que l'élève ne devait pas : Nanan
 * diagnostique, propose d'ajuster le dû, la personne valide. Le même chemin
 * sert la CLI.
 */
class ReinscriptionBloqueeTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private ESBTPInscription $inscription;
    private ESBTPFraisCategory $categorie;
    private ESBTPFraisSubscription $souscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);

        Role::findOrCreate('superAdmin', 'web');
        foreach (['frais.souscriptions.ajuster', 'inscriptions.view', 'students.view', 'finances.etudiants.voir'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        // L'année quittée, et une année courante à part.
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $quittee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false, 'start_date' => now()->subYear()->startOfMonth(), 'end_date' => now()->subMonths(2)]);
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $this->inscription = ESBTPInscription::factory()->create(['annee_universitaire_id' => $quittee->id]);
        $this->categorie = ESBTPFraisCategory::factory()->create(['name' => 'Frais d\'inscription']);
        $this->souscription = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->categorie->id,
            'amount' => 220000,
        ]);

        $conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
        app(ContexteDEchange::class)->conversation = $conversation;
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
    }

    public function test_le_diagnostic_dit_pourquoi_et_que_la_decision_ne_repose_sur_rien(): void
    {
        $d = app(DiagnostiquerReinscriptionTool::class)
            ->executeAuthorized(['etudiant_id' => $this->inscription->etudiant_id], $this->admin)['diagnostic'];

        $this->assertSame((int) $this->inscription->id, $d['inscription_id']);
        $this->assertSame('solde_impaye', $d['cause']);
        $this->assertEquals(220000, $d['solde']);
        $this->assertTrue($d['aucun_versement_enregistre']);
        $this->assertFalse($d['decision_fiable']);
        $this->assertTrue($d['peut_ajuster_le_du']);
    }

    public function test_le_diagnostic_masque_les_montants_sans_droit_finances(): void
    {
        $lecteur = $this->utilisateur();
        $lecteur->givePermissionTo('inscriptions.view');

        $d = app(DiagnostiquerReinscriptionTool::class)
            ->executeAuthorized(['etudiant_id' => $this->inscription->etudiant_id], $lecteur)['diagnostic'];

        $this->assertIsString($d['solde']);
        $this->assertArrayNotHasKey('du', $d['frais'][0]);
        $this->assertFalse($d['peut_ajuster_le_du']);
    }

    public function test_proposer_n_ecrit_rien_valider_ramene_le_du_a_zero_avec_trace(): void
    {
        $resultat = app(AjusterMontantSouscription::class)->executeAuthorized([
            'inscription_id' => $this->inscription->id,
            'montant' => 0,
            'motif' => 'Absente de l\'état des arriérés 2025-2026 de la comptabilité',
        ], $this->admin);

        $this->assertSame('approbation', $resultat['widget']['kind']);
        $this->assertEquals(220000, (float) $this->souscription->fresh()->amount);

        $this->actingAs($this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->assertOk()->assertJson(['statut' => 'executee']);

        $apres = $this->souscription->fresh();
        $this->assertEquals(0, (float) $apres->amount);
        $this->assertStringContainsString('arriérés 2025-2026', (string) $apres->notes);
        $this->assertTrue(Audit::query()->where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $apres->id)->where('event', 'updated')->exists());
        $this->assertEquals(0.0, ESBTPFraisSubscription::dueAmountForInscription($this->inscription->id));
    }

    public function test_une_proposition_sans_motif_demande_le_motif(): void
    {
        $resultat = app(AjusterMontantSouscription::class)->executeAuthorized([
            'inscription_id' => $this->inscription->id, 'montant' => 0, 'motif' => 'court',
        ], $this->admin);

        $this->assertArrayNotHasKey('widget', $resultat);
        $this->assertStringContainsString('motif', implode(' ', $resultat['manques']));
        $this->assertEquals(220000, (float) $this->souscription->fresh()->amount);
    }

    public function test_on_ne_descend_jamais_sous_ce_qui_est_paye(): void
    {
        ESBTPPaiement::factory()->pour($this->inscription)->surCategorie($this->categorie->id)->montant(50000)->create();

        $examen = app(AjustementMontantSouscription::class)->examiner($this->inscription->id, null, 0);

        $this->assertNotEmpty($examen['refus']);
        $this->expectException(AjustementRefuse::class);
        app(AjustementMontantSouscription::class)->appliquer($examen, 'Exonération accordée par la direction', $this->admin->id);
    }

    public function test_un_frais_modifie_entre_examen_et_validation_est_refuse(): void
    {
        $service = app(AjustementMontantSouscription::class);
        $examen = $service->examiner($this->inscription->id, null, 0);

        $this->travel(2)->seconds();
        $this->souscription->update(['amount' => 200000]);

        $this->expectException(AjustementRefuse::class);
        $service->appliquer($examen, 'Exonération accordée par la direction', $this->admin->id);
    }

    public function test_sans_la_permission_l_action_n_est_pas_offerte(): void
    {
        $autre = $this->utilisateur();
        $autre->givePermissionTo('inscriptions.view');

        $this->assertFalse(app(AjusterMontantSouscription::class)->isAvailableFor($autre));
    }

    public function test_la_cli_montre_puis_applique(): void
    {
        Sanctum::actingAs($this->admin, ['cli:admin']);
        $corps = ['inscription_id' => $this->inscription->id, 'montant' => 0, 'motif' => 'Absente de l\'état des arriérés'];

        $apercu = $this->postJson('/api/cli/frais/souscriptions/ajuster', $corps)
            ->assertOk()->assertJsonPath('data.applique', false);
        $this->assertEquals(0, $apercu->json('data.solde_apres'));
        $this->assertEquals(220000, (float) $this->souscription->fresh()->amount);

        $this->postJson('/api/cli/frais/souscriptions/ajuster', $corps + ['apply' => true])
            ->assertOk()->assertJsonPath('data.applique', true);
        $this->assertEquals(0, (float) $this->souscription->fresh()->amount);

        $this->postJson('/api/cli/frais/souscriptions/ajuster', ['inscription_id' => $this->inscription->id, 'montant' => 5000, 'apply' => true])
            ->assertStatus(422);
    }

    private function diagnostic(): array
    {
        return app(DiagnostiquerReinscriptionTool::class)
            ->executeAuthorized(['etudiant_id' => $this->inscription->etudiant_id], $this->admin)['diagnostic'];
    }

    public function test_un_trop_verse_sur_un_frais_compense_l_autre_comme_a_l_ecran(): void
    {
        $scolarite = ESBTPFraisCategory::factory()->create(['name' => 'Scolarité']);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id, 'frais_category_id' => $scolarite->id, 'amount' => 100000,
        ]);
        ESBTPPaiement::factory()->pour($this->inscription)->surCategorie($scolarite->id)->montant(320000)->create();

        $d = $this->diagnostic();

        $this->assertLessThanOrEqual(0, SoldeDeReinscription::solde($this->inscription->id));
        $this->assertFalse($d['bloquee']);
        $this->assertNull($d['cause']);
    }

    public function test_une_inscription_quittee_sur_l_annee_courante_veut_dire_deja_reinscrit(): void
    {
        $this->inscription->update(['annee_universitaire_id' => ESBTPAnneeUniversitaire::where('is_current', true)->value('id')]);

        $d = $this->diagnostic();

        $this->assertTrue($d['deja_reinscrit_cette_annee']);
        $this->assertFalse($d['bloquee']);
        $this->assertSame('deja_reinscrit', $d['cause']);
    }

    public function test_une_reinscription_annulee_ne_cache_pas_l_impaye(): void
    {
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'annee_universitaire_id' => ESBTPAnneeUniversitaire::where('is_current', true)->value('id'),
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'status' => 'annulée',
            'workflow_step' => 'etudiant_cree',
        ]);

        $d = $this->diagnostic();

        $this->assertFalse($d['deja_reinscrit_cette_annee']);
        $this->assertSame('solde_impaye', $d['cause']);
    }
}
