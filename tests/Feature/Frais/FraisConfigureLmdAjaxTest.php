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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FraisConfigureLmdAjaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_lmd_ajax_categories_endpoint_accepts_parcours_scope_without_explicit_systeme(): void
    {
        $this->withoutMiddleware();

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'L1', 'type' => 'Licence', 'year' => 1]);
        $filiere = ESBTPFiliere::factory()->create();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Physique', 'code' => 'PHY', 'domaine_id' => $domaine->id, 'is_active' => true]);
        $parcours = ESBTPLMDParcours::create(['name' => 'Tronc commun', 'code' => 'TCP', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);
        $category = ESBTPFraisCategory::create([
            'name' => 'Scolarite',
            'code' => 'SCOLARITE',
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 1000,
            'payment_deadline_days' => 30,
        ]);

        ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => 'LMD',
            'parcours_id' => $parcours->id,
            'niveau_id' => $niveau->id,
            'amount' => 18000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get(route('esbtp.frais.get-categories', [
            'filiere_id' => $parcours->id,
            'niveau_id' => $niveau->id,
            'type' => 'mandatory',
        ]));

        $response->assertOk();
        $response->assertJson(['success' => true, 'systeme' => 'LMD']);
        $response->assertSee('Scolarite', false);
    }
}
