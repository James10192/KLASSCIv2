<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\CorrectionDeNotes;
use App\Http\Controllers\API\CLI\CLIMoyennesController;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * POST /api/cli/notes/corriger : corriger les notes existantes d'un eleve,
 * puis recalculer ses moyennes sans attendre de worker.
 */
class CorrectionDeNotesCliTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    private ESBTPEtudiant $eleve;

    private ESBTPMatiere $anglais;

    private ESBTPNote $note;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
        $this->anglais = $this->matiereConfiguree();
        $this->eleve = $this->etudiantInscrit();
        $this->noter($this->eleve, $this->evaluationDe($this->anglais), 0);
        $this->note = ESBTPNote::where('etudiant_id', $this->eleve->id)->firstOrFail();
    }

    /** @test */
    public function la_route_existe(): void
    {
        $this->assertTrue(Route::has('api.cli.notes.corriger'));
    }

    /** @test */
    public function sans_dry_run_explicite_la_note_ne_bouge_pas(): void
    {
        $data = $this->appeler(['notes' => [['note_id' => $this->note->id, 'note' => 15]]])->getData(true)['data'];

        $this->assertTrue($data['dry_run']);
        $this->assertSame(0.0, (float) $data['lignes'][0]['avant']);
        $this->assertSame(0.0, (float) $this->note->fresh()->note);
    }

    /** @test */
    public function la_correction_remplace_la_moyenne_enregistree_sans_worker(): void
    {
        // Moyenne enregistree a 0 : c'est elle qui l'emporte au bulletin.
        ESBTPResultat::updateOrCreate([
            'etudiant_id' => $this->eleve->id, 'classe_id' => $this->classe->id, 'matiere_id' => $this->anglais->id,
            'periode' => 'semestre1', 'annee_universitaire_id' => $this->annee->id,
        ], ['moyenne' => 0, 'coefficient' => 2]);
        // Aucun worker : la file ne doit pas etre le chemin du recalcul.
        Queue::fake();

        $data = $this->appeler(['dry_run' => false, 'notes' => [['note_id' => $this->note->id, 'note' => 15]]])->getData(true)['data'];

        $this->assertSame(15.0, (float) $this->note->fresh()->note);
        $this->assertSame(15.0, (float) ESBTPResultat::where('matiere_id', $this->anglais->id)->value('moyenne'));
        $this->assertSame(15.0, (float) $data['moyennes'][0]['moyenne']);
        Queue::assertNothingPushed();
    }

    /** @test */
    public function la_note_d_un_autre_eleve_est_refusee(): void
    {
        $autre = $this->etudiantInscrit();

        $reponse = $this->appeler(['etudiant_id' => $autre->id, 'dry_run' => false, 'notes' => [['note_id' => $this->note->id, 'note' => 15]]]);

        $this->assertSame(422, $reponse->getStatusCode());
        $this->assertSame(0.0, (float) $this->note->fresh()->note);
    }

    /** @test */
    public function une_note_au_dessus_du_bareme_est_refusee(): void
    {
        $reponse = $this->appeler(['dry_run' => false, 'notes' => [['note_id' => $this->note->id, 'note' => 21]]]);

        $this->assertSame(422, $reponse->getStatusCode());
    }

    /** @test */
    public function un_jeton_en_lecture_seule_ne_peut_pas_corriger(): void
    {
        $this->assertSame(403, $this->appeler(['dry_run' => false, 'notes' => [['note_id' => $this->note->id, 'note' => 15]]], ['cli:read'])->getStatusCode());
        $this->assertSame(0.0, (float) $this->note->fresh()->note);
    }

    /**
     * @param  array<string,mixed>  $surcharge
     * @param  array<int,string>  $droits
     */
    private function appeler(array $surcharge, array $droits = ['cli:admin']): JsonResponse
    {
        $requete = Request::create('/', 'POST', $surcharge + [
            'etudiant_id' => $this->eleve->id,
            'motif' => 'Réclamation de l’élève validée par la direction des études',
        ]);
        $requete->setUserResolver(fn () => new class($droits) extends User
        {
            /** @var array<int,string> */
            private array $droits;

            /** @param array<int,string> $droits */
            public function __construct(array $droits = [])
            {
                parent::__construct();
                $this->droits = $droits;
                $this->setRawAttributes(['id' => 1]);
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->droits, true);
            }
        });

        return app(CLIMoyennesController::class)->corrigerNotes($requete, app(CorrectionDeNotes::class));
    }
}
