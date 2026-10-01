<?php

namespace App\Mail\Transport;

use App\Services\MailPulse\MailPulseClient;

/**
 * Le mailer par défaut de l'instance : celui du serveur (`MAIL_MAILER`), sauf
 * si l'école a demandé, dans ses réglages MailPulse, que tous ses courriels
 * partent par MailPulse.
 *
 * Deux sources, une seule règle :
 * - `MAIL_MAILER=mailpulse` dans le `.env` impose MailPulse, quel que soit le
 *   réglage. C'est la dérogation du serveur, et l'écran le dit ;
 * - sinon, MailPulse si le réglage `mailpulse_courriels_enabled` ET
 *   `mailpulse_enabled` sont tous deux à oui. MailPulse coupé, plus aucun
 *   courriel ne partirait : on garde alors le mailer du serveur.
 *
 * Lu à CHAQUE envoi, pas une fois au démarrage, pour trois raisons :
 * - rien n'interroge la base au démarrage (`migrate` sur une installation
 *   neuve, `package:discover` sans base joignable) : la lecture n'a lieu que
 *   quand un courriel part ou qu'un gabarit demande `actif()` ;
 * - un worker de file qui tourne depuis des heures suit le réglage dès son
 *   enregistrement, sans `queue:restart` : l'enregistrement vide le cache des
 *   réglages, que tous les processus partagent ;
 * - le coût est deux lectures du cache des réglages par courriel.
 *
 * Base injoignable : `MailPulseClient::getSetting()` rattrape et retombe sur la
 * configuration (`services.mailpulse.*`), où `courriels_enabled` n'existe pas :
 * le mailer du serveur reste en place, et `Setting::get()` journalise le repli.
 */
final class MailerDeLEcole
{
    public const REGLAGE = 'mailpulse_courriels_enabled';

    public const MAILER = 'mailpulse';

    /**
     * Réglages qui décident par où partent les courriels de l'école, donc qui
     * lit un lien de réinitialisation de mot de passe. Ils ne s'écrivent que
     * depuis l'écran des paramètres (`system.manage`), jamais par la CLI : la
     * même raison qui tient MAIL_* hors de `CleEnvAutorisee`.
     */
    public const REGLAGES_RESERVES_A_L_ECRAN = [self::REGLAGE, 'mailpulse_base_url'];

    public function __construct(private readonly MailPulseClient $client)
    {
    }

    /** Le nom du mailer qui doit servir quand l'appelant n'en désigne aucun. */
    public function parDefaut(?string $duServeur = null): string
    {
        $duServeur ??= (string) (config('mail.driver') ?? config('mail.default'));

        if ($duServeur === self::MAILER || ! $this->demandeParLEcole()) {
            return $duServeur;
        }

        return self::MAILER;
    }

    /** Les deux réglages sont à oui. Le réglage le plus rare est lu en premier. */
    public function demandeParLEcole(): bool
    {
        return $this->oui(self::REGLAGE, 'courriels_enabled', '0')
            && $this->oui('mailpulse_enabled', 'enabled', '1');
    }

    /** `MAIL_MAILER=mailpulse` : le serveur impose MailPulse, le réglage n'y peut rien. */
    public function imposeParLeServeur(): bool
    {
        return config('mail.default') === self::MAILER;
    }

    private function oui(string $reglage, string $cleConfig, string $defaut): bool
    {
        return filter_var($this->client->getSetting($reglage, $cleConfig, $defaut), FILTER_VALIDATE_BOOLEAN);
    }
}
