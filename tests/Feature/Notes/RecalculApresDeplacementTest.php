<?php

namespace Tests\Feature\Notes;

use App\Http\Controllers\API\CLI\CLIMaintenanceController;
use App\Http\Controllers\API\CLI\CLINotesRecomputeController;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
 * **SIX** tests tombent si l'appel a `RecalculApresDeplacement::pour()` est
 * retire, mesure en le retirant : les trois premiers, « l ecran web recalcule
 * lui aussi », « le recalcul tourne sur place » et « le recalcul laisse une
 * trace d audit ». Les cinq autres couvrent le point d'entree CLI et ne
 * dependent pas de cet appel.
 *
 * Le quatrieme, « la simulation ne recalcule rien », reste vert sans le
 * correctif — et c'est juste : il verifie qu'on n'ecrit RIEN, ce qui est aussi
 * vrai quand le recalcul n'existe pas. Il garde le `dry_run`, pas le recalcul.
 *
 * **Ce compte a ete faux deux fois de suite.** Il a d'abord dit « les neuf »,
 * puis « les QUATRE premiers » — or ni le nombre ni l'ensemble n'etaient bons,
 * et le message du commit qui a pose ce « quatre » disait six deux lignes plus
 * loin. Un docbloc qui dit « verifie en le retirant » sans que le retrait ait
 * ete refait est pire qu'un docbloc muet : il fait croire la verification
 * faite. Rejouez-le avant de toucher ce chiffre.
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

        $refus = app(CLINotesRecomputeController::class)->notesRecompute(
            $this->requete([], [])
        );
        $this->assertSame(403, $refus->getStatusCode());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(CLINotesRecomputeController::class)->notesRecompute(
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

    /**
     * `etudiant_id` etait valide, conseille dans le refus 422 — et jamais
     * applique : viser un eleve recalculait toute sa classe. Un recalcul ECRASE
     * la moyenne enregistree, y compris une valeur saisie a la main.
     */
    /** @test */
    public function le_recalcul_cli_vise_bien_un_seul_eleve(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $vise = $this->etudiantInscrit();
        $voisin = $this->etudiantInscrit();

        $evaluation = $this->evaluationDe($matiere);
        $this->noter($vise, $evaluation, 12);
        $this->noter($voisin, $evaluation, 8);

        ESBTPResultat::whereIn('etudiant_id', [$vise->id, $voisin->id])
            ->where('matiere_id', $matiere->id)
            ->update(['moyenne' => 7]);

        $reponse = $this->appeler('notesRecompute', ['cli:admin'], [
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'etudiant_id' => $vise->id,
        ]);

        $this->assertSame(1, $reponse['total']);
        $this->assertSame(12.0, $this->moyenne($vise->id, $matiere->id));
        $this->assertSame(7.0, $this->moyenne($voisin->id, $matiere->id), 'le voisin ne devait pas bouger');
    }

    /**
     * Le choix le plus contre-intuitif du correctif : le job tourne SUR PLACE.
     * `.env.testing` pose `QUEUE_CONNECTION=sync`, donc `dispatch()` et
     * `dispatchSync()` y sont indiscernables — sans ce test, remplacer l'un par
     * l'autre laisserait toute la suite verte, et le correctif ne s'executerait
     * jamais sur les instances, ou aucun worker ne tourne.
     */
    /** @test */
    public function le_recalcul_tourne_sur_place_et_non_sur_la_file(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $restante = $this->evaluationDe($arrivee);
        $this->noter($etudiant, $restante, 20);

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        // `Queue::fake()` ne discriminerait PAS : `dispatchSync()` passe lui
        // aussi par le gestionnaire de file (sur la connexion `sync`), donc la
        // fausse file l'intercepte exactement comme un `dispatch()`. Les deux
        // deviennent indiscernables, et le job ne tourne dans aucun des cas.
        //
        // Ce qui discrimine, c'est la vraie file `database` — celle des huit
        // instances. Avec `dispatch()`, une ligne atterrit dans `jobs` et rien
        // n'est recalcule ; `dispatchSync()` force la connexion `sync` et tourne
        // sur place. Les deux assertions se tiennent l'une l'autre.
        config(['queue.default' => 'database']);
        DB::table('jobs')->delete();

        $this->deplacer($deplacee->id, $arrivee->id);

        $this->assertSame(0, DB::table('jobs')->count(), 'aucun recalcul ne doit partir sur la file');
        $this->assertSame(15.0, $this->moyenne($etudiant->id, $arrivee->id));
    }

    /**
     * La trace d'audit a ete muette pendant toute la premiere version du
     * correctif : `esbtp_resultats_recompute_log.source` etait une enumeration
     * fermee, l'INSERT levait sous `STRICT_TRANS_TABLES`, et le job avalait
     * l'exception. Le recalcul se declarait reussi, sans trace.
     */
    /** @test */
    public function le_recalcul_laisse_une_trace_d_audit(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);

        DB::table('esbtp_resultats_recompute_log')->delete();

        $this->deplacer($deplacee->id, $arrivee->id);

        $this->assertSame(
            1,
            DB::table('esbtp_resultats_recompute_log')->where('source', 'deplacement')->count()
        );
    }

    /** @test */
    public function le_recalcul_cli_voit_une_periode_encodee_en_chiffre(): void
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        // `esbtp_evaluations.periode` accepte `'1'` autant que `'semestre1'` —
        // c'est la raison d'etre d'`ESBTPEvaluation::aliasDePeriode()`.
        $evaluation = $this->evaluationDe($matiere);
        $evaluation->update(['periode' => '1']);
        $this->noter($etudiant, $evaluation, 9);

        $reponse = $this->appeler('notesRecompute', ['cli:admin'], [
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'dry_run' => true,
        ]);

        // Avec un `where('periode', 'semestre1')` nu, le perimetre etait vide et
        // l'endpoint repondait « rien a recalculer » : un SUCCES rassurant sur
        // un perimetre explicitement signale comme a recalculer.
        $this->assertSame(1, $reponse['total'], 'la note encodee en "1" doit entrer dans le perimetre');
    }

    /** @test */
    public function la_route_de_recalcul_est_enregistree(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())->map(fn ($r) => $r->uri());

        $this->assertTrue($uris->contains('api/cli/notes/recompute'));
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
        // `notesRecompute` a son controleur a lui (`CLINotesRecomputeController`) :
        // `CLIMaintenanceController` passait 1500 lignes, le grossir encore
        // contredisait l'axe « no god code ».
        $controleur = $methode === 'notesRecompute'
            ? app(CLINotesRecomputeController::class)
            : app(CLIMaintenanceController::class);
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
