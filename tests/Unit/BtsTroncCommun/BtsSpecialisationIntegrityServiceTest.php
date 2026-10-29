<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsSpecialisationIntegrityService;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BtsSpecialisationIntegrityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_diagnoses_and_repairs_a_missing_primary_pointer(): void
    {
        [$inscription, $phase] = $this->makeFixture();
        $service = app(BtsSpecialisationIntegrityService::class);

        $diagnosis = $service->diagnose($inscription);

        $this->assertSame('repairable_missing_primary_pointer', $diagnosis['status']);
        $this->assertTrue($diagnosis['repairable']);

        $actor = User::factory()->create();
        $result = $service->repairPrimaryPointer($inscription, $actor->id);

        $this->assertSame('coherent', $result['after']['status']);
        $this->assertSame($phase->classe_id, $inscription->fresh()->classe_id);
        $this->assertSame($phase->filiere_id, $inscription->fresh()->filiere_id);
        $this->assertSame(ESBTPInscription::DEFAULT_AFFECTATION_STATUS, $inscription->fresh()->affectation_status);
    }

    public function test_it_refuses_to_repair_a_conflicting_filiere(): void
    {
        [$inscription] = $this->makeFixture();
        $conflictingFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $inscription->update(['filiere_id' => $conflictingFiliere->id]);
        $service = app(BtsSpecialisationIntegrityService::class);

        $diagnosis = $service->diagnose($inscription->fresh());

        $this->assertSame('conflicting_primary_pointer', $diagnosis['status']);
        $this->assertFalse($diagnosis['repairable']);

        $this->expectException(\InvalidArgumentException::class);
        $service->repairPrimaryPointer($inscription, User::factory()->create()->id);
    }

    public function test_it_refuses_to_repair_multiple_active_specialisations(): void
    {
        [$inscription, $phase] = $this->makeFixture();
        ESBTPInscriptionPhase::factory()->create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $phase->classe_id,
            'filiere_id' => $phase->filiere_id,
            'is_active' => true,
        ]);

        $diagnosis = app(BtsSpecialisationIntegrityService::class)->diagnose($inscription->fresh());

        $this->assertSame('multiple_active_specialisations', $diagnosis['status']);
        $this->assertFalse($diagnosis['repairable']);
    }

    private function makeFixture(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true]);
        $specialisation = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $tcFiliere->id,
        ]);
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specialisationClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specialisation->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
            'annee_universitaire_id' => $annee->id,
            'niveau_id' => $niveau->id,
            'classe_id' => null,
            'filiere_id' => $specialisation->id,
            'affectation_status' => 'non_affecté',
        ]);
        ESBTPInscriptionPhase::factory()->create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_TRONC_COMMUN,
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'is_active' => false,
        ]);
        $phase = ESBTPInscriptionPhase::factory()->create([
            'inscription_id' => $inscription->id,
            'type_phase' => ESBTPInscriptionPhase::TYPE_SPECIALISATION,
            'classe_id' => $specialisationClasse->id,
            'filiere_id' => $specialisation->id,
            'is_active' => true,
        ]);

        return [$inscription, $phase];
    }
}
