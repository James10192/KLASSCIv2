<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\Paiements\EtatRecuPaiement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EtatRecuPaiementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_le_statut_d_affectation_apparait_sur_le_recu(): void
    {
        $inscription = $this->creerInscription(['affectation_status' => 'non_affecté']);
        $paiement = ESBTPPaiement::factory()->pour($inscription)->create();

        $etat = app(EtatRecuPaiement::class)->construire($paiement->fresh(['inscription', 'allocations']));

        $this->assertSame('Non affecté', $etat['affectationLabel']);
    }

    public function test_un_frais_depose_en_nature_est_coche_et_signale(): void
    {
        $inscription = $this->creerInscription();
        $ramette = ESBTPFraisCategory::factory()->create(['name' => 'Ramette']);
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $inscription->id,
            'frais_category_id' => $ramette->id,
            'amount' => 3000,
            'satisfied_in_kind' => true,
            'is_active' => true,
        ]);
        $paiement = ESBTPPaiement::factory()->pour($inscription)->create();

        $etat = app(EtatRecuPaiement::class)->construire($paiement->fresh(['inscription', 'allocations']));
        $ligne = $etat['lignes']->firstWhere('name', 'Ramette');

        $this->assertNotNull($ligne);
        $this->assertTrue($ligne['checked']);
        $this->assertTrue($ligne['in_kind']);
    }

    public function test_les_versements_avant_et_apres_sont_separes(): void
    {
        $inscription = $this->creerInscription();
        $avant = ESBTPPaiement::factory()->pour($inscription)->create([
            'date_paiement' => now()->subDays(10)->toDateString(),
            'montant' => 50000,
        ]);
        $courant = ESBTPPaiement::factory()->pour($inscription)->create([
            'date_paiement' => now()->toDateString(),
            'montant' => 100000,
        ]);
        $apres = ESBTPPaiement::factory()->pour($inscription)->create([
            'date_paiement' => now()->addDays(5)->toDateString(),
            'montant' => 20000,
        ]);

        $etat = app(EtatRecuPaiement::class)->construire($courant->fresh(['inscription', 'allocations']));

        $this->assertTrue($etat['versementsAvant']->contains('id', $avant->id));
        $this->assertTrue($etat['versementsApres']->contains('id', $apres->id));
        $this->assertFalse($etat['versementsAvant']->contains('id', $courant->id));
        $this->assertFalse($etat['versementsApres']->contains('id', $courant->id));
    }

    /**
     * @param  array<string, mixed>  $attributs
     */
    private function creerInscription(array $attributs = []): ESBTPInscription
    {
        $classe = ESBTPClasse::factory()->create();

        return ESBTPInscription::factory()->create(array_merge([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
        ], $attributs));
    }
}
