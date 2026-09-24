<?php

namespace Tests\Feature\Dashboard;

use App\Helpers\InstallationHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Accueil comptable (rule premium-dashboard) : chaque chiffre a son repère,
 * une tendance sur six mois, une file « À traiter », et aucun lien vers un
 * écran que le rôle ne peut pas ouvrir.
 */
class ComptableAccueilTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin.access', 'comptabilite.access', 'paiements.view', 'paiements.avoir', 'paiements.delete', 'comptabilite.dashboard.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superAdmin', 'web');
        User::factory()->create()->assignRole('superAdmin');
        InstallationHelper::flushCachedStatus();
        Carbon::setTestNow(Carbon::parse('2026-09-24 11:00:00'));

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true, 'start_date' => '2025-09-01', 'end_date' => '2026-12-31']);
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $this->inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_les_chiffres_ont_un_repere_une_tendance_et_une_file_de_travail(): void
    {
        $this->versement(['montant' => 100000, 'date_paiement' => '2026-09-24']);
        $this->versement(['montant' => 50000, 'date_paiement' => '2026-09-23']);
        $this->versement(['montant' => 40000, 'date_paiement' => '2026-08-10']);
        $this->versement(['montant' => 200000, 'date_paiement' => '2026-07-10']);
        $this->versement(['montant' => 30000, 'date_paiement' => '2026-09-24', 'status' => 'en_attente']);

        $comptable = $this->comptable(['paiements.view', 'paiements.avoir', 'paiements.delete']);

        $reponse = $this->actingAs($comptable)->get(route('dashboard'));

        $reponse->assertOk();
        $c = $reponse->viewData('compta');
        $this->assertSame(100000.0, $c['totalValidatedToday']);
        $this->assertSame(50000.0, $c['totalPaidYesterday']);
        $this->assertSame(150000.0, $c['totalPaidMonth']);
        // Août jusqu'au 24 : le versement du 10 août.
        $this->assertSame(40000.0, $c['totalPaidPrevMonthToDate']);
        $this->assertSame(390000.0, $c['totalPaid']);
        $this->assertSame(1, $c['countToValidate']);
        $this->assertSame(30000.0, $c['totalPending']);
        $this->assertCount(84, $c['serieJours']);

        $reponse->assertSee('Tableau de bord comptable')
            ->assertSee('À traiter')
            ->assertSee('Versement à valider')
            ->assertSee('au même jour de août', false)
            ->assertSee(route('esbtp.paiements.index', ['status' => 'en_attente']), false)
            ->assertSee('cbChart', false)
            // Actions sur les derniers paiements, depuis l'accueil.
            ->assertSee('Annuler le versement (avoir', false)
            // Pas le droit : pas de lien vers l'analyse financière.
            ->assertDontSee('Analyse financière');
    }

    /**
     * La revue adverse de septembre 2026 : l'accueil et l'analyse financière
     * donnaient deux réponses à la même question. Ils lisent désormais le même
     * calcul ; ce test le verrouille.
     */
    public function test_l_accueil_et_l_analyse_financiere_donnent_les_memes_chiffres(): void
    {
        $this->versement(['montant' => 100000, 'date_paiement' => '2026-09-24']);
        $this->versement(['montant' => 70000, 'date_paiement' => '2026-09-02']);
        $this->versement(['montant' => 30000, 'date_paiement' => '2026-09-24', 'status' => 'en_attente']);

        $comptable = $this->comptable(['paiements.view', 'comptabilite.dashboard.view']);

        $accueil = $this->actingAs($comptable)->get(route('dashboard'))->viewData('compta');
        $analyse = $this->actingAs($comptable)->getJson(route('esbtp.comptabilite.dashboard.data'))->assertOk()->json();

        foreach (['totalDue', 'totalPaid', 'totalOverdue', 'countToValidate', 'totalValidatedToday', 'countValidatedToday'] as $cle) {
            $this->assertEquals($accueil[$cle], $analyse[$cle], "« {$cle} » diffère entre l'accueil et l'analyse financière");
        }
        $this->assertEquals(100000.0, $analyse['totalValidatedToday']);
    }

    private function comptable(array $permissions): User
    {
        $user = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $user->givePermissionTo(array_merge(['admin.access', 'comptabilite.access'], $permissions));

        return $user;
    }

    private function versement(array $attributs): ESBTPPaiement
    {
        return ESBTPPaiement::factory()->pour($this->inscription)->create(array_merge(['status' => 'validé'], $attributs));
    }
}
