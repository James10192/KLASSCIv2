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
        $this->versement(['montant' => 200000, 'date_paiement' => '2026-07-10']);
        $this->versement(['montant' => 30000, 'date_paiement' => '2026-09-24', 'status' => 'en_attente']);

        $comptable = $this->comptable(['paiements.view', 'paiements.avoir', 'paiements.delete']);

        $reponse = $this->actingAs($comptable)->get(route('dashboard'));

        $reponse->assertOk();
        $this->assertSame(100000.0, $reponse->viewData('encaisseAujourdhui'));
        $this->assertSame(50000.0, $reponse->viewData('encaisseHier'));
        $serie = $reponse->viewData('serieMois');
        $this->assertCount(6, $serie);
        $this->assertSame(200000.0, collect($serie)->firstWhere('mois', '2026-07')['total']);
        $this->assertSame(150000.0, collect($serie)->firstWhere('mois', '2026-09')['total']);

        $reponse->assertSee('Tableau de bord comptable')
            ->assertSee('À traiter')
            ->assertSee('1 paiement à valider')
            ->assertSee('vs hier', false)
            ->assertSee(route('esbtp.paiements.index', ['status' => 'en_attente']), false)
            // Actions sur les derniers paiements, depuis l'accueil.
            ->assertSee('Annuler le versement (avoir', false)
            // Pas le droit : pas de lien vers l'analyse détaillée.
            ->assertDontSee('Analyse détaillée');
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
