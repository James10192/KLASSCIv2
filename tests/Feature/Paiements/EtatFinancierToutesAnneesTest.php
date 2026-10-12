<?php

namespace Tests\Feature\Paiements;

use App\Http\Controllers\Comptabilite\EtatFinancierController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use App\Services\Frais\EtatFinancierParFrais;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Tests\TestCase;

class EtatFinancierToutesAnneesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_toutes_annees_retient_l_inscription_terminee_mais_exclut_l_annulee(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $terminee = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $annee->id,
            'status' => 'terminée',
        ]);
        $annulee = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $annee->id,
            'status' => 'annulée',
        ]);

        // Cible la requête du contrôleur d'export, sans générer un PDF en test.
        $method = new ReflectionMethod(EtatFinancierController::class, 'inscriptions');
        $service = app(EtatFinancierController::class);
        $toutes = $method->invoke($service, null, '', null);
        $courante = $method->invoke($service, ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]), '', null);

        $this->assertTrue($toutes->contains('id', $terminee->id));
        $this->assertFalse($toutes->contains('id', $annulee->id));
        $this->assertFalse($courante->contains('id', $terminee->id));
    }

    public function test_un_reliquat_compte_une_seule_fois_sans_masquer_le_restant_du(): void
    {
        $source = ESBTPInscription::factory()->create(['status' => 'terminée']);
        $destination = ESBTPInscription::factory()->create();
        $frais = ESBTPFraisCategory::factory()->create();
        $souscription = ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $source->id,
            'frais_category_id' => $frais->id,
            'amount' => 100000,
            'is_active' => true,
        ]);

        ESBTPReliquatDetail::create([
            'inscription_source_id' => $source->id,
            'inscription_destination_id' => $destination->id,
            'frais_subscription_id' => $souscription->id,
            'montant_attendu' => 100000,
            'montant_paye' => 0,
            'montant_reliquat' => 60000,
            'montant_regle' => 20000,
            'statut' => 'partiellement_regle',
            'date_creation' => now(),
            'created_by' => User::factory()->create()->id,
        ]);

        $lignes = app(EtatFinancierParFrais::class)->construire(
            ESBTPInscription::query()->whereKey($source->id)->with(['etudiant','classe','anneeUniversitaire'])->get(),
            $frais->id
        );

        // 40 000 non reportés + 40 000 restants sur le reliquat ;
        // jamais 100 000 sur la source PLUS 60 000 au report.
        $this->assertCount(2, $lignes);
        $this->assertEquals(100000, $lignes->sum('du'));
        $this->assertEquals(20000, $lignes->sum('paye'));
        $this->assertEquals(80000, $lignes->sum('reste'));
        $this->assertTrue($lignes->contains(fn ($l) => str_contains($l['frais'], 'reliquat reporté')));
    }
}
