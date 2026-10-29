<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FraisBulkLevelConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_preview_level_targets_returns_all_lmd_parcours_for_a_level(): void
    {
        $this->withoutMiddleware();

        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'L1', 'type' => 'Licence', 'year' => 1]);
        $filiere = ESBTPFiliere::factory()->create();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Informatique', 'code' => 'INFO', 'domaine_id' => $domaine->id, 'is_active' => true]);
        $parcoursA = ESBTPLMDParcours::create(['name' => 'Tronc commun', 'code' => 'TC', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);
        $parcoursB = ESBTPLMDParcours::create(['name' => 'IA', 'code' => 'IA', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);

        $response = $this->getJson(route('esbtp.frais.preview-level-targets', [
            'systeme' => 'LMD',
            'niveau_id' => $niveau->id,
            'mode' => 'global',
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true, 'mode' => 'global']);
        $response->assertJsonCount(2, 'targets');
        $response->assertSee($parcoursA->name);
        $response->assertSee($parcoursB->name);
    }

    public function test_bulk_apply_level_configuration_creates_missing_bts_targets_only(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $this->actingAs($user);
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'BTS2', 'type' => 'BTS', 'year' => 2]);
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $filiereA = ESBTPFiliere::factory()->create();
        $filiereB = ESBTPFiliere::factory()->create();
        $category = ESBTPFraisCategory::create([
            'name' => 'Scolarite',
            'code' => 'SCOL-BULK',
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 1000,
            'payment_deadline_days' => 30,
        ]);

        ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $filiereA->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'amount' => 12000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $response = $this->postJson(route('esbtp.frais.apply-level-configuration'), [
            'mode' => 'annual',
            'annee_universitaire_id' => $annee->id,
            'systeme' => 'BTS',
            'niveau_id' => $niveau->id,
            'conflict_strategy' => 'create_missing_only',
            'categories' => [
                $category->id => [
                    'amount_affecte' => 15000,
                    'amount_reaffecte' => 15000,
                    'amount_non_affecte' => 18000,
                    'deadline_days' => 45,
                ],
            ],
            'targets' => [
                [
                    'systeme' => 'BTS',
                    'niveau_id' => $niveau->id,
                    'filiere_id' => $filiereA->id,
                ],
                [
                    'systeme' => 'BTS',
                    'niveau_id' => $niveau->id,
                    'filiere_id' => $filiereB->id,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'mode' => 'annual']);
        $this->assertDatabaseHas('esbtp_frais_configurations', [
            'filiere_id' => $filiereA->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'frais_category_id' => $category->id,
            'amount' => 12000,
        ]);
        $this->assertDatabaseHas('esbtp_frais_configurations', [
            'filiere_id' => $filiereB->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'frais_category_id' => $category->id,
        ]);
    }
}
