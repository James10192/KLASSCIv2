<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPieceDossier;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

/**
 * La règle de portée, sans base de données.
 *
 * Elle tient en une phrase — aucune ligne de portée = tout le monde — et c'est
 * précisément pour cela qu'il faut la tester : une règle simple se réécrit de
 * mémoire, un peu de travers, dans le prochain écran qui en aura besoin.
 */
class ESBTPPieceDossierScopeTest extends TestCase
{
    private function piece(array $filiereIds = [], array $niveauIds = []): ESBTPPieceDossier
    {
        $piece = new ESBTPPieceDossier();

        $piece->setRelation('filieres', new Collection(array_map(
            fn (int $id) => (new ESBTPFiliere())->forceFill(['id' => $id, 'name' => 'Filière ' . $id]),
            $filiereIds
        )));

        $piece->setRelation('niveaux', new Collection(array_map(
            fn (int $id) => (new ESBTPNiveauEtude())->forceFill(['id' => $id, 'name' => 'Niveau ' . $id]),
            $niveauIds
        )));

        return $piece;
    }

    /**
     * Le cas de loin le plus fréquent : la pièce commune à toute l'école. Elle
     * doit valoir pour une filière créée après elle, donc pour n'importe quel
     * identifiant, y compris aucun.
     */
    public function test_sans_portee_la_piece_concerne_tout_le_monde(): void
    {
        $piece = $this->piece();

        $this->assertTrue($piece->concerneToutesFilieres());
        $this->assertTrue($piece->concerneTousNiveaux());

        $this->assertTrue($piece->sApplique(1, 1));
        $this->assertTrue($piece->sApplique(999, 999));
        $this->assertTrue($piece->sApplique(null, null));
    }

    public function test_une_portee_filiere_exclut_les_autres_filieres(): void
    {
        $piece = $this->piece(filiereIds: [3, 4]);

        $this->assertTrue($piece->sApplique(3, 1));
        $this->assertTrue($piece->sApplique(4, 99));
        $this->assertFalse($piece->sApplique(5, 1));
    }

    /**
     * Un étudiant sans filière connue ne peut pas satisfaire une portée qui en
     * exige une : on ne réclame pas au hasard.
     */
    public function test_une_portee_filiere_exclut_l_etudiant_sans_filiere(): void
    {
        $this->assertFalse($this->piece(filiereIds: [3])->sApplique(null, 1));
    }

    /**
     * Les deux portées se cumulent, elles ne s'additionnent pas : cocher la
     * filière A et le niveau 1 vise les étudiants qui sont dans les deux, pas
     * ceux qui sont dans l'un ou dans l'autre.
     */
    public function test_les_deux_portees_se_cumulent(): void
    {
        $piece = $this->piece(filiereIds: [3], niveauIds: [7]);

        $this->assertTrue($piece->sApplique(3, 7));
        $this->assertFalse($piece->sApplique(3, 8));
        $this->assertFalse($piece->sApplique(4, 7));
    }

    public function test_une_portee_de_niveau_seule_laisse_toutes_les_filieres(): void
    {
        $piece = $this->piece(niveauIds: [7]);

        $this->assertTrue($piece->sApplique(1, 7));
        $this->assertTrue($piece->sApplique(42, 7));
        $this->assertFalse($piece->sApplique(1, 8));
    }

    public function test_les_identifiants_sont_compares_en_entiers(): void
    {
        // Les identifiants arrivent parfois en texte, d'un formulaire ou d'une
        // requête : une comparaison stricte sans conversion rendrait faux ce
        // qui est vrai, sans rien signaler.
        $piece = $this->piece(filiereIds: [3]);

        $this->assertSame([3], $piece->filiereIds());
        $this->assertTrue($piece->sApplique((int) '3', null));
    }

    public function test_le_libelle_de_portee_se_lit_au_guichet(): void
    {
        $this->assertSame('Toutes filières / Tous niveaux', $this->piece()->libelleScope());

        $this->assertSame(
            'Filière 3 / Niveau 7',
            $this->piece(filiereIds: [3], niveauIds: [7])->libelleScope()
        );

        $this->assertSame(
            'Filière 3, Filière 4 / Tous niveaux',
            $this->piece(filiereIds: [3, 4])->libelleScope()
        );
    }
}
