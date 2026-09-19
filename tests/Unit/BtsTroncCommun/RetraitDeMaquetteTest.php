<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Domain\BtsTroncCommun\RetraitDeMaquette;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Tests\TestCase;

/**
 * Doublure qui NOTE ce qu'on lui demande d'effacer, sans rien effacer.
 *
 * Ecrite a la main plutot que mockee : ce qu'on veut verifier n'est pas
 * « la methode a ete appelee », c'est « ces couples-la, et aucun autre ».
 */
class LiaisonsQuiNotentLesRetraits extends LiaisonsDeMatiere
{
    /** @var array<int, array{0: int, 1: int, 2: int}> */
    public array $appels = [];

    public function retirer(int $matiereId, int $filiereId, int $niveauId): array
    {
        $this->appels[] = [$matiereId, $filiereId, $niveauId];

        return ['canonique' => 1, 'places_semestre' => 2];
    }
}

/**
 * Le chemin destructeur de la maquette, eprouve sans ECRITURE en base.
 *
 * `preparer()` interroge trois tables et n'est pas testable ici. `appliquer()`,
 * lui, DECIDE CE QUI EST SUPPRIME a partir du seul plan qu'on lui passe : c'est
 * du branchement pur, enveloppe d'une transaction, et c'est la moitie qui efface
 * des lignes de `esbtp_matiere_filiere_niveau` et
 * `esbtp_maquette_places_semestre`.
 *
 * CE FICHIER A DIT « eprouve sans base » PENDANT UN COMMIT DE TROP. La
 * transaction posee sur `appliquer()` exige une connexion PDO vivante : le test
 * n'ecrit rien, mais il ne tourne plus sans MySQL. Le docblock de classe est ce
 * que les outils affichent — le laisser dire le contraire rangeait ce fichier
 * dans la mauvaise categorie pour qui cherche « quels tests tournent sans
 * base ? ». Voir le commentaire dans le corps de la classe.
 *
 * Le defaut fondateur que ces tests gelent : le compteur annoncait
 * « 3 matiere(s) retiree(s) » pour un lot dont une seule etait dans la
 * maquette. Une matiere absente n'est pas une erreur, mais elle n'est pas
 * retiree non plus — et surtout, on ne doit pas aller la supprimer.
 */
class RetraitDeMaquetteTest extends TestCase
{
    // CE TEST BOOTE L'APPLICATION, et c'est un changement assume.
    //
    // Il etendait `PHPUnit\Framework\TestCase` : pas de framework, pas de base,
    // rien que la logique — et c'etait une qualite. Mais `appliquer()` enveloppe
    // desormais son lot dans une transaction, sans quoi un echec au troisieme
    // tour laissait deux lignes effacees et trois intactes. Une facade a besoin
    // d'une application.
    //
    // Les doublures restent des doublures : rien n'est ecrit, aucune table n'est
    // lue. Le test gagne un `BEGIN`/`COMMIT` a vide, et garde tout le reste.

    private function filiere(int $id): ESBTPFiliere
    {
        $filiere = new ESBTPFiliere();
        $filiere->forceFill(['id' => $id]);

        return $filiere;
    }

    private function niveau(int $id): ESBTPNiveauEtude
    {
        $niveau = new ESBTPNiveauEtude();
        $niveau->forceFill(['id' => $id]);

        return $niveau;
    }

    private function retrait(LiaisonsDeMatiere $liaisons): RetraitDeMaquette
    {
        // `ResolutionDeMatiere` n'est jamais appelee par `appliquer()` : une
        // vraie instance suffit, elle ne touche a rien.
        return new RetraitDeMaquette(new ResolutionDeMatiere(), $liaisons);
    }

    public function test_une_matiere_absente_de_la_maquette_n_est_ni_supprimee_ni_comptee(): void
    {
        $liaisons = new LiaisonsQuiNotentLesRetraits();

        $resultat = $this->retrait($liaisons)->appliquer($this->filiere(7), $this->niveau(3), [
            ['matiere_id' => 42, 'dans_la_maquette' => false],
        ]);

        $this->assertSame([], $liaisons->appels, 'Aucune suppression ne doit partir.');
        $this->assertSame(0, $resultat['retirees']);
        $this->assertSame(['canonique' => 0, 'places_semestre' => 0], $resultat['lignes'][0]['retire']);
    }

    public function test_une_matiere_presente_est_retiree_sur_le_couple_demande(): void
    {
        $liaisons = new LiaisonsQuiNotentLesRetraits();

        $resultat = $this->retrait($liaisons)->appliquer($this->filiere(7), $this->niveau(3), [
            ['matiere_id' => 42, 'dans_la_maquette' => true],
        ]);

        $this->assertSame([[42, 7, 3]], $liaisons->appels);
        $this->assertSame(1, $resultat['retirees']);
        $this->assertSame(['canonique' => 1, 'places_semestre' => 2], $resultat['lignes'][0]['retire']);
    }

    public function test_le_compteur_ne_retient_que_les_matieres_reellement_retirees(): void
    {
        $liaisons = new LiaisonsQuiNotentLesRetraits();

        $resultat = $this->retrait($liaisons)->appliquer($this->filiere(7), $this->niveau(3), [
            ['matiere_id' => 1, 'dans_la_maquette' => false],
            ['matiere_id' => 2, 'dans_la_maquette' => true],
            ['matiere_id' => 3, 'dans_la_maquette' => false],
        ]);

        // Le defaut fondateur aurait annonce 3, et serait alle supprimer 1 et 3.
        $this->assertSame(1, $resultat['retirees']);
        $this->assertSame([[2, 7, 3]], $liaisons->appels);
        $this->assertCount(3, $resultat['lignes']);
    }

    public function test_chaque_ligne_rendue_porte_son_compte_rendu_de_retrait(): void
    {
        $liaisons = new LiaisonsQuiNotentLesRetraits();

        $resultat = $this->retrait($liaisons)->appliquer($this->filiere(7), $this->niveau(3), [
            ['matiere_id' => 1, 'dans_la_maquette' => true],
            ['matiere_id' => 2, 'dans_la_maquette' => false],
        ]);

        foreach ($resultat['lignes'] as $ligne) {
            $this->assertArrayHasKey('retire', $ligne);
            $this->assertArrayHasKey('canonique', $ligne['retire']);
            $this->assertArrayHasKey('places_semestre', $ligne['retire']);
        }
    }
}
