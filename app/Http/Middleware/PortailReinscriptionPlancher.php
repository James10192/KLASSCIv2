<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plancher de temps de reponse du portail public.
 *
 * Les corps de reponse sont uniformes : « ce matricule n'existe pas » et « il
 * existe mais la date ne correspond pas » rendent le meme JSON. Sans plancher,
 * le chronometre les distinguerait quand meme — le premier cas sort apres une
 * seule requete manquee, le second apres une lecture d'index reussie.
 *
 * Cet intergiciel est DELIBEREMENT separe du garde et place APRES la
 * limitation de debit : il n'enveloppe que le traitement reel. Un appel sans
 * signature valide, hors saison ou trop frequent est econduit sans attendre,
 * pour ne pas offrir un amplificateur de deni de service — un quart de seconde
 * de processus PHP par requete bidon saturerait un hebergement mutualise bien
 * avant d'inquieter l'attaquant.
 */
class PortailReinscriptionPlancher
{
    /**
     * Couvre largement la duree reelle d'une consultation, afin que les cas
     * « trouve » et « non trouve » soient indistinguables au chronometre.
     */
    private const PLANCHER_MICROSECONDES = 250_000;

    public function handle(Request $request, Closure $next): Response
    {
        $debut = microtime(true);

        // `finally` couvre aussi les exceptions : une erreur qui rendrait la
        // main instantanement signalerait a elle seule qu'un couple
        // identifiant est valide.
        try {
            return $next($request);
        } finally {
            $ecoule = (int) ((microtime(true) - $debut) * 1_000_000);

            if ($ecoule < self::PLANCHER_MICROSECONDES) {
                usleep(self::PLANCHER_MICROSECONDES - $ecoule);
            }
        }
    }
}
