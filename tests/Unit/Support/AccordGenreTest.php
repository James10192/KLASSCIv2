<?php

namespace Tests\Unit\Support;

use App\Support\AccordGenre;
use PHPUnit\Framework\TestCase;

/**
 * L'accord au genre — sans base de données, sans application.
 *
 * Ce calcul décide de ce qu'une famille lit sur le document qu'on lui remet.
 * Il tient en une table et deux fonctions, et il se teste en mémoire.
 */
class AccordGenreTest extends TestCase
{
    public function test_le_masculin_ne_bouge_pas(): void
    {
        $this->assertSame('Affecté', AccordGenre::accorder('Affecté', 'M'));
        $this->assertSame('Non affecté', AccordGenre::accorder('Non affecté', 'M'));
    }

    public function test_le_feminin_prend_sa_forme(): void
    {
        $this->assertSame('Affectée', AccordGenre::accorder('Affecté', 'F'));
        $this->assertSame('Réaffectée', AccordGenre::accorder('Réaffecté', 'F'));
        $this->assertSame('Non affectée', AccordGenre::accorder('Non affecté', 'F'));
    }

    public function test_le_francais_n_ajoute_pas_toujours_un_e(): void
    {
        // C'est la raison d'être de la table. « Ajouter un e » donnerait
        // « nouveaue », « anciene », « boursiere », « admis e ».
        $this->assertSame('Nouvelle', AccordGenre::accorder('Nouveau', 'F'));
        $this->assertSame('Ancienne', AccordGenre::accorder('Ancien', 'F'));
        $this->assertSame('Boursière', AccordGenre::accorder('Boursier', 'F'));
        $this->assertSame('Admise', AccordGenre::accorder('Admis', 'F'));
        $this->assertSame('Exclue', AccordGenre::accorder('Exclu', 'F'));
    }

    public function test_la_casse_d_origine_est_conservee(): void
    {
        // Sans cela, un titre en capitales redescendrait en minuscules au milieu
        // d'une ligne.
        $this->assertSame('AFFECTÉE', AccordGenre::accorder('AFFECTÉ', 'F'));
        $this->assertSame('affectée', AccordGenre::accorder('affecté', 'F'));
        $this->assertSame('Affectée', AccordGenre::accorder('Affecté', 'F'));
    }

    public function test_un_mot_inconnu_ressort_intact(): void
    {
        // On ne devine pas. Rendre le masculin est une imprécision ; inventer un
        // féminin serait une faute imprimée sur un document officiel.
        $this->assertSame('Titulaire', AccordGenre::accorder('Titulaire', 'F'));
        $this->assertSame('Bachelier ès sciences', AccordGenre::accorder('Bachelier ès sciences', 'F'));
    }

    public function test_un_genre_absent_ou_inconnu_reste_au_masculin(): void
    {
        // Le masculin est le défaut du français quand le genre n'est pas connu,
        // et c'est aussi celui d'un groupe mixte.
        $this->assertSame('Affecté', AccordGenre::accorder('Affecté', null));
        $this->assertSame('Affecté', AccordGenre::accorder('Affecté', ''));
        $this->assertSame('Affecté', AccordGenre::accorder('Affecté', 'inconnu'));
    }

    public function test_les_formes_libres_du_sexe_sont_lues(): void
    {
        // La colonne est un enum('M','F'), mais les imports ont fait passer
        // d'autres formes. Traiter « Féminin » comme un masculin serait pire que
        // de ne rien accorder.
        foreach (['F', 'f', 'Féminin', 'FEMININ', 'femme', 'Fille'] as $forme) {
            $this->assertSame('Affectée', AccordGenre::accorder('Affecté', $forme), $forme);
        }
        foreach (['M', 'm', 'Masculin', 'homme', 'garçon'] as $forme) {
            $this->assertSame('Affecté', AccordGenre::accorder('Affecté', $forme), $forme);
        }
    }

    public function test_une_phrase_composee_s_accorde_sans_se_contredire(): void
    {
        // « non affecté » doit être vu AVANT « affecté », sinon on obtiendrait
        // « non affectée » par le hasard de l'ordre plutôt que par la règle.
        $this->assertSame('Nouvelle · Non affectée', AccordGenre::accorderPhrase('Nouveau · Non affecté', 'F'));
        $this->assertSame('Ancienne, réinscrite', AccordGenre::accorderPhrase('Ancien, réinscrit', 'F'));
    }

    public function test_une_negation_s_accorde_par_le_mot_qu_elle_nie(): void
    {
        // « Non inscrit » n'est pas dans la table ; c'est « inscrit » qui l'est,
        // et la phrase le trouve. Sans cela le listing aurait affiche « Non
        // inscrit » sous le nom d'une fille.
        $this->assertSame('Non inscrite', AccordGenre::accorderPhrase('Non inscrit', 'F'));
        $this->assertSame('Non inscrit', AccordGenre::accorderPhrase('Non inscrit', 'M'));
    }

    public function test_l_accord_ne_deborde_pas_sur_un_mot_plus_long(): void
    {
        // « inscrit » ne doit pas transformer « inscription », ni « né » toucher
        // « née » deja accorde, ni « ancien » entamer « ancienneté ».
        $this->assertSame("Dossier d'inscription", AccordGenre::accorderPhrase("Dossier d'inscription", 'F'));
        $this->assertSame('Ancienneté', AccordGenre::accorderPhrase('Ancienneté', 'F'));
    }

    public function test_le_sexe_s_ecrit_en_toutes_lettres(): void
    {
        // Les fiches imprimaient « M » et « F » bruts. Sur un document que signe
        // une famille, cela se lit comme un code de base de données.
        $this->assertSame('Féminin', AccordGenre::libelle('F'));
        $this->assertSame('Masculin', AccordGenre::libelle('M'));
        $this->assertNull(AccordGenre::libelle(null));
        $this->assertNull(AccordGenre::libelle('  '));
    }
}
