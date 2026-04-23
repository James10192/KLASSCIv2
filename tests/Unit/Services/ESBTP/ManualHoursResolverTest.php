<?php

namespace Tests\Unit\Services\ESBTP;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPAttendanceManualHours;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Services\ESBTP\ManualHoursResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualHoursResolverTest extends TestCase
{
    use RefreshDatabase;

    private ManualHoursResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(ManualHoursResolver::class);
    }

    public function test_snapshot_is_empty_when_no_rows(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();

        $snapshot = $this->resolver->snapshot($etudiant->id, $annee->id, 'semestre1');

        $this->assertFalse($snapshot->hasAnything());
        $this->assertTrue($snapshot->perMatiere->isEmpty());
        $this->assertNull($snapshot->global);
    }

    public function test_snapshot_separates_per_matiere_from_global(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        $perMatiere = ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_presence' => 20,
            'heures_absence_justifiees' => 4,
            'heures_absence_non_justifiees' => 2,
        ]);

        $global = ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_presence' => 0,
            'heures_absence_justifiees' => 8,
            'heures_absence_non_justifiees' => 0,
            'notes' => 'Voyage officiel',
        ]);

        $snapshot = $this->resolver->snapshot($etudiant->id, $annee->id, 'semestre1');

        $this->assertTrue($snapshot->hasAnything());
        $this->assertCount(1, $snapshot->perMatiere);
        $this->assertTrue($snapshot->hasMatiere($matiere->id));
        $this->assertEquals($perMatiere->id, $snapshot->forMatiere($matiere->id)->id);
        $this->assertNotNull($snapshot->global);
        $this->assertEquals($global->id, $snapshot->global->id);
        $this->assertSame([$matiere->id], $snapshot->matiereIdsWithManual());
    }

    public function test_per_matiere_collection_never_contains_global(): void
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        ESBTPAttendanceManualHours::create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre1',
            'heures_absence_justifiees' => 10,
        ]);

        $snapshot = $this->resolver->snapshot($etudiant->id, $annee->id, 'semestre1');

        $this->assertNull($snapshot->forMatiere($matiere->id));
        $this->assertFalse($snapshot->hasMatiere($matiere->id));
        $this->assertTrue($snapshot->perMatiere->isEmpty());
        $this->assertNotNull($snapshot->global);
    }
}
