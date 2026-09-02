<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Models\User;
use App\Services\Frais\SoldesParSouscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SoldesParSouscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('paiements.view', 'web');
        Cache::flush();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['admin.access', 'paiements.view']);
        $this->actingAs($this->user);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->user->id,
            'statut_etablissement' => ESBTPInscription::STATUT_ETABLISSEMENT_ANCIEN,
        ]);
    }

    public function test_le_reste_suit_la_souscription_pas_le_tarif_catalogue(): void
    {
        $scolarite = ESBTPFraisCategory::factory()->ordre(1)->create([
            'name' => 'Scolarite',
            'default_amount' => 525000,
        ]);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $scolarite->id,
            'amount' => 1400000,
            'created_by' => $this->user->id,
        ]);

        $soldes = app(SoldesParSouscription::class)->pourInscription($this->inscription);
        $ligne = $soldes['categories'][(string) $scolarite->id];

        $this->assertSame(1400000.0, $ligne['total']);
        $this->assertSame(1400000.0, $ligne['remaining']);
        $this->assertSame(1400000.0, $soldes['total_remaining']);
    }

    public function test_un_frais_catalogue_sans_souscription_n_apparait_pas(): void
    {
        $tenue = ESBTPFraisCategory::factory()->ordre(2)->create([
            'name' => 'Tenue',
            'default_amount' => 35000,
            'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
        ]);

        $soldes = app(SoldesParSouscription::class)->pourInscription($this->inscription);

        $this->assertArrayNotHasKey((string) $tenue->id, $soldes['categories']);
        $this->assertSame(0.0, $soldes['total_remaining']);
    }

    public function test_le_paye_compte_les_allocations_pas_la_categorie_designee(): void
    {
        $scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $inscription = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Inscription']);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $scolarite->id,
            'amount' => 200000,
            'created_by' => $this->user->id,
        ]);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $inscription->id,
            'amount' => 50000,
            'created_by' => $this->user->id,
        ]);

        $paiement = ESBTPPaiement::create([
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
            'annee_universitaire_id' => $this->inscription->annee_universitaire_id,
            'frais_category_id' => $scolarite->id,
            'montant' => 80000,
            'date_paiement' => now()->toDateString(),
            'mode_paiement' => 'espèces',
            'status' => 'validé',
            'numero_recu' => 'TEST-'.uniqid(),
            'created_by' => $this->user->id,
        ]);

        ESBTPPaiementAllocation::create([
            'paiement_id' => $paiement->id,
            'frais_category_id' => $scolarite->id,
            'montant' => 30000,
        ]);
        ESBTPPaiementAllocation::create([
            'paiement_id' => $paiement->id,
            'frais_category_id' => $inscription->id,
            'montant' => 50000,
        ]);

        $soldes = app(SoldesParSouscription::class)->pourInscription($this->inscription);

        $this->assertSame(30000.0, $soldes['categories'][(string) $scolarite->id]['paid']);
        $this->assertSame(170000.0, $soldes['categories'][(string) $scolarite->id]['remaining']);
        $this->assertSame(50000.0, $soldes['categories'][(string) $inscription->id]['paid']);
        $this->assertSame(0.0, $soldes['categories'][(string) $inscription->id]['remaining']);
        $this->assertSame(170000.0, $soldes['total_remaining']);
    }

    public function test_un_depot_en_nature_n_entre_pas_dans_le_reste(): void
    {
        $ramette = ESBTPFraisCategory::factory()->ordre(3)->create([
            'name' => 'Ramette',
            'accepts_in_kind' => true,
            'default_amount' => 5000,
        ]);

        ESBTPFraisSubscription::factory()->enNature()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $ramette->id,
            'amount' => 5000,
            'created_by' => $this->user->id,
        ]);

        $soldes = app(SoldesParSouscription::class)->pourInscription($this->inscription);

        $this->assertArrayNotHasKey((string) $ramette->id, $soldes['categories']);
        $this->assertSame(0.0, $soldes['total_remaining']);
    }

    public function test_l_api_soldes_renvoie_le_montant_souscrit(): void
    {
        $scolarite = ESBTPFraisCategory::factory()->ordre(1)->create([
            'default_amount' => 100000,
        ]);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $scolarite->id,
            'amount' => 275000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(route('esbtp.api.etudiants.soldes', [
            'etudiant_id' => $this->inscription->etudiant_id,
            'inscription_id' => $this->inscription->id,
        ]))->assertOk();

        $this->assertEquals(275000, $response->json('total_remaining'));
        $this->assertEquals(275000, $response->json('categories.'.$scolarite->id.'.total'));
    }

    public function test_l_api_categories_ne_liste_que_les_souscriptions(): void
    {
        $scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['default_amount' => 100000]);
        $tenue = ESBTPFraisCategory::factory()->ordre(2)->create([
            'default_amount' => 35000,
            'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
        ]);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $scolarite->id,
            'amount' => 275000,
            'created_by' => $this->user->id,
        ]);

        $ids = collect($this->getJson(route('esbtp.api.frais.categories', [
            'inscription_id' => $this->inscription->id,
        ]))->assertOk()->json())->pluck('id');

        $this->assertTrue($ids->contains($scolarite->id));
        $this->assertFalse($ids->contains($tenue->id));
    }
}
