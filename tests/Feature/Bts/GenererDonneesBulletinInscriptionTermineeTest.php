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
 * c3 — Le class-map resolver résout aussi les inscriptions terminées.
 *
 * Contrairement à l'ancien bloc (status IN active|terminée requis et inscriptionOrigine
 * direct), le résolveur stateless ordonne par status='active' EN PREMIER mais ne filtre
 * PAS sur le statut. Une inscription terminée orientée doit donc toujours résoudre la
 * classe TC pour le S1.
 *
 * BTS uniquement (LMD intouché).
 */
class GenererDonneesBulletinInscriptionTermineeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** @test */
    public function it_resolves_tronc_commun_classe_even_when_inscription_is_terminated(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '1');

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
            'status' => 'terminée',
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

        $this->seedConfiguredBulletin($etudiant->id, $specClasse->id, $annee->id, 'semestre1');

        $service = app(BulletinService::class);
        $data = $service->genererDonneesBulletin($etudiant->id, $specClasse->id, $annee->id, 'semestre1');

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
}
