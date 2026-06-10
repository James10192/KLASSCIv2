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
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * c3 — Le setting tronc_commun_mga_include_s1 gouverne l'appel au class-map resolver.
 *
 *  - ON  : le résolveur est consulté et la classe TC est substituée pour le S1.
 *  - OFF : le résolveur N'EST PAS appelé du tout, aucune substitution.
 *
 * BTS uniquement (LMD intouché).
 */
class SettingTroncCommunMgaIncludeS1Test extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** @test */
    public function when_setting_on_the_resolver_substitutes_the_tronc_commun_classe(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '1');

        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $this->seedConfiguredBulletin(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id,
            'semestre1'
        );

        $service = app(BulletinService::class);
        $data = $service->genererDonneesBulletin(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id,
            'semestre1'
        );

        $this->assertNotNull($data['classeTroncCommun']);
        $this->assertSame($tcClasse->id, $data['classeTroncCommun']->id);
    }

    /** @test */
    public function when_setting_off_no_tronc_commun_substitution_happens(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '0');

        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $this->seedConfiguredBulletin(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id,
            'semestre1'
        );

        // Setting OFF : la branche d'inclusion S1 (BulletinService.php:466) est sautée,
        // donc AUCUNE substitution de classe TC pour le MGA. La classe reste la
        // spécialité. NB : le BtsCurrentResultSnapshotService consulte le class-map
        // resolver indépendamment de ce setting (comportement Plan C voulu, couvert
        // par BtsCurrentResultSnapshotClassMapTest) — on ne le mocke donc PAS ici,
        // on vérifie uniquement l'absence de substitution observable.
        $service = app(BulletinService::class);
        $data = $service->genererDonneesBulletin(
            $inscription->etudiant_id,
            $specClasse->id,
            $inscription->annee_universitaire_id,
            'semestre1'
        );

        $this->assertNull($data['classeTroncCommun']);
        $this->assertFalse($data['isSpecialisation']);
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

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makePhaseBasedInscription(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
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
