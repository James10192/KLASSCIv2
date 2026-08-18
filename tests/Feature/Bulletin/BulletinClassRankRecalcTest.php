<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinClassRankRecalcTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_generation_recalculates_the_whole_class(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false]);
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $first = $this->makeStudentBulletin($classe, $annee, 16.0, 0.13);
        $second = $this->makeStudentBulletin($classe, $annee, 12.0, 0.00);

        $service = app(BulletinService::class);
        $service->calculerRang($first);
        $service->calculerRangsPourClasse($classe->id, $annee->id, 'semestre2');

        $this->assertSame(1, (int) $first->fresh()->rang);
        $this->assertSame(2, (int) $second->fresh()->rang);
        $this->assertSame(2, (int) $first->fresh()->effectif_classe);
    }

    private function makeStudentBulletin(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, float $moyenne, float $assiduite): ESBTPBulletin
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        return ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => 'semestre2',
            'moyenne_generale' => $moyenne,
            'note_assiduite' => $assiduite,
        ]);
    }
}
