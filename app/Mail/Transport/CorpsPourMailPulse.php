<?php

namespace App\Mail\Transport;

/**
 * Ce que l'API MailPulse sait transporter d'un courriel : un texte, et un HTML
 * rangé dans `metadata.email_html`. Pas de pièce intégrée (`cid:`), et un
 * objet `metadata` plafonné à 16 384 octets de JSON.
 *
 * Fonctions pures : rien ici n'envoie ni ne journalise.
 */
final class CorpsPourMailPulse
{
    /**
     * Retire les images qui pointent vers une pièce intégrée : MailPulse ne la
     * transporterait pas, et la messagerie afficherait un cadre cassé.
     *
     * @return array{0: string, 1: int} le HTML, et le nombre d'images retirées
     */
    public static function sansPiecesIntegrees(string $html): array
    {
        $retirees = 0;
        $html = preg_replace_callback(
            '/<img\b[^>]*\bsrc\s*=\s*(["\'])cid:[^"\']*\1[^>]*>/i',
            function () use (&$retirees) {
                $retirees++;

                return '';
            },
            $html
        ) ?? $html;

        return [$html, $retirees];
    }

    /**
     * Retire les commentaires HTML (sauf les conditionnels d'Outlook,
     * `<!--[if mso]>`, qui portent du rendu) et l'indentation des lignes.
     *
     * Les sauts de ligne restent, lignes vides comprises : un bloc en
     * `white-space: pre-line` les affiche — la réponse du support en est un. Les espaces de tête, eux,
     * n'y comptent pas, et ce sont eux qui pèsent dans un gabarit indenté.
     */
    public static function resserrer(string $html): string
    {
        $html = preg_replace('/<!--(?!\[if|<!\[endif)(?:(?!-->).)*-->/s', '', $html) ?? $html;

        return trim(preg_replace('/[ \t]*\R[ \t]*/u', "\n", $html) ?? $html);
    }

    /** Version texte d'un HTML : liens conservés en clair, structure en sauts de ligne. */
    public static function texteDepuisHtml(string $html): string
    {
        $html = preg_replace('#<(head|style|script|title)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace_callback(
            '#<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            function (array $m) {
                $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $libelle = trim(strip_tags($m[3]));

                if ($libelle === '' || $libelle === $url || ! preg_match('#^(https?:|mailto:)#i', $url)) {
                    return $libelle !== '' ? $libelle : $url;
                }

                return $libelle.' ('.$url.')';
            },
            $html
        ) ?? $html;
        $html = preg_replace('#<br\s*/?>#i', "\n", $html) ?? $html;
        $html = preg_replace('#</(p|div|tr|li|h[1-6]|table|blockquote)>#i', "\n", $html) ?? $html;
        $texte = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texte = preg_replace('/[ \t\x{00A0}]+/u', ' ', $texte) ?? $texte;
        $texte = preg_replace('/ *\n */', "\n", $texte) ?? $texte;

        return trim(preg_replace("/\n{3,}/", "\n\n", $texte) ?? $texte);
    }

    /** Taille de l'objet `metadata` telle que MailPulse la mesure (JSON en UTF-8). */
    public static function octetsJson(array $valeur): int
    {
        return strlen((string) json_encode($valeur, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
