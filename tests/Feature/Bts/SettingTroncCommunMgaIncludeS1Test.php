<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPNiveauEtude;
use App\Models\Setting;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le reglage choisit la formule annuelle d'un eleve oriente, sans effacer sa
 * chronologie : le S1 d'origine reste connu et affichable dans les deux modes.
 */
class SettingTroncCommunMgaIncludeS1Test extends TestCase
{
    use RefreshDatabase;

    public function test_actif_annuel_combine_s1_origine_et_s2_specialite(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '1');
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $service = app(BulletinService::class);
        $this->assertTrue($service->annualIncludesSemester1($tcClasse->id, $specClasse->id));
        $this->assertEqualsWithDelta(
            14.0,
            $service->calculateConfiguredAnnualAverage(
                18.0,
                10.0,
                ['semester1' => 1.0, 'semester2' => 1.0],
                $tcClasse->id,
                $specClasse->id
            ),
            0.001
        );

        $map = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)->resolve(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id
        );
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    public function test_coupe_annuel_oriente_devient_s2_seul_sans_perdre_la_classe_origine(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '0');
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $service = app(BulletinService::class);
        $this->assertFalse($service->annualIncludesSemester1($tcClasse->id, $specClasse->id));
        $this->assertSame(
            10.0,
            $service->calculateConfiguredAnnualAverage(
                18.0,
                10.0,
                ['semester1' => 1.0, 'semester2' => 1.0],
                $tcClasse->id,
                $specClasse->id
            )
        );

        $map = app(\App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver::class)->resolve(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id
        );
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    public function test_coupe_ne_change_pas_une_classe_ordinaire(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '0');

        $service = app(BulletinService::class);
        $this->assertTrue($service->annualIncludesSemester1(42, 42));
        $this->assertEqualsWithDelta(
            14.0,
            $service->calculateConfiguredAnnualAverage(
                18.0,
                10.0,
                ['semester1' => 1.0, 'semester2' => 1.0],
                42,
                42
            ),
            0.001
        );
    }

    private function setSetting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value' => $value,
            'type' => 'string',
            'group' => 'tronc_commun',
            'category' => 'tronc_commun',
            'is_active' => true,
        ]);
    }

    /** @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse} */
    private function makePhaseBasedInscription(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $specClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return [$inscription, $tcClasse, $specClasse];
    }
}
