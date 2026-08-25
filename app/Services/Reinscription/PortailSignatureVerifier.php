<?php

namespace App\Services\Reinscription;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Verifie la signature HMAC-SHA256 posee par le site klassci.com sur chaque
 * appel a l'export de reinscription.
 *
 * Meme algorithme et meme discipline que SsoTokenVerifier, deja en production
 * pour le portail groupe : un secret partage par etablissement, jamais expose
 * au navigateur. Le site public detient le secret cote serveur (variables
 * Vercel), signe, et relaie. Le navigateur ne voit qu'une reponse.
 *
 * La signature ne remplace pas l'identification de l'etudiant : elle atteste
 * seulement que l'appel vient bien du portail. C'est le couple matricule +
 * date de naissance qui identifie, et c'est precisement parce que ce couple
 * est faible que la reponse ne revele presque rien.
 *
 * Trois choix a connaitre avant de toucher a cette classe :
 *
 * 1. La charge signee est le CORPS BRUT, pas un tableau re-encode. Signer un
 *    tableau obligerait le site vitrine a reproduire a l'octet pres l'encodage
 *    JSON de PHP, apres que Laravel a desserialise et que deux intergiciels
 *    globaux — TrimStrings et ConvertEmptyStringsToNull — ont modifie
 *    l'entree. Une espace terminale suffirait a produire un 401 inexplicable.
 * 2. La methode et le chemin entrent dans la charge : sans eux, une signature
 *    emise pour /lookup serait arithmetiquement valide sur /submit.
 * 3. L'horodatage est en MILLISECONDES. Une signature est consommee a l'usage
 *    (voir consommer()), et deux clics rapides produisent sinon deux requetes
 *    identiques a l'octet — le second serait refuse comme un rejeu. La
 *    milliseconde les distingue sans ajouter de champ au contrat, la ou un
 *    identifiant d'appel obligerait le site vitrine a en tirer un neuf a
 *    chaque envoi, avec un 401 indiagnosticable s'il l'oubliait.
 */
class PortailSignatureVerifier
{
    private const ALGO = 'sha256';

    /**
     * Fenetre de validite d'un appel, en millisecondes. Assez large pour
     * absorber une derive d'horloge entre Vercel et l'hebergement, assez
     * courte pour qu'une requete interceptee ne soit pas rejouable le
     * lendemain.
     */
    public const TOLERANCE_MILLISECONDES = 300_000;

    public function verifie(
        string $corpsBrut,
        string $methode,
        string $chemin,
        string $signature,
        int $horodatageMs,
    ): bool {
        // Un horodatage en secondes passerait la fenetre a l'echelle des
        // millisecondes et echouerait de facon opaque. On le refuse
        // explicitement pour que l'auteur du site vitrine voie son erreur.
        if (strlen((string) $horodatageMs) !== 13) {
            return false;
        }

        if (abs($this->maintenantMs() - $horodatageMs) > self::TOLERANCE_MILLISECONDES) {
            return false;
        }

        $attendue = $this->signature($corpsBrut, $methode, $chemin, $horodatageMs);

        if (! hash_equals($attendue, $signature)) {
            return false;
        }

        return $this->consommer($attendue);
    }

    public function signature(string $corpsBrut, string $methode, string $chemin, int $horodatageMs): string
    {
        $charge = implode('.', [$horodatageMs, strtoupper($methode), trim($chemin, '/'), $corpsBrut]);

        return hash_hmac(self::ALGO, $charge, $this->secret());
    }

    public function estConfigure(): bool
    {
        $secret = $this->secretBrut();

        return is_string($secret) && strlen($secret) >= 32;
    }

    public function maintenantMs(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Usage unique dans la fenetre de validite.
     *
     * Sans cela, une requete interceptee resterait rejouable cinq minutes
     * durant. La cle est la signature elle-meme, deja unique par couple
     * (corps, horodatage), et l'expiration suit exactement la fenetre.
     *
     * `add` est atomique : le magasin de cache de ce projet est `file`, dont
     * l'implementation prend un verrou exclusif sur le fichier.
     */
    private function consommer(string $signature): bool
    {
        $secondes = (int) ceil(self::TOLERANCE_MILLISECONDES / 1000);

        return Cache::add('reinscription-portail-sig:'.$signature, true, $secondes);
    }

    private function secret(): string
    {
        $secret = $this->secretBrut();

        if (! is_string($secret) || strlen($secret) < 32) {
            throw new RuntimeException(
                'REINSCRIPTION_PORTAL_SECRET doit etre defini et faire au moins 32 caracteres.'
            );
        }

        return $secret;
    }

    private function secretBrut(): mixed
    {
        return config('services.reinscription_portal.secret');
    }
}
