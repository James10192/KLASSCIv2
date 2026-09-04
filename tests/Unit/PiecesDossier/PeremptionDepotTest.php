<?php

namespace Tests\Unit\PiecesDossier;

use App\Enums\EtatPieceDossier;
use App\Models\ESBTPPieceDeposee;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * La péremption d'un dépôt — sans base de données.
 *
 * Ce calcul décide si un étudiant doit être rappelé au guichet. Il tient en
 * quelques lignes et se teste sur des modèles construits en mémoire : aucune
 * ligne n'est écrite, aucune migration n'est rejouée.
 *
 * L'application est tout de même démarrée — `Tests\TestCase` et non le TestCase
 * nu de PHPUnit — parce qu'affecter une date à un modèle Eloquent passe par le
 * cast, qui demande son format à la connexion. Sans application, l'affectation
 * échoue avant même que le calcul testé ne soit atteint. La connexion n'est pas
 * ouverte pour autant : PDO reste paresseux tant qu'aucune requête ne part.
 */
class PeremptionDepotTest extends TestCase
{
    private function piece(?int $mois): ESBTPPieceDossier
    {
        $piece = new ESBTPPieceDossier();
        $piece->duree_validite_mois = $mois;

        return $piece;
    }

    private function depot(array $attributs): ESBTPPieceDeposee
    {
        $depot = new ESBTPPieceDeposee();

        foreach ($attributs as $cle => $valeur) {
            $depot->{$cle} = $valeur;
        }

        return $depot;
    }

    public function test_une_piece_sans_duree_ne_perime_jamais(): void
    {
        // Le nul veut dire « ne périme jamais ». Le remplacer par zéro — ce que
        // ferait un `?? 0` bien intentionné — périmerait tout à l'instant du
        // dépôt, et le guichet rappellerait toute l'école.
        $depot = $this->depot(['date_delivrance' => Carbon::parse('1990-01-01')]);

        $this->assertFalse($depot->estPerime($this->piece(null)));
        $this->assertFalse($depot->estPerime($this->piece(0)));
    }

    public function test_la_validite_court_depuis_la_delivrance_pas_depuis_le_depot(): void
    {
        // C'est tout l'intérêt de la colonne. Un extrait délivré il y a sept ans
        // et remis ce matin est déjà périmé sous une validité de trois mois ;
        // compter depuis le dépôt le déclarerait valable.
        $depot = $this->depot([
            'date_delivrance' => Carbon::now()->subYears(7),
            'date_depot' => Carbon::now(),
        ]);

        $this->assertTrue($depot->estPerime($this->piece(3)));
    }

    public function test_faute_de_delivrance_on_retombe_sur_le_depot(): void
    {
        $recent = $this->depot(['date_depot' => Carbon::now()->subMonth()]);
        $ancien = $this->depot(['date_depot' => Carbon::now()->subMonths(10)]);

        $this->assertFalse($recent->estPerime($this->piece(3)));
        $this->assertTrue($ancien->estPerime($this->piece(3)));
    }

    public function test_un_depot_sans_aucune_date_n_est_pas_declare_perime(): void
    {
        // Ne rien savoir n'est pas savoir que c'est périmé. Déclarer périmé un
        // dépôt dont l'école n'a noté aucune date ferait rappeler des étudiants
        // parfaitement en règle.
        $depot = $this->depot([]);

        $this->assertFalse($depot->estPerime($this->piece(3)));
    }

    public function test_seul_un_depot_valide_et_non_perime_alimente_le_stock(): void
    {
        // « Déposée » n'est PAS soldée : l'étudiant a remis quelque chose, nul
        // n'a encore dit que c'était la bonne pièce.
        $piece = $this->piece(null);

        $valide = $this->depot(['etat' => EtatPieceDossier::VALIDEE]);
        $remise = $this->depot(['etat' => EtatPieceDossier::DEPOSEE]);
        $refusee = $this->depot(['etat' => EtatPieceDossier::REFUSEE]);
        $perimee = $this->depot([
            'etat' => EtatPieceDossier::VALIDEE,
            'date_delivrance' => Carbon::now()->subYears(3),
        ]);

        $this->assertTrue($valide->compteDansLeStock($piece));
        $this->assertFalse($remise->compteDansLeStock($piece));
        $this->assertFalse($refusee->compteDansLeStock($piece));
        $this->assertFalse($perimee->compteDansLeStock($this->piece(6)));
    }

    public function test_le_vocabulaire_des_etats_est_clos(): void
    {
        // La garde du motif, en base, ne vaut que si les états sont clos : une
        // écriture posant « REFUSEE » passerait à côté d'elle. Si ce compte
        // change, la contrainte CHECK de la migration doit changer avec.
        $this->assertSame(
            ['attendue', 'deposee', 'validee', 'refusee'],
            EtatPieceDossier::values()
        );
    }
}
