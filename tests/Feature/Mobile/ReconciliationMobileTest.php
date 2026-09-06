<?php

namespace Tests\Feature\Mobile;

use App\Domain\Comptabilite\Reconciliation\Models\CashCount;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationDiscrepancy;
use App\Domain\Comptabilite\Reconciliation\Models\ReconciliationSession;
use App\Helpers\InstallationHelper;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Réconciliation caisse dans le shell mobile (issue #963, lot 2a — comptable).
 *
 * Sous 992px, la liste des sessions devient des cartes-lignes avec un bouton
 * flottant « + » pour ouvrir une session, et la fiche devient un comptage à la
 * main : progression en quatre étapes, un bloc par mode de paiement, écarts en
 * lignes, feuilles de résolution / confirmation / réouverture. Chaque action
 * n'apparaît que sous sa permission. Le DOM de bureau reste rendu à côté, dans
 * .m-only-desktop. Sans shell, rien de mobile n'est rendu.
 */
class ReconciliationMobileTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'comptabilite.access',
            'comptabilite.reconciliation.view',
            'comptabilite.reconciliation.open',
            'comptabilite.reconciliation.resolve',
            'comptabilite.reconciliation.approve',
            'comptabilite.reconciliation.export',
            'comptabilite.reconciliation.bypass_lock',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        // Le garde « installed » du groupe de routes exige qu'un superAdmin existe.
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        MobileProfileResolver::oublier();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create([
            'is_current' => true,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
        ]);
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        parent::tearDown();
    }

    public function test_la_liste_mobile_rend_les_sessions_en_cartes_et_le_bouton_flottant_sous_permission(): void
    {
        $comptable = $this->comptable(['comptabilite.reconciliation.view', 'comptabilite.reconciliation.open']);
        $session = $this->sessionDeCaisse($comptable, 'draft');

        $response = $this->actingAs($comptable)->get(route('esbtp.comptabilite.reconciliation.index'));

        $response->assertOk();
        $response->assertSee('has-m-shell m-profile-comptable', false);
        // Bureau conservé, écran mobile à côté.
        $response->assertSee('class="m-only-desktop"', false);
        $response->assertSee('m-only-mobile m-screen rim-screen', false);
        // Bouton flottant + feuille d'ouverture : la permission open est là.
        $response->assertSee('class="m-fab"', false);
        $response->assertSee('data-m-sheet="rim-nouvelle"', false);
        // Les données de la liste alimentent l'état Alpine (code + écart total calculé).
        $response->assertSee($session->code);
        $response->assertSee($this->fragmentJson('total_ecart'), false);
        $response->assertDontSee('window.location.reload()', false);
    }

    public function test_sans_la_permission_d_ouverture_ni_bouton_flottant_ni_feuille(): void
    {
        $lecteur = $this->comptable(['comptabilite.reconciliation.view']);

        $response = $this->actingAs($lecteur)->get(route('esbtp.comptabilite.reconciliation.index'));

        $response->assertOk();
        $response->assertSee('m-only-mobile m-screen rim-screen', false);
        $response->assertDontSee('class="m-fab"', false);
        $response->assertDontSee('data-m-sheet="rim-nouvelle"', false);
    }

    public function test_la_liste_json_porte_l_ecart_total_de_chaque_session(): void
    {
        $comptable = $this->comptable(['comptabilite.reconciliation.view', 'comptabilite.reconciliation.open']);
        $session = $this->sessionDeCaisse($comptable, 'draft');
        CashCount::create([
            'reconciliation_session_id' => $session->id,
            'mode_paiement' => 'especes',
            'montant_compte' => 1060000,
            'montant_systeme' => 1310000,
            'counted_by' => $comptable->id,
            'counted_at' => now(),
        ]);

        $response = $this->actingAs($comptable)->getJson(route('esbtp.comptabilite.reconciliation.index'));

        $response->assertOk();
        $response->assertJsonPath('sessions.data.0.code', $session->code);
        $this->assertEqualsWithDelta(-250000.0, (float) $response->json('sessions.data.0.total_ecart'), 0.001);
    }

    public function test_la_fiche_mobile_porte_les_etapes_les_comptages_par_mode_et_les_feuilles_sous_permission(): void
    {
        $comptable = $this->comptable([
            'comptabilite.reconciliation.view',
            'comptabilite.reconciliation.open',
            'comptabilite.reconciliation.resolve',
        ]);
        $session = $this->sessionDeCaisse($comptable, 'draft');
        $count = CashCount::create([
            'reconciliation_session_id' => $session->id,
            'mode_paiement' => 'virement',
            'montant_compte' => 0,
            'montant_systeme' => 250000,
            'counted_by' => $comptable->id,
            'counted_at' => now(),
        ]);
        ReconciliationDiscrepancy::create([
            'reconciliation_session_id' => $session->id,
            'cash_count_id' => $count->id,
            'type' => 'paiement_en_trop',
            'montant_ecart' => -250000,
            'action' => 'a_traiter',
            'motif' => 'Virement reçu par la banque, non pointé en caisse',
        ]);

        $response = $this->actingAs($comptable)->get(route('esbtp.comptabilite.reconciliation.show', $session));

        $response->assertOk();
        $response->assertSee('m-only-mobile m-screen rsm-screen', false);
        $response->assertSee('class="m-step"', false);
        $response->assertSee('m-bill rsm-bill', false);
        // Feuilles : résolution (resolve) et confirmation (open) présentes, approbation absente.
        $response->assertSee('data-m-sheet="rsm-resoudre"', false);
        $response->assertSee('data-m-sheet="rsm-confirmer"', false);
        $response->assertDontSee('data-m-sheet="rsm-rouvrir"', false);
        $response->assertDontSee('Approuver la session');
        $response->assertDontSee('Clôturer la session');
        // Le mode de l'écart et le paiement lié (absent) sont exposés à l'écran de résolution.
        $response->assertSee($this->fragmentJson(['mode_label' => 'Virement bancaire']), false);
        $response->assertSee($this->fragmentJson(['paiement_concerne_id' => null]), false);
        // Rien n'est écrit en dur : l'école vient des réglages.
        $response->assertSee(e(SettingsHelper::getSchoolInfo()['name']), false);
    }

    public function test_approbation_cloture_pv_et_reouverture_chacun_sous_sa_permission(): void
    {
        $ouvreur = $this->comptable(['comptabilite.reconciliation.view', 'comptabilite.reconciliation.open']);
        $approbateur = $this->comptable([
            'comptabilite.reconciliation.view',
            'comptabilite.reconciliation.approve',
            'comptabilite.reconciliation.export',
            'comptabilite.reconciliation.bypass_lock',
        ]);
        $session = $this->sessionDeCaisse($ouvreur, 'closed');

        $response = $this->actingAs($approbateur)->get(route('esbtp.comptabilite.reconciliation.show', $session));

        $response->assertOk();
        $response->assertSee('Approuver la session');
        $response->assertSee('Clôturer la session');
        $response->assertSee('data-m-sheet="rsm-rouvrir"', false);
        $response->assertSee(route('esbtp.comptabilite.reconciliation.export-pv', $session), false);
        // Sans resolve : pas de feuille de résolution ni de « Passer aux écarts ».
        $response->assertDontSee('data-m-sheet="rsm-resoudre"', false);
        $response->assertDontSee('Passer aux écarts');
    }

    public function test_les_mutations_repondent_en_json_422_quand_le_domaine_refuse(): void
    {
        $comptable = $this->comptable([
            'comptabilite.reconciliation.view',
            'comptabilite.reconciliation.open',
            'comptabilite.reconciliation.resolve',
        ]);
        $session = $this->sessionDeCaisse($comptable, 'draft');
        $count = CashCount::create([
            'reconciliation_session_id' => $session->id,
            'mode_paiement' => 'especes',
            'montant_compte' => 0,
            'montant_systeme' => 100000,
            'counted_by' => $comptable->id,
            'counted_at' => now(),
        ]);
        ReconciliationDiscrepancy::create([
            'reconciliation_session_id' => $session->id,
            'cash_count_id' => $count->id,
            'type' => 'paiement_en_trop',
            'montant_ecart' => -100000,
            'action' => 'a_traiter',
            'motif' => 'Écart auto-détecté',
        ]);

        // Passage en revue avec un écart non traité : refus métier, en JSON, pas en page d'erreur.
        $this->actingAs($comptable)
            ->postJson(route('esbtp.comptabilite.reconciliation.review', $session))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);

        // Comptage sur une session figée : même contrat.
        $figee = $this->sessionDeCaisse($comptable, 'review');
        $this->actingAs($comptable)
            ->postJson(route('esbtp.comptabilite.reconciliation.record-count', $figee), [
                'mode_paiement' => 'especes',
                'montant_compte' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_reglage_coupe_le_dom_de_bureau_est_rendu_sans_ecran_mobile(): void
    {
        $comptable = $this->comptable(['comptabilite.reconciliation.view', 'comptabilite.reconciliation.open']);
        $session = $this->sessionDeCaisse($comptable, 'draft');
        SettingsHelper::set(MobileProfileResolver::REGLAGE_ACTIF, '0');
        MobileProfileResolver::oublier();

        $index = $this->actingAs($comptable)->get(route('esbtp.comptabilite.reconciliation.index'));
        $index->assertOk();
        $index->assertDontSee('has-m-shell', false);
        $index->assertDontSee('rim-screen', false);
        $index->assertSee('rec-hero', false);

        $show = $this->actingAs($comptable)->get(route('esbtp.comptabilite.reconciliation.show', $session));
        $show->assertOk();
        $show->assertDontSee('rsm-screen', false);
        $show->assertSee('rec-tabs', false);
    }

    /**
     * Fragment tel que Blade @json le rend (JSON_HEX_TAG|APOS|AMP|QUOT) : une
     * paire clé/valeur sans les accolades, ou une simple clé entre guillemets.
     *
     * @param array<string, mixed>|string $valeur
     */
    private function fragmentJson(array|string $valeur): string
    {
        $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        if (is_string($valeur)) {
            return json_encode($valeur, $flags);
        }

        return trim((string) json_encode($valeur, $flags), '{}');
    }

    /**
     * @param array<int, string> $permissions
     */
    private function comptable(array $permissions): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->givePermissionTo(array_merge(['comptabilite.access'], $permissions));

        return $user;
    }

    private function sessionDeCaisse(User $ouvreur, string $status): ReconciliationSession
    {
        return ReconciliationSession::create([
            'code' => ReconciliationSession::reserveCode($this->annee->id),
            'frequency' => 'daily',
            'annee_universitaire_id' => $this->annee->id,
            'period_start' => now()->toDateString(),
            'period_end' => now()->toDateString(),
            'status' => $status,
            'opened_by' => $ouvreur->id,
            'opened_at' => now(),
        ]);
    }
}
