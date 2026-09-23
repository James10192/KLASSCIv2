<?php

namespace App\Support;

/**
 * La mise en page affiche les messages de retour (success, error, warning,
 * info) en haut de chaque écran. Environ 126 vues les affichent aussi dans
 * leur propre contenu, et l'utilisateur lisait chaque message deux fois.
 *
 * La mise en page ne répète donc pas un message que la page montre déjà —
 * mais seulement quand on est sûr que la page le MONTRE, pas qu'il figure
 * quelque part dans son code source. Se tromper dans ce sens-là fait
 * disparaître le message ; se tromper dans l'autre ne fait que le doubler,
 * comme avant. D'où trois règles, toutes du côté prudent :
 *
 * - le texte doit se trouver dans un élément d'alerte (classe contenant
 *   « alert ») qui n'est pas caché d'emblée ;
 * - le contenu des `<script>`, `<style>` et `<template>` ne compte pas ;
 * - sur un écran à double version bureau / mobile (`m-only-…`), on ne
 *   déduplique pas : la copie de la page peut n'exister que dans la version
 *   bureau, masquée sur téléphone.
 */
final class MessagesDeRetour
{
    /** Distance maximale entre l'ouverture de l'alerte et son texte. */
    private const PORTEE = 800;

    public static function dejaAffichePar(string $contenuDeLaPage, mixed $message): bool
    {
        if (! is_string($message) || $message === '' || str_contains($contenuDeLaPage, 'm-only-')) {
            return false;
        }

        $visible = preg_replace('#<(script|style|template)\b[^>]*>.*?</\1>#is', '', $contenuDeLaPage) ?? '';
        $texte = e($message);

        preg_match_all('#<[a-z][a-z0-9-]*\b[^>]*\bclass="[^"]*alert[^"]*"[^>]*>#i', $visible, $alertes, PREG_OFFSET_CAPTURE);

        foreach ($alertes[0] as [$ouverture, $position]) {
            if (preg_match('#\bd-none\b|display:\s*none|x-show|x-cloak|\bhidden\b#i', $ouverture)) {
                continue;
            }

            if (str_contains(substr($visible, $position + strlen($ouverture), self::PORTEE), $texte)) {
                return true;
            }
        }

        return false;
    }
}
