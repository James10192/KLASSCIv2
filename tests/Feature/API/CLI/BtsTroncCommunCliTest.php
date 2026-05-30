<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIBtsTroncCommunController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BtsTroncCommunCliTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cli_endpoints_return_phase_based_diagnostics(): void
    {
        [$user, $inscription, $etudiant, $sourceClasse] = $this->makeFixture();
        $controller = app(CLIBtsTroncCommunController::class);
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => new class($user) {
            public function __construct(private User $user) {}
            public function tokenCan(string $ability): bool { return $ability === 'cli:read'; }
        });

        $diagnose = $controller->diagnoseInscription($request, $inscription->id);

        $journeyRequest = Request::create('/?annee_universitaire_id=' . $inscription->annee_universitaire_id, 'GET');
        $journeyRequest->setUserResolver($request->getUserResolver());
        $journey = $controller->studentJourney($journeyRequest, $etudiant->id);

        $checkRequest = Request::create('/', 'GET');
        $checkRequest->setUserResolver($request->getUserResolver());
        $check = $controller->classOrientationCheck($checkRequest, $sourceClasse->id);

        $this->assertSame(200, $diagnose->getStatusCode());
        $this->assertSame('phase_based', $diagnose->getData(true)['data']['source_model']);
        $this->assertSame('specialisation', $diagnose->getData(true)['data']['current_phase']['type_phase']);
        $this->assertCount(2, $journey->getData(true)['data']['timeline']);
        $this->assertSame('ok', $check->getData(true)['data']['status']);
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $sourceClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $targetClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $target = ESBTPClasseOrientationTarget::create([
            'source_classe_id' => $sourceClasse->id,
            'target_classe_id' => $targetClasse->id,
            'semestre_activation' => 2,
            'is_active' => true,
        ]);

        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $targetClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $sourceClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
            'orientation_target_id' => $target->id,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $targetClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
            'orientation_target_id' => $target->id,
        ]);

        return [$user, $inscription, $etudiant, $sourceClasse];
    }
}
