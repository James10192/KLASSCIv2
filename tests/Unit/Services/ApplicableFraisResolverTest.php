<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\ApplicableFraisResolver;
use App\Services\FraisScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApplicableFraisResolverTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_resolves_bts_and_lmd_configurations_with_distinct_scopes(): void
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'L1', 'type' => 'Licence', 'year' => 1]);
        $filiere = ESBTPFiliere::factory()->create();
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

        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Genie Civil', 'code' => 'GC', 'domaine_id' => $domaine->id, 'is_active' => true]);
        $parcours = ESBTPLMDParcours::create(['name' => 'Tronc commun', 'code' => 'TCGC', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);

        $btsConfig = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => null,
            'amount' => 15000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $lmdConfig = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => 'LMD',
            'filiere_id' => $filiere->id,
            'parcours_id' => $parcours->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => null,
            'amount' => 25000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->assertTrue($btsConfig->is($category->fresh()->getApplicableRule($filiere->id, $niveau->id)));

        $resolvedLmd = ESBTPFraisConfiguration::getApplicableForScope($category->id, [
            'systeme' => 'LMD',
            'parcours_id' => $parcours->id,
            'niveau_id' => $niveau->id,
        ]);

        $this->assertNotNull($resolvedLmd);
        $this->assertTrue($lmdConfig->is($resolvedLmd));
    }

    public function test_it_resolves_lmd_subscription_configuration_from_inscription_context(): void
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'L2', 'type' => 'Licence', 'year' => 2]);
        $filiere = ESBTPFiliere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();
        $category = ESBTPFraisCategory::create([
            'name' => 'Inscription',
            'code' => 'INSC',
            'is_mandatory' => false,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 5000,
            'payment_deadline_days' => 15,
        ]);

        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Informatique', 'code' => 'INFO', 'domaine_id' => $domaine->id, 'is_active' => true]);
        $parcours = ESBTPLMDParcours::create(['name' => 'IA', 'code' => 'IA', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'LMD',
            'parcours_id' => $parcours->id,
        ]);

        $config = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => 'LMD',
            'filiere_id' => $filiere->id,
            'parcours_id' => $parcours->id,
            'niveau_id' => $niveau->id,
            'amount' => 22000,
            'payment_deadline_days' => 20,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $inscription = ESBTPInscription::create([
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'date_inscription' => now()->toDateString(),
            'type_inscription' => 'première_inscription',
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            'montant_scolarite' => 22000,
            'frais_inscription' => 0,
            'created_by' => $user->id,
        ]);

        $subscription = ESBTPFraisSubscription::create([
            'inscription_id' => $inscription->id,
            'frais_category_id' => $category->id,
            'amount' => 22000,
            'is_active' => true,
            'subscribed_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertTrue($config->is($subscription->fresh()->frais_configuration));
    }

    public function test_scope_resolver_marks_lmd_class_with_parcours_context(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'L3', 'type' => 'Licence', 'year' => 3]);
        $filiere = ESBTPFiliere::factory()->create();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $mention = ESBTPLMDMention::create(['name' => 'Maths', 'code' => 'MTH', 'domaine_id' => $domaine->id, 'is_active' => true]);
        $parcours = ESBTPLMDParcours::create(['name' => 'Tronc commun', 'code' => 'TCM', 'mention_id' => $mention->id, 'filiere_id' => $filiere->id, 'is_active' => true]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'LMD',
            'parcours_id' => $parcours->id,
        ]);

        $scope = app(FraisScopeResolver::class)->resolveForClasse($classe);

        $this->assertSame('LMD', $scope['systeme']);
        $this->assertSame($parcours->id, $scope['parcours_id']);
        $this->assertSame('Tronc commun', $scope['parcours']);
        $this->assertSame('Maths', $scope['mention']);
    }
}
