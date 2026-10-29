<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

/**
 * Une moyenne supprimee ne condamne pas l'etudiant.
 *
 * La cle unique d'esbtp_resultats ignorait `deleted_at` : la ligne supprimee
 * occupait toujours la cle, et quand une note revenait sur la matiere, la
 * generation tentait un INSERT sur le meme quintuple -- `Duplicate entry`,
 * 500 definitif, sans issue depuis l'interface. Ce test rejoue exactement ce
 * scenario : suppression, retour de la note, generation.
 */
class PersistResultatsCleUniqueTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase, SeedsConfiguredBulletin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    public function test_la_generation_survit_au_retour_d_une_note_apres_suppression(): void
    {
        $matiere = $this->matiereConfiguree();
        $evaluation = $this->evaluationDe($matiere);
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $evaluation, 13);

        $this->seedConfiguredBulletin(
            (int) $etudiant->id,
            (int) $this->classe->id,
            (int) $this->annee->id,
            'semestre1',
            [(int) $matiere->id],
        );

        $service = app(BulletinService::class);

        // 1. Premiere generation : la ligne esbtp_resultats existe.
        $service->genererDonneesBulletin((int) $etudiant->id, (int) $this->classe->id, (int) $this->annee->id, 'semestre1');
        $ligne = ESBTPResultat::where('etudiant_id', $etudiant->id)->where('matiere_id', $matiere->id)->firstOrFail();

        // 2. Suppression (le geste du panneau d'avertissement).
        $ligne->delete();

        // 3. La note est toujours la : la generation suivante doit REVIVRE la
        //    ligne, pas percuter la cle unique.
        $service->genererDonneesBulletin((int) $etudiant->id, (int) $this->classe->id, (int) $this->annee->id, 'semestre1');

        $this->assertDatabaseHas('esbtp_resultats', [
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'periode' => 'semestre1',
            'deleted_at' => null,
        ]);
        $this->assertSame(1, ESBTPResultat::withTrashed()
            ->where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->count(), 'Une seule ligne, revivee : jamais deux quintuples identiques vivants.');
    }
}
