<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\FraisConfigurationWriter;
use App\Services\FraisScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;

class FraisConfigurationWriterPreservationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_modifier_audience_et_montants_ne_reinitialise_pas_echeancier_et_delai(): void
    {
        $user = User::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1]);
        $category = ESBTPFraisCategory::factory()->create([
            'audience' => ESBTPFraisCategory::AUDIENCE_TOUS,
        ]);

        $configuration = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'amount' => 60000,
            'amount_affecte' => 60000,
            'amount_reaffecte' => 60000,
            'amount_non_affecte' => 60000,
            'payment_deadline_days' => 47,
            'installments_allowed' => true,
            'max_installments' => 4,
            'early_payment_discount' => 5,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);
        $configuration->audience = ESBTPFraisCategory::AUDIENCE_TOUS;
        $configuration->save();

        app(FraisConfigurationWriter::class)->persistCategories([
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'parcours_id' => null,
            'niveau_id' => $niveau->id,
        ], [
            $category->id => [
                'amount_affecte' => 65000,
                'amount_reaffecte' => 65000,
                'amount_non_affecte' => 65000,
                'audience' => ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
            ],
        ], 'global', null, $user->id);

        $fresh = $configuration->fresh();
        $this->assertSame(47, (int) $fresh->payment_deadline_days);
        $this->assertTrue((bool) $fresh->installments_allowed);
        $this->assertSame(4, (int) $fresh->max_installments);
        $this->assertSame(5.0, (float) $fresh->early_payment_discount);
        $this->assertSame(ESBTPFraisCategory::AUDIENCE_NOUVEAUX, $fresh->audience);
    }

    public function test_un_lot_de_configuration_est_atomique_si_une_categorie_est_invalide(): void
    {
        $user = User::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1]);
        $category = ESBTPFraisCategory::factory()->create();
        $scope = [
            'systeme' => FraisScopeResolver::SYSTEME_BTS,
            'filiere_id' => $filiere->id,
            'parcours_id' => null,
            'niveau_id' => $niveau->id,
        ];

        try {
            app(FraisConfigurationWriter::class)->persistCategories($scope, [
                $category->id => [
                    'amount_affecte' => 60000,
                    'amount_reaffecte' => 60000,
                    'amount_non_affecte' => 60000,
                ],
                999999999 => [
                    'amount_affecte' => 1,
                    'amount_reaffecte' => 1,
                    'amount_non_affecte' => 1,
                ],
            ], 'global', null, $user->id);
            $this->fail('Le lot devait être refusé si une catégorie est introuvable.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('introuvable', $e->getMessage());
        }

        $this->assertFalse(
            ESBTPFraisConfiguration::queryForScope($scope)
                ->where('frais_category_id', $category->id)
                ->exists(),
            'La première ligne ne doit pas rester écrite si une ligne suivante échoue.'
        );
    }
}
