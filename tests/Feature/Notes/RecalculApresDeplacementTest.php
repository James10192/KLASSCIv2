<?php

namespace Tests\Feature\Notes;

use App\Http\Controllers\API\CLI\CLIMaintenanceController;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Ce qui arrive a `esbtp_resultats` quand une evaluation change de matiere.
 *
 * Le deplacement propage `esbtp_notes.matiere_id` par un `update()` de query
 * builder, qui n'emet aucun evenement Eloquent : sans recalcul explicite, les
 * DEUX coordonnees gardent la moyenne d'avant. Et cette moyenne l'emporte sur
 * les notes a l'affichage comme au bulletin.
 *
 * Chaque test ici echoue si l'appel a `RecalculApresDeplacement` est retire —
 * c'est le seul critere qui les rend utiles. Verifie en le retirant.
 */
class RecalculApresDeplacementTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function la_nouvelle_matiere_prend_en_compte_les_notes_arrivees(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        $restante = $this->evaluationDe($arrivee);
        $this->noter($etudiant, $restante, 20);

        // Etat de depart : chaque matiere porte la moyenne de sa seule note.
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $depart->id));
        $this->assertSame(20.0, $this->moyenne($etudiant->id, $arrivee->id));

        $this->deplacer($deplacee->id, $arrivee->id);

        // (20 + 10) / 2 : sans le recalcul, la matiere d'arrivee resterait a 20.
        $this->assertSame(15.0, $this->moyenne($etudiant->id, $arrivee->id));
    }

    /** @test */
    public function une_matiere_videe_de_ses_notes_n_est_pas_remise_a_zero(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        $reponse = $this->deplacer($deplacee->id, $arrivee->id);

        // `studentMatiereAverage([])` rend 0.0, pas null : rejouer le calcul sur
        // une matiere vide y ecrirait un 0/20 que rien ne justifie. La ligne est
        // donc laissee telle quelle, et signalee.
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $depart->id));

        $orphelins = $reponse['agregats_orphelins'];
        $this->assertCount(1, $orphelins);
        $this->assertSame($depart->id, $orphelins[0]['matiere_id']);
        // `getData(true)` repasse par JSON : 10.0 y ressort en entier.
        $this->assertSame(10.0, (float) $orphelins[0]['moyenne']);
    }

    /** @test */
    public function une_matiere_qui_garde_des_notes_est_recalculee(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $gardee = $this->evaluationDe($depart);
        $this->noter($etudiant, $gardee, 10);

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 20);

        $this->assertSame(15.0, $this->moyenne($etudiant->id, $depart->id));

        $reponse = $this->deplacer($deplacee->id, $arrivee->id);

        // Il reste la note de 10 : la matiere de depart descend a 10, elle ne
        // reste ni a 15 (perime) ni ne tombe a 0 (vide a tort).
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $depart->id));
        $this->assertSame(20.0, $this->moyenne($etudiant->id, $arrivee->id));
        $this->assertSame([], $reponse['agregats_orphelins']);
    }

    /** @test */
    public function la_simulation_ne_recalcule_rien(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        $reponse = $this->appeler('evaluationChangeMatiere', ['cli:admin'], [
            'matiere_id' => $arrivee->id,
            'dry_run' => true,
        ], $deplacee->id);

        $this->assertTrue($reponse['dry_run']);
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $depart->id));
        $this->assertNull($this->moyenne($etudiant->id, $arrivee->id));
    }

    /** @test */
    public function le_recalcul_cli_exige_un_perimetre_et_le_droit_admin(): void
    {
        $this->monterLaClasse();

        $refus = app(CLIMaintenanceController::class)->notesRecompute(
            $this->requete([], [])
        );
        $this->assertSame(403, $refus->getStatusCode());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(CLIMaintenanceController::class)->notesRecompute(
            $this->requete(['cli:admin'], ['periode' => 'semestre1'])
        );
    }

    /** @test */
    public function le_recalcul_cli_repare_un_agregat_perime(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $evaluation = $this->evaluationDe($matiere);
        $this->noter($etudiant, $evaluation, 12);

        // On perime l'agregat comme le fait un `update()` de query builder :
        // sans evenement, donc sans recalcul.
        ESBTPResultat::where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->update(['moyenne' => 7]);
        $this->assertSame(7.0, $this->moyenne($etudiant->id, $matiere->id));

        $perimetre = [
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
        ];

        $simulation = $this->appeler('notesRecompute', ['cli:admin'], $perimetre + ['dry_run' => true]);
        $this->assertTrue($simulation['dry_run']);
        $this->assertSame(7.0, $this->moyenne($etudiant->id, $matiere->id));

        $reel = $this->appeler('notesRecompute', ['cli:admin'], $perimetre);
        $this->assertSame(1, $reel['modifies']);
        $this->assertSame(0, $reel['echecs']);
        $this->assertSame(7.0, (float) $reel['couples'][0]['moyenne_avant']);
        $this->assertSame(12.0, (float) $reel['couples'][0]['moyenne_apres']);
        $this->assertSame(12.0, $this->moyenne($etudiant->id, $matiere->id));
    }


    /**
     * L'ecran web est l'autre chemin, et le plus emprunte. Il partage le meme
     * appel, mais il peut bouger TROIS dimensions d'un coup (classe, matiere,
     * periode) : cette couverture-la ne se deduit pas de celle du CLI.
     */
    /** @test */
    public function l_ecran_web_recalcule_lui_aussi_apres_un_deplacement(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        $restante = $this->evaluationDe($arrivee);
        $this->noter($etudiant, $restante, 20);

        // `evaluations.edit_locked` : sans elle, l'ecran refuse de bouger une
        // evaluation deja notee — et c'est justement ce cas qu'on teste.
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        app(\App\Http\Controllers\ESBTPEvaluationController::class)->update(
            Request::create('/', 'PUT', [
                'titre' => 'Devoir deplace',
                'type' => 'devoir',
                'date_evaluation' => now()->toDateString(),
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'classe_id' => $this->classe->id,
                'matiere_id' => $arrivee->id,
                'bareme' => 20,
                'coefficient' => 1,
                'periode' => 'semestre1',
            ]),
            $deplacee
        );

        $this->assertSame($arrivee->id, $deplacee->fresh()->matiere_id);
        $this->assertSame(15.0, $this->moyenne($etudiant->id, $arrivee->id));
        $this->assertSame(10.0, $this->moyenne($etudiant->id, $depart->id));
    }

    /** @test */
    public function les_deux_routes_sont_enregistrees(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri());

        $this->assertTrue($uris->contains('api/cli/notes/recompute'));
        $this->assertTrue($uris->contains('api/cli/diagnostics/queue'));
    }

    /** @test */
    public function le_diagnostic_de_file_dit_si_un_job_a_une_chance_de_tourner(): void
    {
        $reponse = app(CLIMaintenanceController::class)->queueHealth($this->requete(['cli:read'], []));
        $donnees = $reponse->getData(true)['data'];

        $this->assertSame(config('queue.default'), $donnees['driver']);
        $this->assertSame(403, app(CLIMaintenanceController::class)
            ->queueHealth($this->requete([], []))->getStatusCode());
    }

    /** @return array<string,mixed> */
    private function deplacer(int $evaluationId, int $matiereCible): array
    {
        return $this->appeler('evaluationChangeMatiere', ['cli:admin'], [
            'matiere_id' => $matiereCible,
        ], $evaluationId);
    }

    /**
     * @param  array<int,string>  $droits
     * @param  array<string,mixed>  $charge
     * @return array<string,mixed>
     */
    private function appeler(string $methode, array $droits, array $charge, ?int $id = null): array
    {
        $controleur = app(CLIMaintenanceController::class);
        $requete = $this->requete($droits, $charge);

        $reponse = $id === null
            ? $controleur->{$methode}($requete)
            : $controleur->{$methode}($requete, $id);

        $donnees = $reponse->getData(true);
        $this->assertSame(200, $reponse->getStatusCode(), json_encode($donnees));

        return $donnees['data'];
    }

    private function moyenne(int $etudiantId, int $matiereId): ?float
    {
        $valeur = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $this->classe->id)
            ->where('matiere_id', $matiereId)
            ->where('periode', 'semestre1')
            ->where('annee_universitaire_id', $this->annee->id)
            ->value('moyenne');

        return $valeur === null ? null : (float) $valeur;
    }

    /**
     * @param  array<int,string>  $droits
     * @param  array<string,mixed>  $charge
     */
    private function requete(array $droits, array $charge): Request
    {
        $requete = Request::create('/', 'POST', $charge);
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

        return $requete;
    }
}
