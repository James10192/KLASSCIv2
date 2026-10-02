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
use App\Services\Reinscription\MoyennesAnnuellesDuBulletin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * La reinscription decide sur la moyenne annuelle que le bulletin IMPRIME.
 * `MoyennesAnnuellesDuBulletin` en tient une seconde ecriture, pour toute une
 * promotion d'un coup : ce test la confronte au bulletin lui-meme, sur un eleve
 * oriente apres un semestre de tronc commun, reglage
 * `tronc_commun_mga_include_s1` actif puis coupe.
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

        [$attendue, $calculee] = $this->lesDeuxMoyennes();

        $this->assertNotNull($attendue, 'Temoin : le bulletin imprime une annuelle quand le S1 vient du tronc commun.');
        $this->assertEqualsWithDelta((18 + 2 * 9.13) / 3, $attendue, 0.01, 'Temoin : le S1 enregistre du tronc commun est bien lu.');
        $this->assertEqualsWithDelta($attendue, $calculee, 0.001);
    }

    public function test_tronc_commun_exclu_meme_moyenne_que_le_bulletin(): void
    {
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '0');

        [$attendue, $calculee] = $this->lesDeuxMoyennes();

        // Reglage coupe : le bulletin enregistre du tronc commun n'est plus lu,
        // le S1 vient du calcul courant.
        $this->assertNotNull($attendue);
        $this->assertEqualsWithDelta($attendue, $calculee, 0.001);
    }

    /** @return array{0: float|null, 1: float|null} */
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
        // notes vivantes donnent 15 : c'est lui que le reglage fait lire, ou non.
        $this->seedConfiguredBulletin($etudiant->id, $tc->id, $annee->id, 'semestre1', [$s1->id])
            ->forceFill(['moyenne_generale' => 18, 'note_assiduite' => 0])->save();

        $calculee = app(MoyennesAnnuellesDuBulletin::class)->pour([$inscription->fresh()])[$inscription->id]['moyenne'] ?? null;
        $attendue = app(BulletinService::class)->genererDonneesBulletin($etudiant->id, $spec->id, $annee->id, 'semestre2')['moyenneAnnuelle'];

        return [$attendue, $calculee];
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
