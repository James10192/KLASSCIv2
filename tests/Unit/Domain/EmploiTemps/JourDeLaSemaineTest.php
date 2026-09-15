<?php

namespace Tests\Unit\Domain\EmploiTemps;

use App\Domain\EmploiTemps\JourDeLaSemaine;
use PHPUnit\Framework\TestCase;

/**
 * Le jour d'une séance, quel que soit le format sous lequel il a été écrit.
 *
 * Sans base ni conteneur : c'est de l'arithmétique sur une chaîne, et ces cas
 * doivent rester exécutables là où MySQL ne l'est pas.
 */
class JourDeLaSemaineTest extends TestCase
{
    // --- Le défaut qui a motivé la classe ---

    public function test_l_entier_et_le_libelle_designent_le_meme_jour(): void
    {
        // Le cœur du problème. `esbtp_seance_cours.jour` reçoit `1` depuis un
        // écran et `'Lundi'` depuis l'autre, et en PHP 8 `1 == 'Lundi'` est
        // FAUX : c'est l'entier qui est converti en chaîne. Deux séances des
        // deux chemins de saisie ne se voyaient donc jamais.
        $this->assertFalse(1 == 'Lundi', 'Le comportement PHP que cette classe corrige a changé.');

        $this->assertTrue(JourDeLaSemaine::memeJour(1, 'Lundi'));
        $this->assertTrue(JourDeLaSemaine::memeJour('Lundi', 1));
    }

    public function test_deux_jours_differents_ne_concordent_pas(): void
    {
        $this->assertFalse(JourDeLaSemaine::memeJour(1, 'Mardi'));
        $this->assertFalse(JourDeLaSemaine::memeJour(1, 2));
        $this->assertFalse(JourDeLaSemaine::memeJour('Lundi', 'Samedi'));
    }

    public function test_deux_jours_inconnus_ne_concordent_pas(): void
    {
        // Les tenir pour égaux replierait toutes les données abîmées les unes
        // sur les autres — c'est le défaut `null == null` de la détection de
        // conflits, transposé ici.
        $this->assertFalse(JourDeLaSemaine::memeJour(null, null));
        $this->assertFalse(JourDeLaSemaine::memeJour('', ''));
        $this->assertFalse(JourDeLaSemaine::memeJour('Dimanche', 'Dimanche'));
    }

    // --- Le rang ---

    public function test_les_six_jours_ouvres_ont_leur_rang(): void
    {
        $attendus = ['Lundi' => 0, 'Mardi' => 1, 'Mercredi' => 2, 'Jeudi' => 3, 'Vendredi' => 4, 'Samedi' => 5];

        foreach ($attendus as $libelle => $rang) {
            $this->assertSame($rang, JourDeLaSemaine::rang($libelle), $libelle);
            $this->assertSame($rang, JourDeLaSemaine::rang($rang + 1), "entier de {$libelle}");
        }
    }

    public function test_la_casse_et_les_espaces_ne_comptent_pas(): void
    {
        $this->assertSame(0, JourDeLaSemaine::rang('LUNDI'));
        $this->assertSame(0, JourDeLaSemaine::rang('lundi'));
        $this->assertSame(0, JourDeLaSemaine::rang('  Lundi  '));
    }

    public function test_l_anglais_est_accepte(): void
    {
        // Reproduit les résolveurs remplacés. Rien dans le dépôt ne l'écrit
        // aujourd'hui ; le retirer serait un pari sur des données non vues.
        $this->assertSame(0, JourDeLaSemaine::rang('Monday'));
        $this->assertSame(5, JourDeLaSemaine::rang('saturday'));
    }

    public function test_un_entier_sous_forme_de_chaine_est_accepte(): void
    {
        // Une valeur qui vient d'une requête HTTP est toujours une chaîne.
        $this->assertSame(0, JourDeLaSemaine::rang('1'));
        $this->assertSame(5, JourDeLaSemaine::rang('6'));
    }

    public function test_une_ecriture_inconnue_ne_rend_aucun_rang(): void
    {
        // Et surtout PAS zéro. Deux sites du dépôt repliaient sur `0`, ce qui
        // faisait passer un jour inconnu pour un lundi et posait une date
        // fausse sans le dire.
        $this->assertNull(JourDeLaSemaine::rang(null));
        $this->assertNull(JourDeLaSemaine::rang(''));
        $this->assertNull(JourDeLaSemaine::rang('   '));
        $this->assertNull(JourDeLaSemaine::rang('n importe quoi'));
        $this->assertNull(JourDeLaSemaine::rang([]));
    }

    public function test_le_dimanche_et_les_bornes_ne_sont_pas_des_jours_connus(): void
    {
        // Aucun des deux formulaires ne propose le dimanche, et les deux
        // résolveurs remplacés s'arrêtaient au samedi. Rendre `6` pour `7`
        // inventerait une correspondance que personne n'a décidée.
        $this->assertNull(JourDeLaSemaine::rang(7));
        $this->assertNull(JourDeLaSemaine::rang('Dimanche'));
        $this->assertNull(JourDeLaSemaine::rang(0));
        $this->assertNull(JourDeLaSemaine::rang(-1));
    }

    // --- Le libellé ---

    public function test_le_libelle_est_le_meme_quelle_que_soit_l_ecriture(): void
    {
        $this->assertSame('Mercredi', JourDeLaSemaine::libelle(3));
        $this->assertSame('Mercredi', JourDeLaSemaine::libelle('mercredi'));
        $this->assertSame('Mercredi', JourDeLaSemaine::libelle('Wednesday'));
    }

    public function test_une_ecriture_inconnue_n_a_pas_de_libelle(): void
    {
        $this->assertNull(JourDeLaSemaine::libelle('Dimanche'));
    }

    // --- Le décalage depuis le début d'un emploi du temps ---

    public function test_depuis_un_lundi_le_decalage_est_le_rang(): void
    {
        // Le cas où le raccourci `jour - 1` était juste, et qui explique qu'on
        // ne l'ait jamais vu échouer : les emplois du temps commencent
        // généralement un lundi.
        $this->assertSame(0, JourDeLaSemaine::decalageDepuis('Lundi', 1));
        $this->assertSame(2, JourDeLaSemaine::decalageDepuis('Mercredi', 1));
        $this->assertSame(5, JourDeLaSemaine::decalageDepuis('Samedi', 1));
    }

    public function test_depuis_un_autre_jour_le_decalage_reste_dans_la_semaine_ouverte(): void
    {
        // Emploi du temps commençant un MERCREDI (ISO 3). Le raccourci
        // `jour - 1` placerait « Lundi » sur le mercredi même, deux jours AVANT
        // l'ouverture de la période. La bonne réponse est le lundi suivant,
        // cinq jours plus tard.
        $this->assertSame(5, JourDeLaSemaine::decalageDepuis('Lundi', 3));
        $this->assertSame(0, JourDeLaSemaine::decalageDepuis('Mercredi', 3));
        $this->assertSame(3, JourDeLaSemaine::decalageDepuis('Samedi', 3));
    }

    public function test_le_decalage_reste_toujours_dans_la_semaine(): void
    {
        foreach (range(1, 7) as $departIso) {
            foreach (range(1, 6) as $jour) {
                $decalage = JourDeLaSemaine::decalageDepuis($jour, $departIso);

                $this->assertGreaterThanOrEqual(0, $decalage, "jour {$jour} depuis {$departIso}");
                $this->assertLessThanOrEqual(6, $decalage, "jour {$jour} depuis {$departIso}");
            }
        }
    }

    public function test_un_jour_illisible_n_a_pas_de_decalage(): void
    {
        // Et non zéro : une date fausse se propagerait aux heures de
        // l'enseignant et à son émargement, où personne ne la rattraperait.
        $this->assertNull(JourDeLaSemaine::decalageDepuis('Dimanche', 1));
        $this->assertNull(JourDeLaSemaine::decalageDepuis(null, 1));
    }

    // --- La liste des jours, pour que les écrans cessent de la recopier ---

    public function test_les_libelles_couvrent_la_semaine_ouvree_indexee_par_l_entier(): void
    {
        $this->assertSame(
            [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'],
            JourDeLaSemaine::libelles()
        );
    }

    public function test_chaque_libelle_se_relit_par_sa_propre_cle(): void
    {
        // Le contrat dont dépendent les écrans qui bouclent dessus : la clé
        // proposée en filtre doit être une écriture que `rang()` comprend.
        foreach (JourDeLaSemaine::libelles() as $valeur => $libelle) {
            $this->assertSame($libelle, JourDeLaSemaine::libelle($valeur));
            $this->assertNotNull(JourDeLaSemaine::rang($valeur));
        }
    }

    // --- Interroger une colonne non normalisée ---

    public function test_les_ecritures_d_un_jour_couvrent_les_deux_formats(): void
    {
        $ecritures = JourDeLaSemaine::ecrituresDe(1);

        $this->assertContains('1', $ecritures, 'le format entier de la liste des séances');
        $this->assertContains('Lundi', $ecritures, 'le format écrit par le formulaire de l\'emploi du temps');
        $this->assertContains('lundi', $ecritures, 'la colonne n\'est pas normalisée en casse');
    }

    public function test_les_ecritures_sont_les_memes_quel_que_soit_le_point_de_depart(): void
    {
        $this->assertSame(
            JourDeLaSemaine::ecrituresDe(1),
            JourDeLaSemaine::ecrituresDe('Lundi')
        );
    }

    public function test_les_ecritures_ne_contiennent_pas_de_doublon(): void
    {
        $ecritures = JourDeLaSemaine::ecrituresDe('Samedi');

        $this->assertSame(array_values(array_unique($ecritures)), $ecritures);
    }

    public function test_les_ecritures_n_incluent_pas_l_anglais(): void
    {
        // Volontaire : cette liste sert à interroger la colonne, et rien dans le
        // dépôt n'y écrit l'anglais. L'y mettre élargirait chaque requête sans
        // rien retrouver. La lecture, elle, continue de l'accepter.
        $this->assertNotContains('Monday', JourDeLaSemaine::ecrituresDe(1));
    }

    public function test_une_ecriture_inconnue_ne_donne_aucune_ecriture(): void
    {
        // Le contrat compte : `whereIn('jour', [])` ne rend aucune ligne, donc
        // un filtre sur un jour inconnu vide la liste au lieu de l'élargir.
        $this->assertSame([], JourDeLaSemaine::ecrituresDe('Dimanche'));
    }
}
