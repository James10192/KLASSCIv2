<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RangAnnuelPariteTroncCommunTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create(['id' => 1]);
        SettingsHelper::setOrCreate('bulletin_semester1_weight', '1');
        SettingsHelper::setOrCreate('bulletin_semester2_weight', '1');
        SettingsHelper::setOrCreate('tronc_commun_mga_include_s1', '0');
        SettingsHelper::setOrCreate('bulletin_show_attendance_note', '0');
    }

    public function test_le_rang_annuel_classe_sur_s1_plus_s2_et_non_sur_s2_seul(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tc = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);
        $spec = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'systeme_academique' => 'BTS',
        ]);

        $a = $this->etudiantOriente($annee, $niveau, $tcFiliere, $specFiliere, $tc, $spec);
        $b = $this->etudiantOriente($annee, $niveau, $tcFiliere, $specFiliere, $tc, $spec);

        // A : S1=18, S2=10 => annuelle 14.
        // B : S1=6,  S2=20 => annuelle 13.
        // Un classement S2 seul donnerait B premier ; l'annuel correct donne A premier.
        $this->bulletin($a, $tc, $annee, 'semestre1', 18);
        $this->bulletin($a, $spec, $annee, 'semestre2', 10);
        $this->bulletin($b, $tc, $annee, 'semestre1', 6);
        $this->bulletin($b, $spec, $annee, 'semestre2', 20);

        $rang = app(BulletinService::class)->calculerRangAnnuel(
            $a->id,
            $spec->id,
            $annee->id,
            14.0
        );

        $this->assertSame(1, $rang);
    }

    private function etudiantOriente($annee, $niveau, $tcFiliere, $specFiliere, $tc, $spec): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $spec->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'workflow_step' => 'etudiant_cree',
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tc->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $spec->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return $etudiant;
    }

    private function bulletin(
        ESBTPEtudiant $etudiant,
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode,
        float $moyenne
    ): void {
        ESBTPBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'periode' => $periode,
            'moyenne_generale' => $moyenne,
            'note_assiduite' => 0,
        ]);
    }
}
