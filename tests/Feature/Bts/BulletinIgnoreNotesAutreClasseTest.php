<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class BulletinIgnoreNotesAutreClasseTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    public function test_une_note_d_une_autre_classe_ne_revient_pas_sur_le_bulletin(): void
    {
        $matiereOk = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($matiereOk), 12);

        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);
        $hydrologie = ESBTPMatiere::factory()->create([
            'name' => 'Hydrologie',
            'unite_enseignement_id' => null,
        ]);
        $evalHydro = \App\Models\ESBTPEvaluation::factory()->create([
            'matiere_id' => $hydrologie->id,
            'classe_id' => $autreClasse->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'status' => 'published',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
        $this->noter($etudiant, $evalHydro, 15, $autreClasse);

        ESBTPBulletin::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config_matieres' => [
                'generales' => [$matiereOk->id],
                'techniques' => [],
            ],
        ]);

        app(BulletinService::class)->genererDonneesBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        $this->assertSame(0, ESBTPResultat::where('matiere_id', $hydrologie->id)->count());
        $this->assertGreaterThan(0, ESBTPResultat::where('matiere_id', $matiereOk->id)->count());
    }
}
