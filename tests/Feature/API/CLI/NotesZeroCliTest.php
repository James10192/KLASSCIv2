<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLINotesZeroController;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Poser la note manquante aux eleves qu'une evaluation a oublies.
 *
 * Le point sensible n'est pas de creer les notes : c'est de ne PAS en creer
 * sur une evaluation que personne n'a encore corrigee. Sans cette retenue,
 * l'outil colle un zero a toute la classe dans une matiere ou la saisie
 * n'a simplement pas commence.
 */
class NotesZeroCliTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function la_route_est_enregistree_en_post(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/cli/evaluations/noter-les-non-notes');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    /** @test */
    public function poser_les_notes_exige_le_droit_cli_admin(): void
    {
        $reponse = app(CLINotesZeroController::class)->noter($this->requeteAvecDroits([], []));

        $this->assertSame(403, $reponse->getStatusCode());
    }

    /** @test */
    public function la_simulation_est_le_defaut_et_n_ecrit_rien(): void
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $note = $this->etudiantInscrit();
        $oublie = $this->etudiantInscrit();
        $this->noter($note, $evaluation, 14);

        $reponse = app(CLINotesZeroController::class)->noter(
            $this->requeteAvecDroits(['cli:admin'], ['annee_id' => $this->annee->id])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertTrue($donnees['dry_run']);
        $this->assertSame(1, $donnees['total_notes']);
        $this->assertSame(1, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
        $this->assertSame(0, ESBTPNote::where('etudiant_id', $oublie->id)->count());
    }

    /** @test */
    public function l_eleve_oublie_recoit_un_zero_qui_n_est_pas_une_absence(): void
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $note = $this->etudiantInscrit();
        $oublie = $this->etudiantInscrit();
        $this->noter($note, $evaluation, 14);

        app(CLINotesZeroController::class)->noter(
            $this->requeteAvecDroits(['cli:admin'], [
                'annee_id' => $this->annee->id,
                'dry_run' => false,
            ])
        );

        $posee = ESBTPNote::where('evaluation_id', $evaluation->id)
            ->where('etudiant_id', $oublie->id)
            ->first();

        $this->assertNotNull($posee);
        $this->assertEqualsWithDelta(0.0, (float) $posee->note, 0.001);
        $this->assertFalse((bool) $posee->is_absent);
        $this->assertSame($evaluation->periode, $posee->semestre);
        $this->assertSame((int) $evaluation->classe_id, (int) $posee->classe_id);
        $this->assertSame((int) $evaluation->matiere_id, (int) $posee->matiere_id);

        // La note deja saisie n'est pas touchee.
        $this->assertEqualsWithDelta(
            14.0,
            (float) ESBTPNote::where('evaluation_id', $evaluation->id)->where('etudiant_id', $note->id)->value('note'),
            0.001
        );
    }

    /** @test */
    public function une_evaluation_que_personne_n_a_notee_est_laissee_intacte(): void
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $this->etudiantInscrit();
        $this->etudiantInscrit();

        $reponse = app(CLINotesZeroController::class)->noter(
            $this->requeteAvecDroits(['cli:admin'], [
                'annee_id' => $this->annee->id,
                'dry_run' => false,
            ])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertSame(0, $donnees['total_notes']);
        $this->assertTrue($donnees['evaluations'][0]['ignoree']);
        $this->assertSame(0, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    /** @test */
    public function la_retenue_peut_etre_levee_explicitement(): void
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $this->etudiantInscrit();
        $this->etudiantInscrit();

        app(CLINotesZeroController::class)->noter(
            $this->requeteAvecDroits(['cli:admin'], [
                'annee_id' => $this->annee->id,
                'seulement_evaluations_deja_notees' => false,
                'dry_run' => false,
            ])
        );

        $this->assertSame(2, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    /** @test */
    public function relancer_l_operation_ne_cree_pas_de_doublon(): void
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $note = $this->etudiantInscrit();
        $this->etudiantInscrit();
        $this->noter($note, $evaluation, 14);

        $charge = [
            'annee_id' => $this->annee->id,
            'dry_run' => false,
        ];

        app(CLINotesZeroController::class)->noter($this->requeteAvecDroits(['cli:admin'], $charge));
        $reponse = app(CLINotesZeroController::class)->noter($this->requeteAvecDroits(['cli:admin'], $charge));

        $this->assertSame(0, $reponse->getData(true)['data']['total_notes']);
        $this->assertSame(2, ESBTPNote::where('evaluation_id', $evaluation->id)->count());
    }

    private function requeteAvecDroits(array $droits, array $charge): Request
    {
        $utilisateur = new User();
        $utilisateur->id = 1;
        $requete = Request::create('/', 'POST', $charge);
        $requete->setUserResolver(fn () => new class($utilisateur, $droits) extends User
        {
            private array $droits = [];

            public function __construct(?User $utilisateur = null, array $droits = [])
            {
                parent::__construct();
                $this->droits = $droits;
                if ($utilisateur) {
                    $this->setRawAttributes($utilisateur->getAttributes());
                }
            }

            public function tokenCan(string $ability): bool
            {
                return in_array($ability, $this->droits, true);
            }
        });

        return $requete;
    }
}
