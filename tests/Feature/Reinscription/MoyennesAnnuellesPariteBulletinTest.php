<?php

namespace Tests\Feature\Reinscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\User;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Services\Reinscription\MoyennesAnnuellesDuBulletin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * La reinscription decide sur la moyenne annuelle que le bulletin IMPRIME.
 * `MoyennesAnnuellesDuBulletin` en tient une seconde ecriture, pour toute une
 * promotion d'un coup : ce test la confronte au bulletin lui-meme, sur un eleve
 * oriente apres un semestre de tronc commun, dans les deux politiques possibles.
 * Le snapshot courant (source de resultats.index et du classement live) doit
 * basculer avec le meme reglage.
 */
class MoyennesAnnuellesPariteBulletinTest extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['id' => 1]);
        SettingsHelper::setOrCreate('bulletin_semester1_weight', '1');
        SettingsHelper::setOrCreate('bulletin_semester2_weight', '2');
    }

    public function test_tronc_commun_inclus_meme_moyenne_que_le_bulletin(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '1');

        [$attendue, $calculee, $snapshot] = $this->lesDeuxMoyennes();

        $this->assertNotNull($attendue, 'Temoin : le bulletin imprime une annuelle quand le S1 vient du tronc commun.');
        $this->assertEqualsWithDelta((18 + 2 * 9.13) / 3, $attendue, 0.01, 'Temoin : le S1 enregistre du tronc commun est bien lu.');
        $this->assertEqualsWithDelta($attendue, $calculee, 0.001);
        $this->assertSame('s1_s2', $snapshot['annual_policy']);
        $this->assertNotEquals(
            $snapshot['semester_snapshots']['semestre2']['effective_total'],
            $snapshot['effective_total']
        );
    }

    public function test_mode_s2_seul_reste_identique_entre_bulletin_reinscription_et_resultats(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '0');

        [$attendue, $calculee, $snapshot] = $this->lesDeuxMoyennes();

        $this->assertNotNull($attendue);
        $this->assertEqualsWithDelta(9.13, $attendue, 0.01);
        $this->assertEqualsWithDelta($attendue, $calculee, 0.001);
        $this->assertSame('specialisation_s2', $snapshot['annual_policy']);
        $this->assertEqualsWithDelta(
            (float) $snapshot['semester_snapshots']['semestre2']['effective_total'],
            (float) $snapshot['effective_total'],
            0.001
        );
        $this->assertContains($snapshot['state'], ['annual_complete', 'annual_complete_no_coefficients']);
    }

    /** @return array{0: float|null, 1: float|null, 2: array} */
    private function lesDeuxMoyennes(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tc = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $spec = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);

        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id, 'filiere_id' => $tcFiliere->id, 'niveau_id' => $niveau->id,
            'classe_id' => $spec->id, 'annee_universitaire_id' => $annee->id,
            'status' => 'active', 'workflow_step' => 'etudiant_cree',
        ]);
        ESBTPInscriptionPhase::create(['inscription_id' => $inscription->id, 'type_phase' => 'tronc_commun', 'classe_id' => $tc->id,
            'filiere_id' => $tcFiliere->id, 'semestre_debut' => 1, 'semestre_fin' => 1, 'is_active' => false]);
        ESBTPInscriptionPhase::create(['inscription_id' => $inscription->id, 'type_phase' => 'specialisation', 'classe_id' => $spec->id,
            'filiere_id' => $specFiliere->id, 'semestre_debut' => 2, 'is_active' => true]);

        $s1 = $this->matiereNotee($etudiant, $tc, $tcFiliere, $niveau, $annee, 'semestre1', 15);
        $s2 = $this->matiereNotee($etudiant, $spec, $specFiliere, $niveau, $annee, 'semestre2', 9);
        $this->seedConfiguredBulletin($etudiant->id, $spec->id, $annee->id, 'semestre2', [$s2->id]);
        // Bulletin de S1 enregistre dans le tronc commun, a 18 alors que les
        // notes vivantes donnent 15 : ce S1 propre a l'etudiant suit au S2.
        $this->seedConfiguredBulletin($etudiant->id, $tc->id, $annee->id, 'semestre1', [$s1->id])
            ->forceFill(['moyenne_generale' => 18, 'note_assiduite' => 0])->save();

        $calculee = app(MoyennesAnnuellesDuBulletin::class)->pour([$inscription->fresh()])[$inscription->id]['moyenne'] ?? null;
        $attendue = app(BulletinService::class)->genererDonneesBulletin($etudiant->id, $spec->id, $annee->id, 'semestre2')['moyenneAnnuelle'];
        $snapshot = app(BtsCurrentResultSnapshotService::class)
            ->getAnnualSnapshot($etudiant->id, $spec->id, $annee->id);

        return [$attendue, $calculee, $snapshot];
    }

    private function matiereNotee(ESBTPEtudiant $etudiant, ESBTPClasse $classe, ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, ESBTPAnneeUniversitaire $annee, string $periode, float $note): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        ESBTPConfigMatiere::create(['matiere_id' => $matiere->id, 'classe_id' => $classe->id, 'annee_universitaire_id' => $annee->id,
            'periode' => $periode, 'config' => ['type' => 'general', 'coefficient' => 2]]);
        ESBTPMatiereCoefficient::create(['matiere_id' => $matiere->id, 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id, 'periode' => $periode, 'coefficient' => 2]);
        $evaluation = ESBTPEvaluation::factory()->create(['matiere_id' => $matiere->id, 'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id, 'periode' => $periode, 'status' => 'published', 'bareme' => 20, 'coefficient' => 1]);
        ESBTPNote::create(['evaluation_id' => $evaluation->id, 'etudiant_id' => $etudiant->id, 'matiere_id' => $matiere->id,
            'classe_id' => $classe->id, 'note' => $note, 'is_absent' => false]);

        return $matiere;
    }
}
