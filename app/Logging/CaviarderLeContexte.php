<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * Ce qui part dans un journal ne se reprend pas.
 *
 * Un fichier de log se lit sans authentification par qui a un acces cPanel,
 * se copie dans une sauvegarde, se joint a un ticket de support. Deux categories
 * n'ont donc rien a y faire :
 *
 *  1. La matiere d'authentification — mot de passe, jeton, en-tete `Cookie`
 *     (qui transporte le cookie de session ET le `remember_web_*`, valable
 *     cinq ans). Une seule ligne suffit a rejouer une session.
 *  2. Les vidages de requete entiers — `$request->all()`, `$request->headers->all()`.
 *     Ecrits pour deboguer un formulaire, ils emportent avec eux l'etat civil
 *     d'un mineur : date et lieu de naissance, filiation, adresse, telephone.
 *
 * Les appels precis restent intacts : un controleur qui journalise
 * `['email' => $destinataire]` a choisi de le faire et sait pourquoi. Ce filet
 * ne coupe que ce qui est indiscriminé — le dump — et ce qui est toujours secret.
 *
 * Il ne repare pas les journaux deja ecrits. Il empeche les suivants.
 */
class CaviarderLeContexte
{
    /** Profondeur au-dela de laquelle on arrete de descendre (structures cycliques, gros payloads). */
    private const PROFONDEUR_MAX = 6;

    private const REMPLACEMENT = '[caviardé]';

    /**
     * Fragments de nom de cle qui designent toujours un secret, quelle que soit
     * la casse et le contexte. Compares en minuscules, en sous-chaine.
     */
    private const SECRETS = [
        'password', 'passwd', 'mot_de_passe', 'motdepasse',
        'token', 'secret', 'api_key', 'apikey', 'private_key',
        'authorization', 'auth_basic',
        'cookie',
        'cvv', 'card_number', 'numero_carte',
        'otp', 'code_verification',
        'signature', 'hmac',
        'credentials',
    ];

    /**
     * Cles dont la valeur est un vidage brut de requete. On n'en garde que les
     * noms de champs : assez pour savoir ce qui a ete soumis, rien pour
     * reconstituer un dossier.
     */
    private const VIDAGES = [
        'request', 'request_data', 'request_all', 'request_headers',
        'headers', 'server', 'cookies', 'payload', 'input', 'post', 'query_params',
    ];

    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->pushProcessor(fn ($record) => $this->traiter($record));
        }
    }

    /**
     * @param  LogRecord|array<string, mixed>  $record
     * @return LogRecord|array<string, mixed>
     */
    private function traiter($record)
    {
        // Monolog 3 expose un objet immuable, Monolog 2 un tableau. Le projet
        // tourne sur Laravel 9 (Monolog 2), mais la mise a jour ne doit pas
        // rendre ce filet silencieusement inoperant.
        if ($record instanceof LogRecord) {
            return $record->with(
                context: $this->parcourir($record->context, 0),
                extra: $this->parcourir($record->extra, 0)
            );
        }

        if (is_array($record)) {
            if (isset($record['context']) && is_array($record['context'])) {
                $record['context'] = $this->parcourir($record['context'], 0);
            }
            if (isset($record['extra']) && is_array($record['extra'])) {
                $record['extra'] = $this->parcourir($record['extra'], 0);
            }
        }

        return $record;
    }

    /**
     * @param  array<mixed, mixed>  $valeurs
     * @return array<mixed, mixed>
     */
    private function parcourir(array $valeurs, int $profondeur): array
    {
        if ($profondeur >= self::PROFONDEUR_MAX) {
            return ['...' => '[profondeur maximale atteinte]'];
        }

        $sortie = [];

        foreach ($valeurs as $cle => $valeur) {
            $nom = is_string($cle) ? mb_strtolower($cle) : '';

            if ($nom !== '' && $this->estUnSecret($nom)) {
                $sortie[$cle] = self::REMPLACEMENT;
                continue;
            }

            if ($nom !== '' && is_array($valeur) && in_array($nom, self::VIDAGES, true)) {
                $sortie[$cle] = $this->nomsDeChamps($valeur);
                continue;
            }

            $sortie[$cle] = is_array($valeur)
                ? $this->parcourir($valeur, $profondeur + 1)
                : $valeur;
        }

        return $sortie;
    }

    private function estUnSecret(string $nom): bool
    {
        foreach (self::SECRETS as $fragment) {
            if (str_contains($nom, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * D'un vidage, on ne conserve que la liste des cles. Un tableau sans cle
     * lisible (liste de fichiers, par exemple) devient un simple compte.
     *
     * @param  array<mixed, mixed>  $vidage
     * @return array<string, mixed>
     */
    private function nomsDeChamps(array $vidage): array
    {
        $noms = array_values(array_filter(
            array_keys($vidage),
            fn ($cle) => is_string($cle)
        ));

        return [
            'champs' => $noms,
            'total' => count($vidage),
            'note' => 'contenu caviardé — vidage de requête',
        ];
    }
}
