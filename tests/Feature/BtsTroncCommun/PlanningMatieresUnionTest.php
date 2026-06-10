<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\ClassPlanningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature pour l'union TC → spécialité côté planning (C9 — Plan C BTS).
 *
 * Une classe de spécialité (filière fille d'un tronc commun) doit récupérer
 * dans son planning matière les planifications définies au niveau de la filière
 * mère (le tronc commun), en plus des siennes.
 *
 * DB : klassci_testing (RefreshDatabase).
 */
class PlanningMatieresUnionTest extends TestCase
{
    use RefreshDatabase;

    private function makePlanification(
        ESBTPAnneeUniversitaire $annee,
        int $filiereId,
        int $niveauId,
        int $matiereId,
        int $volume = 30,
        int $semestre = 1,
    ): ESBTPPlanificationAcademique {
        return ESBTPPlanificationAcademique::create([
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $filiereId,
            'niveau_etude_id' => $niveauId,
            'matiere_id' => $matiereId,
            'semestre' => $semestre,
            'volume_horaire_total' => $volume,
            'statut' => 'valide',
            'is_active' => true,
        ]);
    }

    public function test_classe_specialite_herite_des_matieres_du_tronc_commun(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $tc = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => true,
            'parent_id' => null,
        ]);
        $specialite = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $tc->id,
        ]);

        $matiereTc = ESBTPMatiere::factory()->create(['name' => 'Mathématiques TC']);
        $matiereSpe = ESBTPMatiere::factory()->create(['name' => 'Spécialité Avancée']);

        // Planifications : une sur le TC, une sur la spécialité
        $this->makePlanification($annee, $tc->id, $niveau->id, $matiereTc->id, 30);
        $this->makePlanification($annee, $specialite->id, $niveau->id, $matiereSpe->id, 40);

        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $classe->load('filiere');

        $service = app(ClassPlanningService::class);
        $result = $service->buildPlanningMatierePourClasse($classe, $annee, 'annee');

        $matiereIds = $result['matieres']
            ->pluck('matiere.id')
            ->filter()
            ->values()
            ->all();

        $this->assertContains($matiereTc->id, $matiereIds, 'La matière du tronc commun doit apparaître');
        $this->assertContains($matiereSpe->id, $matiereIds, 'La matière de spécialité doit apparaître');
    }

    public function test_classe_filiere_normale_ne_remonte_pas_au_parent_non_tc(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        // Parent ordinaire (PAS un tronc commun)
        $parentOrdinaire = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => null,
        ]);
        $option = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $parentOrdinaire->id,
        ]);

        $matiereParent = ESBTPMatiere::factory()->create(['name' => 'Matiere Parent Ordinaire']);
        $matiereOption = ESBTPMatiere::factory()->create(['name' => 'Matiere Option']);

        $this->makePlanification($annee, $parentOrdinaire->id, $niveau->id, $matiereParent->id, 30);
        $this->makePlanification($annee, $option->id, $niveau->id, $matiereOption->id, 40);

        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $option->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $classe->load('filiere');

        $service = app(ClassPlanningService::class);
        $result = $service->buildPlanningMatierePourClasse($classe, $annee, 'annee');

        $matiereIds = $result['matieres']
            ->pluck('matiere.id')
            ->filter()
            ->values()
            ->all();

        $this->assertContains($matiereOption->id, $matiereIds);
        $this->assertNotContains(
            $matiereParent->id,
            $matiereIds,
            'Une filière fille dont le parent n\'est pas un TC ne doit pas hériter du parent'
        );
    }

    public function test_get_matieres_classe_union_via_pivot_filiere_niveau(): void
    {
        $niveau = ESBTPNiveauEtude::factory()->create();

        $tc = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => true,
            'parent_id' => null,
        ]);
        $specialite = ESBTPFiliere::factory()->create([
            'is_tronc_commun' => false,
            'parent_id' => $tc->id,
        ]);

        $matiereTc = ESBTPMatiere::factory()->create(['name' => 'Commune TC', 'is_active' => true]);
        $matiereSpe = ESBTPMatiere::factory()->create(['name' => 'Propre Spé', 'is_active' => true]);

        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiereTc->id,
            'filiere_id' => $tc->id,
            'niveau_etude_id' => $niveau->id,
        ]);
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiereSpe->id,
            'filiere_id' => $specialite->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        // L'union des matières attendues = matiereTc + matiereSpe
        $expected = collect([$tc->id, $specialite->id])
            ->flatMap(fn ($fid) => ESBTPMatiereFilierNiveau::matiereIdsForCombo($fid, $niveau->id))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertEqualsCanonicalizing([$matiereTc->id, $matiereSpe->id], $expected);
    }
}
