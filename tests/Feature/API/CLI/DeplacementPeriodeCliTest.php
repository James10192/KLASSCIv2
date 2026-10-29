<?php

namespace Tests\Feature\API\CLI;

use App\Http\Controllers\API\CLI\CLIEvaluationDeplacementController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Le deplacement d'evaluations d'un semestre a l'autre.
 *
 * Ce qu'on verifie tient en trois points : la simulation n'ecrit rien, le
 * deplacement reel aligne aussi la copie denormalisee portee par les notes,
 * et relancer le meme appel ne deplace pas deux fois.
 */
class DeplacementPeriodeCliTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function la_route_est_enregistree_en_post(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($r) => $r->uri() === 'api/cli/evaluations/deplacer-periode');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
    }

    /** @test */
    public function le_deplacement_exige_le_droit_cli_admin(): void
    {
        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteAvecDroits([], ['evaluation_ids' => [1], 'periode' => 'semestre1'])
        );

        $this->assertSame(403, $reponse->getStatusCode());
    }

    /** @test */
    public function la_simulation_est_le_defaut_et_n_ecrit_rien(): void
    {
        $evaluation = $this->uneEvaluationDuSemestre('semestre2');

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteAvecDroits(['cli:admin'], [
                'evaluation_ids' => [$evaluation->id],
                'periode' => 'semestre1',
            ])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertSame(200, $reponse->getStatusCode());
        $this->assertTrue($donnees['dry_run']);
        $this->assertSame(1, $donnees['total_a_deplacer']);
        $this->assertSame('semestre2', $evaluation->fresh()->periode);
    }

    /** @test */
    public function le_deplacement_reel_aligne_aussi_le_semestre_porte_par_les_notes(): void
    {
        $evaluation = $this->uneEvaluationDuSemestre('semestre2');
        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $evaluation, 12);

        // On sème la valeur FAUTIVE a dessein : c'est ce que les deux endpoints
        // ecrivaient, et ce que ce test verrouillait.
        ESBTPNote::where('evaluation_id', $evaluation->id)->update(['semestre' => 'semestre2']);

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteAvecDroits(['cli:admin'], [
                'evaluation_ids' => [$evaluation->id],
                'periode' => 'semestre1',
                'dry_run' => false,
            ])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertFalse($donnees['dry_run']);
        $this->assertSame(1, $donnees['total']);
        $this->assertSame('semestre1', $evaluation->fresh()->periode);

        // **Ce test affirmait `'semestre1'`, et il defendait le defaut.**
        //
        // `esbtp_notes.semestre` est un `varchar`, mais l'encodage canonique est
        // l'ENTIER : le hook `saving()` d'`ESBTPNote` fait
        // `(int) str_replace('semestre', '', …)` et a le dernier mot sur chaque
        // chemin Eloquent. Seul un `update()` de query builder le contourne.
        //
        // En MySQL `'semestre1' = 1` vaut **0**. La categorie 2 d'
        // `evaluations:sync-notes --clean-resultats` joint sur
        // `esbtp_notes.semestre = 1`, ne retrouvait donc pas la note, et
        // **supprimait** l'agregat. L'assertion d'avant scellait ce chemin.
        $this->assertSame(
            1,
            (int) ESBTPNote::where('evaluation_id', $evaluation->id)->value('semestre')
        );
        $this->assertSame(
            1,
            ESBTPNote::where('evaluation_id', $evaluation->id)->where('semestre', 1)->count(),
            'la note doit etre retrouvee par le predicat exact de --clean-resultats'
        );
    }

    /** @test */
    public function relancer_le_meme_deplacement_ne_le_refait_pas(): void
    {
        $evaluation = $this->uneEvaluationDuSemestre('semestre1');

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteAvecDroits(['cli:admin'], [
                'evaluation_ids' => [$evaluation->id],
                'periode' => 'semestre1',
                'dry_run' => false,
            ])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertSame(0, $donnees['total']);
        $this->assertCount(1, $donnees['deja_sur_la_cible']);
    }

    /** @test */
    public function une_evaluation_inconnue_est_signalee_sans_faire_echouer_le_lot(): void
    {
        $evaluation = $this->uneEvaluationDuSemestre('semestre2');

        $reponse = app(CLIEvaluationDeplacementController::class)->deplacer(
            $this->requeteAvecDroits(['cli:admin'], [
                'evaluation_ids' => [$evaluation->id, 999999],
                'periode' => 'semestre1',
                'dry_run' => false,
            ])
        );

        $donnees = $reponse->getData(true)['data'];

        $this->assertSame(1, $donnees['total']);
        $this->assertSame([999999], $donnees['introuvables']);
    }

    private function uneEvaluationDuSemestre(string $periode): ESBTPEvaluation
    {
        $this->monterLaClasse();
        $evaluation = $this->evaluationDe($this->matiereConfiguree());
        $evaluation->periode = $periode;
        $evaluation->save();

        return $evaluation;
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
