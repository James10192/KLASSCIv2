<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ESBTPBulletinService;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultatMatiere;
use App\Models\ESBTPAbsence;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ESBTPBulletinServiceTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPBulletinService $bulletinService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bulletinService = new ESBTPBulletinService();
    }

    public function test_calculer_moyenne_matiere()
    {
        // Création des données de test
        $etudiant = ESBTPEtudiant::factory()->create();
        $matiere = ESBTPMatiere::factory()->create(['coefficient' => 2]);
        $evaluation1 = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'coefficient' => 1,
            'bareme' => 20,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);
        $evaluation2 = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'coefficient' => 2,
            'bareme' => 20,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation1->id,
            'etudiant_id' => $etudiant->id,
            'valeur' => 15
        ]);
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation2->id,
            'etudiant_id' => $etudiant->id,
            'valeur' => 12
        ]);

        // Test du calcul de la moyenne
        $moyenne = $this->bulletinService->calculerMoyenneMatiere(
            $etudiant,
            $matiere,
            'semestre1',
            1
        );

        // (15 * 1 + 12 * 2) / (1 + 2) = 13
        $this->assertEquals(13.00, $moyenne);
    }

    public function test_calculer_rang_matiere()
    {
        // Création des données de test
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        $etudiant1 = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);
        $etudiant2 = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);
        $etudiant3 = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);

        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant1->id,
            'valeur' => 15
        ]);
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant2->id,
            'valeur' => 12
        ]);
        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant3->id,
            'valeur' => 18
        ]);

        // Test du calcul des rangs
        $rangs = $this->bulletinService->calculerRangMatiere(
            $matiere,
            $classe,
            'semestre1',
            1
        );

        $this->assertEquals(2, $rangs[$etudiant1->id]);
        $this->assertEquals(3, $rangs[$etudiant2->id]);
        $this->assertEquals(1, $rangs[$etudiant3->id]);
    }

    public function test_calculer_moyenne_generale()
    {
        // Création des données de test
        $resultats = collect([
            new ESBTPResultatMatiere([
                'moyenne' => 15,
                'coefficient' => 2
            ]),
            new ESBTPResultatMatiere([
                'moyenne' => 12,
                'coefficient' => 1
            ]),
            new ESBTPResultatMatiere([
                'moyenne' => 18,
                'coefficient' => 3
            ])
        ]);

        // Test du calcul de la moyenne générale
        $moyenne = $this->bulletinService->calculerMoyenneGenerale($resultats);

        // (15 * 2 + 12 * 1 + 18 * 3) / (2 + 1 + 3) = 16
        $this->assertEquals(16.00, $moyenne);
    }

    public function test_calculer_mention()
    {
        $this->assertEquals('Très Bien', $this->bulletinService->calculerMention(16));
        $this->assertEquals('Bien', $this->bulletinService->calculerMention(14));
        $this->assertEquals('Assez Bien', $this->bulletinService->calculerMention(12));
        $this->assertEquals('Passable', $this->bulletinService->calculerMention(10));
        $this->assertEquals('Insuffisant', $this->bulletinService->calculerMention(8));
    }

    public function test_generer_bulletin()
    {
        // Création des données de test
        $classe = ESBTPClasse::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);
        $matiere = ESBTPMatiere::factory()->create(['coefficient' => 2]);
        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'valeur' => 15
        ]);

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1,
            'nombre_heures' => 4,
            'est_justifiee' => true
        ]);

        // Test de la génération du bulletin
        $bulletin = $this->bulletinService->genererBulletin(
            $etudiant,
            $classe,
            'semestre1',
            1
        );

        $this->assertInstanceOf(ESBTPBulletin::class, $bulletin);
        $this->assertEquals($etudiant->id, $bulletin->etudiant_id);
        $this->assertEquals($classe->id, $bulletin->classe_id);
        $this->assertEquals('semestre1', $bulletin->periode);
        $this->assertEquals(1, $bulletin->annee_universitaire_id);
        $this->assertEquals(15.00, $bulletin->moyenne_generale);
        $this->assertEquals('Bien', $bulletin->mention);
        $this->assertEquals(4, $bulletin->absences_justifiees);
        $this->assertEquals(0, $bulletin->absences_non_justifiees);
    }
}
