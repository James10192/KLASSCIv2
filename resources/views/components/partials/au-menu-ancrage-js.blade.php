/**
 * Un element devient BLOC CONTENEUR de ses descendants `position: fixed` des
 * qu'il porte l'une de ces proprietes : transform, filter, backdrop-filter,
 * perspective, contain (layout|paint|strict|content), ou will-change citant
 * l'une d'elles. Les transformations individuelles rotate/scale/translate
 * comptent au meme titre.
 *
 * Ce composant pose son menu en `fixed` avec des coordonnees lues sur le
 * VIEWPORT. Sous un tel ancetre, le navigateur les interprete par rapport a
 * CET ancetre : le menu part de plusieurs centaines de pixels, souvent hors
 * ecran. C'est ce qu'a produit `.card-moderne:hover { transform: ... }` sur
 * /esbtp/paiements/create — le selecteur d'etudiant disparaissait des qu'on
 * survolait la carte qui le contient.
 *
 * Corriger regle par regle est sans fin : la feuille globale en compte des
 * dizaines et il s'en ajoute a chaque page. Cette fonction sert donc a rendre
 * le composant INSENSIBLE au probleme, en detectant la situation pour aller
 * poser le menu ailleurs.
 *
 * Elle recoit un objet de style deja lu (pas un element) pour rester
 * verifiable hors navigateur.
 */
if (typeof window.auSelectStyleCreeBlocConteneur !== 'function') {
    window.auSelectStyleCreeBlocConteneur = function (style) {
        if (! style) {
            return false;
        }

        // Une valeur CSS calculee est TOUJOURS une chaine. Tout le reste est
        // du bruit — a commencer par `style.filter`, qui vaut la methode
        // `filter` heritee si on tend un tableau au lieu d'un style.
        var lire = function (nom, variante) {
            var valeur = style[nom];
            if (typeof valeur !== 'string' && variante) {
                valeur = style[variante];
            }
            return typeof valeur === 'string' ? valeur.trim().toLowerCase() : '';
        };

        // `none` et `auto` sont les valeurs calculees d'une propriete absente.
        var posee = function (valeur) {
            return valeur !== '' && valeur !== 'none' && valeur !== 'auto';
        };

        if (posee(lire('transform'))) return true;
        if (posee(lire('filter'))) return true;
        if (posee(lire('backdropFilter', 'backdrop-filter'))) return true;
        if (posee(lire('perspective'))) return true;

        // rotate / scale / translate ecrivent leur valeur neutre differemment
        // selon le navigateur : `none`, mais aussi `0deg`, `1` ou `0px`. Seul
        // un deplacement reel cree un bloc conteneur.
        var rotation = lire('rotate');
        if (posee(rotation) && ! /^0(deg|rad|grad|turn)?$/.test(rotation)) return true;

        var echelle = lire('scale');
        if (posee(echelle) && ! /^1(\s+1){0,2}$/.test(echelle)) return true;

        var deplacement = lire('translate');
        if (posee(deplacement) && ! /^0(px)?(\s+0(px)?){0,2}$/.test(deplacement)) return true;

        if (/\b(layout|paint|strict|content)\b/.test(lire('contain'))) return true;

        if (/\b(transform|filter|backdrop-filter|perspective|rotate|scale|translate|contain)\b/
            .test(lire('willChange', 'will-change'))) return true;

        return false;
    };
}

/**
 * Remonte la chaine des ancetres — l'element lui-meme compris, car il englobe
 * deja le menu — et rend le premier qui rendrait le menu mal place, ou null.
 *
 * `lireStyle` est injectable pour que la remontee soit verifiable sans
 * navigateur ; en production elle lit le style calcule.
 */
if (typeof window.auSelectAncetreBloquant !== 'function') {
    window.auSelectAncetreBloquant = function (element, lireStyle) {
        if (! element) {
            return null;
        }

        var lire = typeof lireStyle === 'function'
            ? lireStyle
            : function (noeud) { return window.getComputedStyle(noeud); };

        var noeud = element;
        // Garde-fou : une chaine circulaire ne doit pas figer la page.
        var restant = 200;

        while (noeud && restant-- > 0) {
            if (window.auSelectStyleCreeBlocConteneur(lire(noeud))) {
                return noeud;
            }
            noeud = noeud.parentElement || null;
        }

        return null;
    };
}

/**
 * Deplacement du menu sous <body>, partage par les composants premium qui
 * posent leur menu en `position: fixed` (x-au-select, x-au-user-picker).
 *
 * Une seule copie de la mecanique : la revue de la PR #1200 a montre que
 * deux copies divergeaient deja (l'une deplacait le menu, l'autre non), et
 * que c'est exactement ce qui decalait le menu dans les modales LMD animees
 * par un `transform`.
 *
 * - `deplacerSiBloque(racine, menu)` : si un ancetre de la racine fausserait
 *   le placement, pose un marqueur a la place du menu, deplace le menu sous
 *   <body> et rend le marqueur. Sinon rend null et ne touche a rien.
 * - `rapatrier(menu, marqueur)` : remet le menu a sa place, ou le retire si
 *   cette place a disparu du document (modale remplacee en AJAX).
 * - `veiller(rappel)` : un `transform` de survol n'existe qu'une fois la
 *   souris sur la carte ; on rappelle a chaque survol jusqu'a ce que
 *   l'appelant arrete la veille avec la fonction rendue.
 */
if (typeof window.auMenuAncrage !== 'object' || window.auMenuAncrage === null) {
    window.auMenuAncrage = {
        deplacerSiBloque: function (racine, menu) {
            if (! menu || ! menu.parentNode || ! window.auSelectAncetreBloquant(racine)) {
                return null;
            }
            var marqueur = document.createComment('au-menu');
            menu.parentNode.insertBefore(marqueur, menu);
            document.body.appendChild(menu);
            return marqueur;
        },
        rapatrier: function (menu, marqueur) {
            if (marqueur && marqueur.parentNode) {
                marqueur.parentNode.insertBefore(menu, marqueur);
                marqueur.remove();
            } else if (menu && menu.parentNode) {
                menu.remove();
            }
        },
        veiller: function (rappel) {
            document.addEventListener('pointerover', rappel, { passive: true, capture: true });
            return function () {
                document.removeEventListener('pointerover', rappel, { capture: true });
            };
        },
    };
}
