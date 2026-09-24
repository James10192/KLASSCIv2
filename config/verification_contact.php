<?php

/*
 * Verification du contact des demandes deposees sur le portail public.
 *
 * Le lien du courriel porte le jeton dans le FRAGMENT (`#jeton=`), jamais dans
 * la requete : un fragment ne part pas au serveur, donc ni dans ses journaux
 * ni dans la mesure d'audience. L'ecole est dans la requete, parce que
 * klassci.com sert plusieurs etablissements.
 */

return [
    'url_portail_public' => rtrim((string) env('URL_PORTAIL_PUBLIC', 'https://www.klassci.com'), '/'),

    'code_expire_minutes' => 30,
    'lien_expire_heures' => 48,
    // Duree de vie d'un code WhatsApp : celle que MailPulse applique.
    'code_whatsapp_expire_minutes' => 10,
    'tentatives_max' => 5,
    // Plafond cumule par demande, renvois compris : trois codes pleinement essayes.
    'tentatives_max_total' => 15,

    // Renvoi : un par minute, cinq par heure, par demande.
    'renvoi_intervalle_secondes' => 60,
    'renvoi_max_par_heure' => 5,
];
