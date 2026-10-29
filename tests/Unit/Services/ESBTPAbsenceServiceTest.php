<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ESBTPAbsenceService;
use App\Models\ESBTPAbsence;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPClasse;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ESBTPAbsenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAbsenceService $absenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->absenceService = new ESBTPAbsenceService();
    }

    public function test_enregistrer_absence()
    {
        // Création des données de test
        $etudiant = ESBTPEtudiant::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();

        $data = [
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'date_absence' => '2024-03-15',
            'nombre_heures' => 4,
            'motif' => 'Maladie',
            'est_justifiee' => true,
            'justification' => 'Certificat médical',
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ];

        // Test de l'enregistrement
        $absence = $this->absenceService->enregistrerAbsence($data);

        $this->assertInstanceOf(ESBTPAbsence::class, $absence);
        $this->assertEquals($etudiant->id, $absence->etudiant_id);
        $this->assertEquals($matiere->id, $absence->matiere_id);
        $this->assertEquals('2024-03-15', $absence->date_absence);
        $this->assertEquals(4, $absence->nombre_heures);
        $this->assertEquals('Maladie', $absence->motif);
        $this->assertTrue($absence->est_justifiee);
        $this->assertEquals('Certificat médical', $absence->justification);
        $this->assertEquals('semestre1', $absence->periode);
        $this->assertEquals(1, $absence->annee_universitaire_id);
    }

    public function test_justifier_absence()
    {
        // Création des données de test
        $absence = ESBTPAbsence::factory()->create([
            'est_justifiee' => false,
            'justification' => null,
            'date_justification' => null
        ]);

        // Test de la justification
        $absenceJustifiee = $this->absenceService->justifierAbsence($absence, 'Certificat médical');

        $this->assertTrue($absenceJustifiee->est_justifiee);
        $this->assertEquals('Certificat médical', $absenceJustifiee->justification);
        $this->assertNotNull($absenceJustifiee->date_justification);
    }

    public function test_annuler_justification()
    {
        // Création des données de test
        $absence = ESBTPAbsence::factory()->create([
            'est_justifiee' => true,
            'justification' => 'Certificat médical',
            'date_justification' => now()
        ]);

        // Test de l'annulation
        $absenceAnnulee = $this->absenceService->annulerJustification($absence);

        $this->assertFalse($absenceAnnulee->est_justifiee);
        $this->assertNull($absenceAnnulee->justification);
        $this->assertNull($absenceAnnulee->date_justification);
    }

    public function test_calculer_total_absences()
    {
        // Création des données de test
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'nombre_heures' => 4,
            'est_justifiee' => true,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'nombre_heures' => 2,
            'est_justifiee' => false,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        // Test du calcul
        $totaux = $this->absenceService->calculerTotalAbsences($etudiant, 'semestre1', 1);

        $this->assertEquals(6, $totaux['total']);
        $this->assertEquals(4, $totaux['justifiees']);
        $this->assertEquals(2, $totaux['non_justifiees']);
    }

    public function test_calculer_pourcentage_absences_par_matiere()
    {
        // Création des données de test
        $etudiant = ESBTPEtudiant::factory()->create();
        $matiere = ESBTPMatiere::factory()->create([
            'heures_cm' => 20,
            'heures_td' => 10,
            'heures_tp' => 10
        ]);

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'nombre_heures' => 8,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        // Test du calcul
        $pourcentage = $this->absenceService->calculerPourcentageAbsencesParMatiere(
            $etudiant,
            $matiere,
            'semestre1',
            1
        );

        // 8 heures d'absence sur 40 heures total = 20%
        $this->assertEquals(20.00, $pourcentage);
    }

    public function test_generer_rapport_classe()
    {
        // Création des données de test
        $classe = ESBTPClasse::factory()->create();
        $matiere1 = ESBTPMatiere::factory()->create(['name' => 'Mathématiques']);
        $matiere2 = ESBTPMatiere::factory()->create(['name' => 'Physique']);
        $etudiant = ESBTPEtudiant::factory()->create(['classe_id' => $classe->id]);

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere1->id,
            'nombre_heures' => 4,
            'est_justifiee' => true,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        ESBTPAbsence::factory()->create([
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere2->id,
            'nombre_heures' => 2,
            'est_justifiee' => false,
            'periode' => 'semestre1',
            'annee_universitaire_id' => 1
        ]);

        // Test de la génération du rapport
        $rapport = $this->absenceService->genererRapportClasse($classe, 'semestre1', 1);

        $this->assertIsArray($rapport);
        $this->assertCount(1, $rapport);
        $this->assertEquals($etudiant->nom_complet, $rapport[0]['etudiant']);
        $this->assertEquals($etudiant->matricule, $rapport[0]['matricule']);
        $this->assertEquals(6, $rapport[0]['total_absences']);
        $this->assertEquals(4, $rapport[0]['absences_justifiees']);
        $this->assertEquals(2, $rapport[0]['absences_non_justifiees']);
        $this->assertArrayHasKey('Mathématiques', $rapport[0]['matieres']);
        $this->assertArrayHasKey('Physique', $rapport[0]['matieres']);
    }
}
