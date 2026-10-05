<?php

namespace Tests\Feature\Frais;

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
use App\Services\Frais\SouscriptionsObligatoiresManquantes;
use App\Services\FraisScopeResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LmdInscriptionYearScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lmd_scope_uses_the_inscription_year_instead_of_the_reused_class_year(): void
    {
        [$inscription, $configuration, $anneeInscription] = $this->makeLmdInscriptionWithAnnualFee();

        $scope = app(FraisScopeResolver::class)->resolveForInscription($inscription);
        $fee = app(ApplicableFraisResolver::class)
            ->resolveMandatoryFeesForInscription($inscription)
            ->first();

        $this->assertSame(FraisScopeResolver::SYSTEME_LMD, $scope['systeme']);
        $this->assertSame($anneeInscription->id, $scope['annee_universitaire_id']);
        $this->assertNull($scope['filiere_id']);
        $this->assertSame($configuration->parcours_id, $scope['parcours_id']);
        $this->assertNotNull($fee);
        $this->assertTrue($configuration->is($fee['configuration']));
        $this->assertSame(350000.0, $fee['amount']);
    }

    public function test_regeneration_creates_the_lmd_subscription_from_the_inscription_year_barème(): void
    {
        [$inscription, $configuration] = $this->makeLmdInscriptionWithAnnualFee();
        $this->actingAs(User::findOrFail($inscription->created_by));

        $service = app(SouscriptionsObligatoiresManquantes::class);
        $preview = $service->executer(false, null, [$inscription->id], true);

        $this->assertSame(1, $preview['total_ajouter']);
        $this->assertSame(350000.0, (float) $preview['lignes'][0]['montant']);

        $service->executer(true, null, [$inscription->id], true);

        $subscription = ESBTPFraisSubscription::query()
            ->where('inscription_id', $inscription->id)
            ->where('frais_category_id', $configuration->frais_category_id)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertSame(350000.0, (float) $subscription->amount);
        $this->assertTrue($configuration->is($subscription->fresh()->frais_configuration));
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPFraisConfiguration, 2: ESBTPAnneeUniversitaire}
     */
    private function makeLmdInscriptionWithAnnualFee(): array
    {
        $user = User::factory()->create();
        $ancienneAnnee = ESBTPAnneeUniversitaire::factory()->create();
        $anneeInscription = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create([
            'code' => 'L1',
            'type' => 'Licence',
            'year' => 1,
        ]);
        $filiere = ESBTPFiliere::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $domaine = ESBTPLMDDomaine::create([
            'name' => 'Sciences et techniques',
            'code' => 'ST',
            'is_active' => true,
        ]);
        $mention = ESBTPLMDMention::create([
            'name' => 'Travaux Publics',
            'code' => 'TP',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $parcours = ESBTPLMDParcours::create([
            'name' => 'Licence Travaux Publics',
            'code' => 'LTP',
            'mention_id' => $mention->id,
            'filiere_id' => $filiere->id,
            'is_active' => true,
        ]);

        $classe = ESBTPClasse::factory()->create([
            'name' => 'L1 Licence1 Travaux Publics',
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            // La classe est réutilisée : son année historique ne doit pas
            // décider du barème de la nouvelle inscription.
            'annee_universitaire_id' => $ancienneAnnee->id,
            'systeme_academique' => FraisScopeResolver::SYSTEME_LMD,
            'parcours_id' => $parcours->id,
        ]);

        $category = ESBTPFraisCategory::create([
            'name' => 'Scolarité',
            'code' => 'SCOLARITE-LMD-ANNUELLE',
            'is_mandatory' => true,
            'is_active' => true,
            'category_type' => 'academic',
            'sort_order' => 1,
            'default_amount' => 0,
            'payment_deadline_days' => 30,
        ]);

        $configuration = ESBTPFraisConfiguration::create([
            'frais_category_id' => $category->id,
            'systeme_academique' => FraisScopeResolver::SYSTEME_LMD,
            'filiere_id' => null,
            'parcours_id' => $parcours->id,
            'niveau_id' => $niveau->id,
            'annee_universitaire_id' => $anneeInscription->id,
            'amount' => 350000,
            'amount_affecte' => 350000,
            'amount_reaffecte' => 350000,
            'amount_non_affecte' => 350000,
            'payment_deadline_days' => 30,
            'effective_date' => now()->toDateString(),
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $inscription = ESBTPInscription::create([
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $anneeInscription->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'date_inscription' => now()->toDateString(),
            'type_inscription' => 'première_inscription',
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
            'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
            'montant_scolarite' => 350000,
            'frais_inscription' => 0,
            'created_by' => $user->id,
        ]);

        return [$inscription, $configuration, $anneeInscription];
    }
}
