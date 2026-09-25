<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;

/**
 * Texte mis en forme par l'éditeur riche (descriptions de cycle, de spécialité,
 * de partenariat).
 *
 * Le HTML vient du navigateur : on ne le croit pas. nettoyer() ne garde qu'une
 * liste fermée de balises de mise en forme et, sur les liens, un href dont le
 * protocole est sûr. Tout le reste disparaît : attributs (style, on*, class),
 * scripts, iframes, images.
 *
 * Les descriptions saisies avant l'éditeur sont du texte brut. afficher() les
 * rend comme avant (retours à la ligne conservés, rien d'interprété), et
 * pourEditeur() les convertit en paragraphes pour que l'éditeur ne les écrase
 * pas sur une seule ligne.
 */
final class TexteRiche
{
    /** Balises gardées telles quelles (sans aucun attribut, sauf href sur <a>). */
    private const BALISES = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ul', 'ol', 'li', 'a', 'h3', 'h4', 'h5', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Balises retirées avec tout leur contenu (le texte n'y a pas de sens). */
    private const SUPPRIMEES = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'template', 'noscript', 'textarea', 'select', 'button', 'form', 'input', 'head', 'title', 'meta', 'link',
    ];

    public static function contientDuHtml(?string $texte): bool
    {
        // Une vraie balise, pas un « < » de texte (« note < 10 », « <3 ») :
        // strip_tags() mange ces derniers et ferait prendre du texte brut pour du HTML.
        return $texte !== null && preg_match('#<\s*/?\s*[a-z][a-z0-9]*(\s[^<>]*)?/?\s*>#i', $texte) === 1;
    }

    public static function nettoyer(?string $texte): ?string
    {
        if ($texte === null) {
            return null;
        }
        if (! self::contientDuHtml($texte)) {
            return $texte;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $precedent = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><div id="texte-riche-racine">' . $texte . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($precedent);

        $racine = $doc->getElementById('texte-riche-racine');
        if (! $racine) {
            return trim(strip_tags($texte));
        }

        self::nettoyerEnfants($racine);

        $html = '';
        foreach ($racine->childNodes as $enfant) {
            $html .= $doc->saveHTML($enfant);
        }

        $html = trim($html);

        // Un éditeur vidé laisse « <p><br></p> » : c'est une description vide.
        return trim(strip_tags($html)) === '' ? null : $html;
    }

    /** Pour une page : le HTML nettoyé, ou le texte brut échappé avec ses retours à la ligne. */
    public static function afficher(?string $texte): ?HtmlString
    {
        if ($texte === null || trim($texte) === '') {
            return null;
        }
        if (! self::contientDuHtml($texte)) {
            return new HtmlString(nl2br(e($texte)));
        }

        return new HtmlString((string) self::nettoyer($texte));
    }

    /** Pour pré-remplir l'éditeur : un ancien texte brut devient des paragraphes. */
    public static function pourEditeur(?string $texte): string
    {
        if ($texte === null || trim($texte) === '') {
            return '';
        }
        if (self::contientDuHtml($texte)) {
            return (string) self::nettoyer($texte);
        }

        $paragraphes = preg_split('/\R{2,}/', trim($texte));

        return implode('', array_map(
            fn (string $p) => '<p>' . nl2br(e($p), false) . '</p>',
            $paragraphes
        ));
    }

    private static function nettoyerEnfants(DOMNode $parent): void
    {
        // Copie : on modifie la liste pendant qu'on la parcourt.
        foreach (iterator_to_array($parent->childNodes) as $noeud) {
            if ($noeud->nodeType === XML_COMMENT_NODE || $noeud->nodeType === XML_PI_NODE) {
                $parent->removeChild($noeud);
                continue;
            }
            if (! $noeud instanceof DOMElement) {
                continue;
            }

            $balise = strtolower($noeud->nodeName);

            if (in_array($balise, self::SUPPRIMEES, true)) {
                $parent->removeChild($noeud);
                continue;
            }

            self::nettoyerEnfants($noeud);

            if (! in_array($balise, self::BALISES, true)) {
                // Balise inconnue (span, div, font, img…) : on garde son texte, pas elle.
                while ($noeud->firstChild) {
                    $parent->insertBefore($noeud->firstChild, $noeud);
                }
                $parent->removeChild($noeud);
                continue;
            }

            self::nettoyerAttributs($noeud, $balise);
        }
    }

    private static function nettoyerAttributs(DOMElement $element, string $balise): void
    {
        $href = $balise === 'a' ? trim((string) $element->getAttribute('href')) : '';

        foreach (iterator_to_array($element->attributes) as $attribut) {
            $element->removeAttribute($attribut->nodeName);
        }

        if ($balise !== 'a') {
            return;
        }

        // Protocoles sûrs seulement : pas de javascript:, data:, vbscript:…
        if ($href !== '' && preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', $href)) {
            $element->setAttribute('href', $href);
            if (preg_match('#^https?://#i', $href)) {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }
    }
}
