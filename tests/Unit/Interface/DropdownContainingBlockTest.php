<?php

namespace Tests\Unit\Interface;

use PHPUnit\Framework\TestCase;

/**
 * Le layout force « strategy: fixed » sur tous les menus deroulants, pour
 * qu'ils echappent aux parents qui les coupent. Cette strategie ne tient que
 * si aucun ancetre ne cree de bloc conteneur.
 *
 * backdrop-filter, filter, transform, perspective, contain: paint et
 * will-change en creent un. Un menu se retrouve alors positionne par rapport
 * a cet ancetre au lieu du viewport : il s'ouvre decale, puis saute et
 * clignote a chaque recalcul de Popper.
 *
 * Ce test lit la feuille de style comme une donnee, pas comme du code : il
 * verifie qu'aucune de ces proprietes n'est reintroduite sur la navbar ni sur
 * la barre laterale, qui contiennent l'un et l'autre des menus.
 */
class DropdownContainingBlockTest extends TestCase
{
    private const PIEGES = [
        'backdrop-filter',
        '-webkit-backdrop-filter',
        'perspective',
        'will-change',
    ];

    /** Conteneurs qui hebergent des menus deroulants. */
    private const CONTENEURS = [
        '.nextadmin-navbar',
        '.nextadmin-sidebar',
    ];

    private function bloc(string $css, string $selecteur): string
    {
        $debut = strpos($css, $selecteur.' {');
        $this->assertNotFalse($debut, "Selecteur {$selecteur} introuvable : la feuille a ete restructuree, ce test doit etre remis a jour.");

        $fin = strpos($css, '}', $debut);

        return substr($css, $debut, $fin - $debut);
    }

    public function test_aucun_bloc_conteneur_sur_les_conteneurs_de_menus(): void
    {
        $chemin = __DIR__.'/../../../public/css/nextadmin.css';
        $this->assertFileExists($chemin);

        $css = file_get_contents($chemin);

        foreach (self::CONTENEURS as $selecteur) {
            $bloc = $this->bloc($css, $selecteur);

            foreach (self::PIEGES as $propriete) {
                // Les commentaires citent ces proprietes pour expliquer la
                // regle : on ne regarde que les declarations reelles.
                $sansCommentaires = preg_replace('#/\*.*?\*/#s', '', $bloc);

                $this->assertStringNotContainsString(
                    $propriete.':',
                    $sansCommentaires,
                    "{$selecteur} declare {$propriete} : cette propriete cree un bloc conteneur "
                    ."et casse le positionnement des menus deroulants qu'il contient. "
                    ."Deplacer l'effet sur une couche enfant en position: absolute."
                );
            }
        }
    }
}
