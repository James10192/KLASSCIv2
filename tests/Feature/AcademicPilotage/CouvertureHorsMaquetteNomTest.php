<?php

namespace Tests\Feature\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class CouvertureHorsMaquetteNomTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    public function test_une_matiere_hors_maquette_affiche_son_vrai_nom_meme_archivee(): void
    {
        $this->monterLaClasse();
        $this->etudiantInscrit();

        $matiere = ESBTPMatiere::factory()->create([
            'name' => 'Hydraulique appliquée',
            'unite_enseignement_id' => null,
        ]);
        ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_evaluation' => now()->subDay(),
            'status' => 'completed',
        ]);
        $matiere->delete();

        $resultat = app(AcademicNoteCoverageService::class)->summarize(
            $this->annee->id, 'semestre1', 'BTS', $this->classe->id
        );

        $horsMaquette = collect($resultat['subjects'])->firstWhere('is_orphan', true);
        self::assertNotNull($horsMaquette);
        self::assertSame('Hydraulique appliquée', $horsMaquette['name']);
        self::assertSame('hors_maquette', $horsMaquette['statut']);
    }
}
