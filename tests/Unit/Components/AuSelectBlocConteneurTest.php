<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Components\Concerns\EvalueLeScriptAuSelect;

/**
 * Verifie que <x-au-select> reconnait un ancetre qui fausserait le placement
 * de son menu.
 *
 * Ce qu'il protege : le menu est pose en `position: fixed`, avec des
 * coordonnees lues sur le viewport. Un ancetre porteur de `transform` (ou
 * `filter`, `backdrop-filter`, `perspective`, `contain: layout|paint`,
 * `will-change` citant l'une d'elles) devient le bloc conteneur de ses
 * descendants `fixed` : le navigateur reinterprete alors ces coordonnees par
 * rapport a lui, et le menu part hors ecran.
 *
 * `.card-moderne:hover { transform: translateY(-1px) }` a suffi a faire
 * disparaitre le selecteur d'etudiant de /esbtp/paiements/create. Il reste des
 * dizaines de regles semblables dans les feuilles globales, et il s'en ajoute
 * a chaque page : le composant doit se defendre seul, sans dependre du fait
 * que personne n'ecrira plus jamais un `transform` au-dessus de lui.
 *
 * Le test execute le script REELLEMENT livre par le composant, extrait du
 * Blade et evalue sous Node.
 */
class AuSelectBlocConteneurTest extends TestCase
{
    use EvalueLeScriptAuSelect;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->cheminNode() === null) {
            $this->markTestSkipped('Node introuvable : impossible d\'executer le script du composant.');
        }
    }

    /**
     * @dataProvider styles
     *
     * @param  array<string, mixed>  $style
     */
    public function test_une_propriete_creatrice_de_bloc_conteneur_est_reconnue(
        array $style,
        bool $attendu,
        string $pourquoi
    ): void {
        $this->assertSame($attendu, $this->creeUnBlocConteneur($style), $pourquoi);
    }

    public static function styles(): array
    {
        return [
            // Les six proprietes de la specification.
            'transform pose' => [['transform' => 'matrix(1, 0, 0, 1, 0, -1)'], true,
                'C\'est exactement ce que produit `transform: translateY(-1px)` au survol.'],
            'filter pose' => [['filter' => 'blur(2px)'], true,
                '`filter` cree un bloc conteneur au meme titre que `transform`.'],
            'backdrop-filter pose' => [['backdropFilter' => 'blur(10px)'], true,
                'Les heros premium en `backdrop-filter` sont nombreux dans le produit.'],
            'perspective posee' => [['perspective' => '800px'], true,
                '`perspective` figure dans la meme liste de la specification.'],
            'contain paint' => [['contain' => 'paint'], true,
                '`contain: paint` cree un bloc conteneur.'],
            'contain layout style' => [['contain' => 'layout style'], true,
                'Une valeur composee doit etre reconnue mot a mot.'],
            'will-change transform' => [['willChange' => 'transform'], true,
                'Annoncer un `transform` suffit : le bloc conteneur est cree avant meme la transformation.'],
            'will-change filter et opacite' => [['willChange' => 'opacity, filter'], true,
                'Il suffit qu\'une des proprietes citees soit dans la liste.'],

            // Transformations individuelles.
            'rotate pose' => [['rotate' => '3deg'], true,
                '`rotate` est une transformation individuelle, elle compte aussi.'],
            'scale pose' => [['scale' => '1.05'], true,
                '`scale` deplace reellement l\'element.'],
            'translate pose' => [['translate' => '0px 4px'], true,
                '`translate` deplace reellement l\'element.'],

            // Les valeurs neutres ne doivent RIEN declencher : un faux positif
            // deplacerait le menu sur toutes les pages, y compris celles ou les
            // regles CSS ecrites en descendance du parent sont necessaires.
            'tout a none' => [[
                'transform' => 'none', 'filter' => 'none', 'backdropFilter' => 'none',
                'perspective' => 'none', 'contain' => 'none', 'willChange' => 'auto',
                'rotate' => 'none', 'scale' => 'none', 'translate' => 'none',
            ], false, 'C\'est le style calcule d\'un element ordinaire : il ne doit rien declencher.'],
            'style vide' => [[], false,
                'Un objet sans propriete ne doit pas etre pris pour un bloc conteneur.'],
            'valeur non textuelle' => [['transform' => 42], false,
                'Une valeur CSS calculee est toujours une chaine ; le reste doit etre ignore, '
                . 'sinon une methode heritee passerait pour une propriete posee.'],
            'contain size seul' => [['contain' => 'size'], false,
                '`contain: size` ne cree pas de bloc conteneur — seuls layout, paint, strict et content le font.'],
            'will-change sans propriete concernee' => [['willChange' => 'opacity, background-color'], false,
                'Annoncer une opacite ne cree aucun bloc conteneur.'],
            'rotate neutre' => [['rotate' => '0deg'], false,
                'Certains navigateurs calculent la valeur neutre en `0deg` plutot qu\'en `none`.'],
            'scale neutre' => [['scale' => '1 1'], false,
                'Idem : la valeur neutre peut s\'ecrire `1`, `1 1` ou `1 1 1`.'],
            'translate neutre' => [['translate' => '0px 0px'], false,
                'Idem : `0px 0px` ne deplace rien.'],
        ];
    }

    public function test_l_ancetre_fautif_est_retrouve_en_remontant_l_arbre(): void
    {
        // .au-select > div.form-row > div.card-moderne(:hover) > main
        // Le composant n'a aucun moyen de savoir a l'avance a quelle profondeur
        // se trouve la regle fautive : il doit remonter jusqu'a la trouver.
        $trouve = $this->ancetreBloquant(
            ['au-select', 'form-row', 'card-moderne', 'main'],
            ['card-moderne' => ['transform' => 'matrix(1, 0, 0, 1, 0, -1)']]
        );

        $this->assertSame(
            'card-moderne',
            $trouve,
            'La carte survolee est l\'ancetre fautif : c\'est elle qui doit etre retrouvee.'
        );
    }

    public function test_un_arbre_sans_transformation_ne_declenche_aucun_deplacement(): void
    {
        $this->assertSame(
            '',
            $this->ancetreBloquant(['au-select', 'form-row', 'card-moderne', 'main'], []),
            'Sans propriete fautive, le menu doit rester ou il est : le deplacer lui ferait '
            . 'perdre les regles CSS ecrites en descendance de son parent.'
        );
    }

    public function test_l_element_lui_meme_est_examine(): void
    {
        // Le menu est un descendant de la racine du composant : si c'est ELLE
        // qui porte la transformation, le defaut est identique.
        $this->assertSame(
            'au-select',
            $this->ancetreBloquant(['au-select', 'main'], ['au-select' => ['filter' => 'blur(1px)']]),
            'La racine du composant englobe deja le menu : elle doit etre examinee comme les autres.'
        );
    }

    public function test_une_chaine_circulaire_ne_fige_pas_la_page(): void
    {
        $programme = <<<'JS'
        var boucle = { nom: 'boucle', style: {} };
        boucle.parentElement = boucle;
        var resultat = window.auSelectAncetreBloquant(boucle, function (n) { return n.style; });
        process.stdout.write(resultat === null ? 'null' : 'trouve');
        JS;

        $this->assertSame(
            'null',
            $this->evalueAvecLeComposant($programme),
            'Une chaine de parents circulaire doit s\'arreter sur le garde-fou, pas boucler indefiniment.'
        );
    }

    /**
     * @param  array<string, mixed>  $style
     */
    private function creeUnBlocConteneur(array $style): bool
    {
        // (object) : un tableau PHP vide s'encoderait en `[]`, et un tableau
        // JavaScript n'est pas un objet de style.
        $programme = 'process.stdout.write(window.auSelectStyleCreeBlocConteneur('
            . json_encode((object) $style, JSON_UNESCAPED_UNICODE) . ') ? "1" : "0");';

        $sortie = $this->evalueAvecLeComposant($programme);

        $this->assertContains(
            $sortie,
            ['0', '1'],
            'Le detecteur du composant n\'a pas pu etre evalue. Sortie Node : ' . $sortie
        );

        return $sortie === '1';
    }

    /**
     * Construit une chaine d'ancetres du plus proche au plus lointain et rend
     * le nom de celui qui bloque, ou '' si aucun.
     *
     * @param  list<string>  $chaine
     * @param  array<string, array<string, string>>  $stylesParNom
     */
    private function ancetreBloquant(array $chaine, array $stylesParNom): string
    {
        $programme = 'var chaine = ' . json_encode($chaine, JSON_UNESCAPED_UNICODE) . ';' . "\n"
            . 'var styles = ' . json_encode((object) $stylesParNom, JSON_UNESCAPED_UNICODE) . ';' . "\n"
            . <<<'JS'
            var precedent = null;
            for (var i = chaine.length - 1; i >= 0; i--) {
                precedent = { nom: chaine[i], parentElement: precedent };
            }
            var resultat = window.auSelectAncetreBloquant(precedent, function (noeud) {
                return styles[noeud.nom] || {};
            });
            process.stdout.write(resultat ? resultat.nom : '');
            JS;

        return $this->evalueAvecLeComposant($programme);
    }
}
