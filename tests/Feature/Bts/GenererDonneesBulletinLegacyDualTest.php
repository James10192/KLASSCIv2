<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\Setting;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * c3 — Bulletin S1 récupère la classe Tronc Commun via le class-map (modèle legacy).
 *
 * Un étudiant avec une double-inscription legacy (inscription_origine_id +
 * type_changement=specialisation) doit voir le bulletin Semestre 1 résoudre la
 * classe d'origine (TC) pour S1.
 *
 * BTS uniquement (LMD intouché).
 */
class GenererDonneesBulletinLegacyDualTest extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** @test */
    public function it_resolves_origine_classe_for_s1_on_legacy_dual_inscription(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '1');

        [$specialisation, $tcClasse, $specClasse] = $this->makeLegacyJourney();

        $this->seedConfiguredBulletin(
            $specialisation->etudiant_id,
            $specClasse->id,
            $specialisation->annee_universitaire_id,
            'semestre1'
        );

        $service = app(BulletinService::class);
        $data = $service->genererDonneesBulletin(
            $specialisation->etudiant_id,
            $specClasse->id,
            $specialisation->annee_universitaire_id,
            'semestre1'
        );

        $this->assertNotNull($data['classeTroncCommun']);
        $this->assertSame($tcClasse->id, $data['classeTroncCommun']->id);
        $this->assertTrue($data['isSpecialisation']);
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
    private function makeLegacyJourney(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $origine = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $specialisation = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
            'type_changement' => 'specialisation',
            'inscription_origine_id' => $origine->id,
        ]);

        return [$specialisation, $tcClasse, $specClasse];
    }
}
