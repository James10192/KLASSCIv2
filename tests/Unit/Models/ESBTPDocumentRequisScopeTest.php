<?php

namespace Tests\Unit\Models;

use App\Models\ESBTPDocumentRequis;
use PHPUnit\Framework\TestCase;

/**
 * Portee d'une piece du catalogue — logique pure, sans base de donnees.
 *
 * C'est la regle la plus facile a casser sans s'en rendre compte : une piece qui
 * cesse silencieusement de s'appliquer ne provoque aucune erreur, elle disparait
 * juste du dossier de l'etudiant.
 */
class ESBTPDocumentRequisScopeTest extends TestCase
{
    private function piece(?array $filieres, ?array $niveaux): ESBTPDocumentRequis
    {
        return new ESBTPDocumentRequis([
            'libelle'     => 'Extrait de naissance',
            'filiere_ids' => $filieres,
            'niveau_ids'  => $niveaux,
        ]);
    }

    public function test_une_portee_nulle_vaut_pour_tout_le_monde(): void
    {
        $piece = $this->piece(null, null);

        $this->assertTrue($piece->concerneToutesFilieres());
        $this->assertTrue($piece->concerneTousNiveaux());
        $this->assertTrue($piece->sApplique(12, 3));
        $this->assertTrue($piece->sApplique(null, null));
    }

    public function test_un_tableau_vide_vaut_aussi_pour_tout_le_monde(): void
    {
        $piece = $this->piece([], []);

        $this->assertTrue($piece->sApplique(12, 3));
    }

    public function test_une_filiere_listee_restreint_aux_seules_filieres_citees(): void
    {
        $piece = $this->piece([4, 7], null);

        $this->assertTrue($piece->sApplique(4, 99));
        $this->assertTrue($piece->sApplique(7, null));
        $this->assertFalse($piece->sApplique(5, 99));
    }

    /**
     * Une inscription sans filiere connue ne doit PAS heriter d'une piece
     * restreinte : sinon le guichet reclamerait une piece hors de propos.
     */
    public function test_une_inscription_sans_filiere_echappe_a_une_piece_restreinte(): void
    {
        $piece = $this->piece([4], null);

        $this->assertFalse($piece->sApplique(null, 3));
    }

    public function test_les_deux_axes_se_cumulent(): void
    {
        $piece = $this->piece([4], [1]);

        $this->assertTrue($piece->sApplique(4, 1));
        $this->assertFalse($piece->sApplique(4, 2));
        $this->assertFalse($piece->sApplique(5, 1));
    }

    /**
     * Les identifiants arrivent parfois en chaine (JSON legacy, formulaire).
     * Une comparaison stricte sans normalisation les ferait tous echouer.
     */
    public function test_les_identifiants_en_chaine_sont_reconnus(): void
    {
        $piece = $this->piece(['4', '7'], null);

        $this->assertTrue($piece->sApplique(4, null));
    }

    public function test_le_libelle_de_portee_nomme_les_entites_connues(): void
    {
        $piece = $this->piece([4], [1]);

        $this->assertSame(
            'Genie Civil / Licence 1',
            $piece->libelleScope([4 => 'Genie Civil'], [1 => 'Licence 1'])
        );
    }

    public function test_le_libelle_de_portee_annonce_l_universalite(): void
    {
        $this->assertSame(
            'Toutes filieres / Tous niveaux',
            $this->piece(null, null)->libelleScope()
        );
    }
}
