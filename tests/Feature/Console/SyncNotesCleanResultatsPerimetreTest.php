<?php

namespace Tests\Feature\Console;

use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * `evaluations:sync-notes --clean-resultats` supprime des lignes
 * d'`esbtp_resultats`. Il sait restreindre son menage a une classe et a une
 * periode — mais cette restriction ne s'appliquait qu'a UNE de ses deux
 * categories.
 *
 * La categorie 1 (« la matiere n'existe plus ») balayait l'ecole entiere quoi
 * qu'on lui demande, et c'est pourtant celle qui supprime le plus largement,
 * puisque son critere ne depend d'aucune option.
 *
 * Retirer l'appel a `restreindreAuPerimetre()` sur cette categorie fait tomber
 * ce test — c'est le seul critere qui le rend utile.
 */
class SyncNotesCleanResultatsPerimetreTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function le_menage_des_matieres_disparues_reste_dans_la_classe_demandee(): void
    {
        $this->monterLaClasse();

        $autreClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        // Une matiere mise de cote : c'est ce que la categorie 1 vise.
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        $vise = $this->uneMoyenne($this->classe, $matiere);
        $horsPerimetre = $this->uneMoyenne($autreClasse, $matiere);

        $matiere->delete();

        $this->artisan('evaluations:sync-notes', [
            '--clean-resultats' => true,
            '--classe' => $this->classe->id,
        ])->assertSuccessful();

        $this->assertNull(ESBTPResultat::find($vise->id), 'la ligne de la classe visee devait partir');
        $this->assertNotNull(
            ESBTPResultat::find($horsPerimetre->id),
            'une autre classe ne devait pas etre touchee'
        );
    }

    private function uneMoyenne(ESBTPClasse $classe, ESBTPMatiere $matiere): ESBTPResultat
    {
        $etudiant = $this->etudiantInscrit();

        return ESBTPResultat::create([
            'etudiant_id' => $etudiant->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'moyenne' => 12,
            'coefficient' => 1,
        ]);
    }
}
