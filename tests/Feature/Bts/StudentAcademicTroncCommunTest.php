<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Services\ESBTP\BtsStudentAcademicRecords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentAcademicTroncCommunTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_tab_recovers_s1_tc_and_s2_specialisation_without_other_classes_or_years(): void
    {
        [$etudiant, $annee, $tc, $spe, $origine] = $this->setupJourney();

        ESBTPInscriptionPhase::create([
            'inscription_id' => $origine->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tc->id,
            'filiere_id' => $tc->filiere_id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $origine->id,
            'type_phase' => 'specialisation',
            'classe_id' => $spe->id,
            'filiere_id' => $spe->filiere_id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        $s1 = $this->bulletin($etudiant->id, $annee->id, $tc->id, 'semestre1', 9);
        $s2 = $this->bulletin($etudiant->id, $annee->id, $spe->id, 'semestre2', 12);

        // Même étudiant, mais mauvais semestre / classe : aucune contamination.
        $this->bulletin($etudiant->id, $annee->id, $spe->id, 'semestre1', 19);
        $this->bulletin($etudiant->id, $annee->id, $tc->id, 'semestre2', 19);
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create();
        $this->bulletin($etudiant->id, $autreAnnee->id, $tc->id, 'semestre1', 20);

        $matiere = ESBTPMatiere::factory()->create();
        $idS1 = $this->resultat($etudiant->id, $annee->id, $tc->id, $matiere->id, 'semestre1', 9);
        $idS2 = $this->resultat($etudiant->id, $annee->id, $spe->id, $matiere->id, 'semestre2', 12);
        $this->resultat($etudiant->id, $annee->id, $spe->id, $matiere->id, 'semestre1', 19);
        $this->resultat($etudiant->id, $annee->id, $tc->id, $matiere->id, 'semestre2', 19);
        $this->resultat($etudiant->id, $autreAnnee->id, $tc->id, $matiere->id, 'semestre1', 20);

        $records = app(BtsStudentAcademicRecords::class);
        $this->assertSame($tc->id, $records->classMap($origine)['semestre1_classe_id']);
        $this->assertSame($spe->id, $records->classMap($origine)['semestre2_classe_id']);
        $this->assertEqualsCanonicalizing(
            [$s1->id, $s2->id],
            $records->bulletins($etudiant->id, $origine)->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$idS1, $idS2],
            $records->resultats($etudiant->id, $origine)->pluck('id')->all()
        );
    }

    public function test_legacy_dual_inscription_keeps_origin_s1_and_destination_s2(): void
    {
        [$etudiant, $annee, $tc, $spe, $origine] = $this->setupJourney();
        // En modèle legacy, l'inscription d'origine conserve la classe TC.
        $origine->update(['classe_id' => $tc->id, 'filiere_id' => $tc->filiere_id]);
        $specialisation = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id,
            'classe_id' => $spe->id,
            'filiere_id' => $spe->filiere_id,
            'niveau_id' => $origine->niveau_id,
            'type_changement' => 'specialisation',
            'inscription_origine_id' => $origine->id,
        ]);
        $s1 = $this->bulletin($etudiant->id, $annee->id, $tc->id, 'semestre1', 0);
        $s2 = $this->bulletin($etudiant->id, $annee->id, $spe->id, 'semestre2', 12);

        $records = app(BtsStudentAcademicRecords::class);
        $this->assertSame($tc->id, $records->classMap($specialisation)['semestre1_classe_id']);
        $this->assertSame($spe->id, $records->classMap($specialisation)['semestre2_classe_id']);
        $this->assertEqualsCanonicalizing(
            [$s1->id, $s2->id],
            $records->bulletins($etudiant->id, $specialisation)->pluck('id')->all()
        );
        // Une moyenne de 0/20 reste bien une moyenne calculée, pas une absence.
        $this->assertSame(0.0, (float) $records->bulletins($etudiant->id, $specialisation)
            ->firstWhere('id', $s1->id)->moyenne_generale);
    }

    private function setupJourney(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $speFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tc = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $spe = ESBTPClasse::factory()->create([
            'filiere_id' => $speFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'annee_universitaire_id' => $annee->id,
            'classe_id' => $spe->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
        ]);

        return [$etudiant, $annee, $tc, $spe, $inscription];
    }

    private function resultat(int $etudiantId, int $anneeId, int $classeId, int $matiereId, string $periode, float $moyenne): int
    {
        return DB::table('esbtp_resultats')->insertGetId([
            'etudiant_id' => $etudiantId,
            'annee_universitaire_id' => $anneeId,
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'periode' => $periode,
            'moyenne' => $moyenne,
            'coefficient' => 1,
        ]);
    }

    private function bulletin(int $etudiantId, int $anneeId, int $classeId, string $periode, float $moyenne): ESBTPBulletin
    {
        return ESBTPBulletin::factory()->create([
            'etudiant_id' => $etudiantId,
            'annee_universitaire_id' => $anneeId,
            'classe_id' => $classeId,
            'periode' => $periode,
            'moyenne_generale' => $moyenne,
        ]);
    }
}
