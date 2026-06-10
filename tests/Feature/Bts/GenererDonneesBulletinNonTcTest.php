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
 * c3 — Bulletin S1 d'un étudiant BTS classique (pas de tronc commun).
 *
 * Une inscription mono-classe doit voir classeTroncCommun = null et isSpecialisation
 * false : le class-map renvoie semestre1_classe_id == classeId, donc aucune
 * substitution de classe pour le S1.
 *
 * BTS uniquement (LMD intouché).
 */
class GenererDonneesBulletinNonTcTest extends TestCase
{
    use RefreshDatabase;
    use SeedsConfiguredBulletin;

    /** @test */
    public function it_does_not_substitute_classe_for_pure_bts_student(): void
    {
        $this->setSetting('tronc_commun_mga_include_s1', '1');

        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create();
        $classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();
        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $this->seedConfiguredBulletin($etudiant->id, $classe->id, $annee->id, 'semestre1');

        $service = app(BulletinService::class);
        $data = $service->genererDonneesBulletin($etudiant->id, $classe->id, $annee->id, 'semestre1');

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
}
