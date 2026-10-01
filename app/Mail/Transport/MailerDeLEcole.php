<?php

namespace App\Mail\Transport;

use App\Models\Setting;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Http\Request;

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

    /**
     * Cocher la case alors que MailPulse est coupé ou sans clé : AUCUN courriel
     * ne partirait plus, liens de confirmation et mots de passe oubliés compris.
     * On le refuse au moment où l'école le demande, plutôt que de le découvrir
     * au premier courriel perdu.
     *
     * On ne juge que ce qui change : un état déjà en base (clé retirée depuis,
     * par exemple) ne bloque pas l'enregistrement d'un logo. Les deux
     * enregistrements de l'écran (page entière, bouton MailPulse) passent ici.
     */
    public function refusDeBascule(Request $requete): ?string
    {
        $enBase = fn (string $cle) => filter_var(Setting::get($cle, '0'), FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        $soumis = fn (string $cle) => $requete->has('setting_'.$cle)
            ? (filter_var($requete->input('setting_'.$cle), FILTER_VALIDATE_BOOLEAN) ? '1' : '0')
            : null;

        $activeAvant = $enBase('mailpulse_enabled');
        $active = $soumis('mailpulse_enabled') ?? $activeAvant;
        $coche = ($soumis(self::REGLAGE) ?? $enBase(self::REGLAGE)) === '1';
        if (! $coche || ($enBase(self::REGLAGE) === '1' && $active === $activeAvant)) {
            return null;
        }

        if ($active !== '1') {
            return "Pour envoyer les e-mails de l'école par MailPulse, activez d'abord MailPulse pour cette instance : sans lui, plus aucun e-mail ne partirait.";
        }

        $cleSoumise = trim((string) $requete->input('setting_mailpulse_api_key', ''));
        if ($cleSoumise === '' && ! $this->client->apiKeyDiagnostics()['configured']) {
            return "Pour envoyer les e-mails de l'école par MailPulse, enregistrez d'abord la clé API MailPulse : sans elle, plus aucun e-mail ne partirait.";
        }

        return null;
    }

    private function oui(string $reglage, string $cleConfig, string $defaut): bool
    {
        return filter_var($this->client->getSetting($reglage, $cleConfig, $defaut), FILTER_VALIDATE_BOOLEAN);
    }
}
