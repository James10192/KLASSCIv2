<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Components\Concerns\EvalueLeScriptAuSelect;

/**
 * Ou <x-au-select> pose son menu, et dans quel repere il le calcule.
 *
 * CE QUE CA PROTEGE. Le menu est en `position: fixed`, dont les coordonnees
 * s'entendent dans le viewport de MISE EN PAGE — le meme repere que celui de
 * `getBoundingClientRect()`. Le composant mesurait pourtant la place disponible
 * avec `visualViewport.width/height`, qui decrivent le viewport VISUEL : ce qui
 * reste affiche apres un zoom.
 *
 * Sans zoom, les deux coincident et rien ne se voit. A 200 % sur un ecran de
 * 1366 px, `visualViewport.width` vaut ~683 pendant que `triggerRect.left`
 * reste exprime sur 1366. La borne
 *
 *     left = max(marge, min(gauche_du_champ, largeur - menu - marge))
 *
 * devenait alors `min(900, 683 - 240 - 12)` = 431 : un champ pose a 900 px
 * voyait son menu partir 469 px plus a gauche. La hauteur disponible etait
 * fausse de moitie dans la foulee, donc aussi le sens d'ouverture.
 *
 * Marcel : « quand je zoome trop, le select, quand l'ecran est petit, ca bug
 * tres mal. » Quatre correctifs successifs avaient deja ete poses sur ce
 * composant sans jamais toucher a ce calcul.
 *
 * La reponse n'est pas un cinquieme correctif : le calcul ne recoit plus qu'UNE
 * vue, et ne peut donc plus melanger les deux reperes. Ce test execute le
 * script REELLEMENT livre par le composant, extrait du Blade et evalue sous
 * Node — pas une copie de sa logique, qui continuerait de passer une fois le
 * composant casse.
 */
class AuSelectGeometrieMenuTest extends TestCase
{
    use EvalueLeScriptAuSelect;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->cheminNode() === null) {
            $this->markTestSkipped('Node introuvable : impossible d\'executer le script du composant.');
        }
    }

    // ------------------------------------------------------------------
    // Le defaut que ce test existe pour empecher
    // ------------------------------------------------------------------

    public function test_un_champ_a_droite_garde_son_menu_sous_lui_sur_un_ecran_zoome(): void
    {
        // Un ecran de 1366 px zoome a 200 % : le viewport de mise en page fait
        // 683 px de large, et le champ s'y trouve a 420 px. Tout est exprime
        // dans le MEME repere — c'est precisement ce que le melange precedent
        // ne garantissait pas.
        $geometrie = $this->geometrie(
            declencheur: ['top' => 300, 'bottom' => 344, 'left' => 420, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 384],
        );

        $this->assertSame(
            420,
            $geometrie['gauche'],
            'Le menu doit rester sous son champ, pas se plaquer au bord gauche.'
        );
    }

    public function test_melanger_les_deux_reperes_plaque_le_menu_a_gauche(): void
    {
        // La demonstration du defaut, executee plutot qu'affirmee.
        //
        // On reproduit ce que faisait l'ancien code : la position du champ dans
        // le repere de MISE EN PAGE (900 sur un ecran de 1366) et la largeur du
        // viewport VISUEL apres un zoom a 200 % (683). Le resultat montre le
        // symptome de Marcel — le menu part a gauche, tres loin de son champ.
        //
        // Ce test ne verifie pas un comportement souhaitable : il fixe le prix
        // de l'erreur, pour que personne ne rebranche `visualViewport` sur cette
        // mesure en croyant corriger autre chose.
        $melange = $this->geometrie(
            declencheur: ['top' => 300, 'bottom' => 344, 'left' => 900, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 384],
        );

        $this->assertSame(431, $melange['gauche'], 'C\'est le decalage que voyait Marcel.');
        $this->assertLessThan(
            900,
            $melange['gauche'],
            'Le menu se retrouve a des centaines de pixels a gauche de son champ.'
        );

        // Le meme champ, mesure dans SON repere : le menu reste sous lui.
        $correct = $this->geometrie(
            declencheur: ['top' => 300, 'bottom' => 344, 'left' => 900, 'width' => 240],
            vue: ['largeur' => 1366, 'hauteur' => 768],
        );

        $this->assertSame(900, $correct['gauche']);
    }

    public function test_le_menu_ne_deborde_pas_a_droite(): void
    {
        // Champ colle au bord droit : la seule raison legitime de decaler le
        // menu vers la gauche.
        $geometrie = $this->geometrie(
            declencheur: ['top' => 100, 'bottom' => 144, 'left' => 600, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 768],
            largeurNaturelle: 300,
        );

        $this->assertSame(683 - 300 - 12, $geometrie['gauche']);
        $this->assertLessThanOrEqual(
            683,
            $geometrie['gauche'] + $geometrie['largeur'],
            'Le menu doit tenir dans la vue.'
        );
    }

    public function test_le_menu_reste_visible_meme_colle_au_bord_gauche(): void
    {
        $geometrie = $this->geometrie(
            declencheur: ['top' => 100, 'bottom' => 144, 'left' => 2, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 768],
        );

        $this->assertSame(12, $geometrie['gauche'], 'La marge de 12 px est le minimum.');
    }

    // ------------------------------------------------------------------
    // Le sens d'ouverture
    // ------------------------------------------------------------------

    public function test_le_menu_s_ouvre_vers_le_haut_quand_le_bas_est_trop_court(): void
    {
        // Champ en bas d'un ecran court — le cas d'un portable zoome.
        $geometrie = $this->geometrie(
            declencheur: ['top' => 320, 'bottom' => 364, 'left' => 100, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 384],
        );

        $this->assertTrue($geometrie['versLeHaut'], 'Il ne reste que 8 px sous le champ.');
        $this->assertStringContainsString('bottom:', $geometrie['style']);
        $this->assertStringContainsString('top:auto', $geometrie['style']);
    }

    public function test_le_menu_s_ouvre_vers_le_bas_quand_la_place_y_est(): void
    {
        $geometrie = $this->geometrie(
            declencheur: ['top' => 100, 'bottom' => 144, 'left' => 100, 'width' => 240],
            vue: ['largeur' => 1366, 'hauteur' => 768],
        );

        $this->assertFalse($geometrie['versLeHaut']);
        $this->assertStringContainsString('top:150px', $geometrie['style'], 'Bas du champ + 6 px d\'ecart.');
        $this->assertStringContainsString('bottom:auto', $geometrie['style']);
    }

    // ------------------------------------------------------------------
    // Les mesures se figent : c'est ce qui empeche le menu de sauter
    // ------------------------------------------------------------------

    public function test_les_mesures_fournies_sont_reutilisees_telles_quelles(): void
    {
        // Au defilement, on ne remesure pas : on DEPLACE. Recalculer la taille
        // et le sens a chaque pixel faisait respirer le menu et le faisait
        // basculer d'un cote a l'autre pres du seuil.
        $geometrie = $this->geometrie(
            declencheur: ['top' => 320, 'bottom' => 364, 'left' => 100, 'width' => 240],
            vue: ['largeur' => 683, 'hauteur' => 384],
            mesures: ['largeur' => 300, 'hauteurMax' => 250, 'versLeHaut' => false],
        );

        $this->assertSame(300, $geometrie['largeur']);
        $this->assertSame(250, $geometrie['hauteurMax']);
        $this->assertFalse(
            $geometrie['versLeHaut'],
            'Le sens fige doit primer, meme quand la place manque desormais en bas.'
        );
    }

    public function test_la_hauteur_est_plafonnee(): void
    {
        $geometrie = $this->geometrie(
            declencheur: ['top' => 100, 'bottom' => 144, 'left' => 100, 'width' => 240],
            vue: ['largeur' => 1366, 'hauteur' => 2000],
        );

        $this->assertSame(380, $geometrie['hauteurMax'], 'Un menu ne prend jamais tout l\'ecran.');
    }

    // ------------------------------------------------------------------
    // Le repere, verifie sur la source livree
    // ------------------------------------------------------------------

    public function test_la_mesure_lit_le_viewport_de_mise_en_page(): void
    {
        // La garde qui compte vraiment. Le calcul est desormais pur et ne voit
        // qu'une vue ; c'est l'APPELANT qui choisit le repere, et il n'y en a
        // qu'un seul de juste pour du `position: fixed`.
        $script = $this->scriptDuComposant();

        $this->assertStringContainsString(
            'racine.clientWidth',
            $script,
            'La largeur doit venir de documentElement, dans le repere de getBoundingClientRect().'
        );
        $this->assertStringContainsString('racine.clientHeight', $script);

        $mesure = substr($script, strpos($script, 'positionMenu(remeasure'));
        $mesure = substr($mesure, 0, strpos($mesure, 'get currentValue'));

        $this->assertStringNotContainsString(
            'visualViewport?.width',
            $mesure,
            'visualViewport decrit le viewport VISUEL : le melanger aux coordonnees '
            .'de mise en page plaque le menu a gauche des qu on zoome.'
        );
        $this->assertStringNotContainsString('visualViewport?.height', $mesure);
    }

    public function test_le_zoom_declenche_toujours_une_remesure(): void
    {
        // Seule la MESURE change de repere : l'evenement, lui, reste le bon.
        // Un zoom doit continuer de provoquer un repositionnement.
        $script = $this->scriptDuComposant();

        $this->assertStringContainsString(
            "window.visualViewport?.addEventListener('resize', this._remeasureMenu",
            $script,
            'Le zoom reste l\'evenement qui declenche la remesure.'
        );
    }

    // ------------------------------------------------------------------

    /**
     * @param  array<string, int>  $declencheur
     * @param  array<string, int>  $vue
     * @param  array<string, mixed>|null  $mesures
     * @return array<string, mixed>
     */
    private function geometrie(
        array $declencheur,
        array $vue,
        ?array $mesures = null,
        int $largeurNaturelle = 240,
    ): array {
        $programme = sprintf(
            'var r = window.auSelectGeometrieMenu(%s, %s, %s, %d, false);'
            .'var g = /left:(-?\d+(?:\.\d+)?)px/.exec(r.style);'
            .'var l = /width:(-?\d+(?:\.\d+)?)px/.exec(r.style);'
            .'console.log(JSON.stringify({'
            .'  gauche: g ? Number(g[1]) : null,'
            .'  largeur: l ? Number(l[1]) : null,'
            .'  hauteurMax: r.mesures.hauteurMax,'
            .'  versLeHaut: r.mesures.versLeHaut,'
            .'  style: r.style'
            .'}));',
            json_encode($declencheur),
            json_encode($vue),
            $mesures === null ? 'null' : json_encode($mesures),
            $largeurNaturelle,
        );

        $sortie = $this->evalueAvecLeComposant($programme);
        $decode = json_decode($sortie, true);

        $this->assertIsArray($decode, 'Sortie inattendue du script : '.$sortie);

        return $decode;
    }
}
