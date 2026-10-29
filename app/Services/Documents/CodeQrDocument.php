<?php

namespace App\Services\Documents;

use App\Helpers\SettingsHelper;
use App\Support\CodeQr;

/**
 * Le code QR imprime sur un document, et ce qu'il ouvre.
 *
 * Ce que ce code NE FAIT PAS : attester l'authenticite du papier. KLASSCI a deja
 * ce mecanisme-la, `/verifier-document-officiel`, et il exige une reference ET un
 * code secret enregistres par document. Une fiche d'inscription n'en est pas un :
 * c'est un formulaire qu'on imprime pour le faire signer, pas un acte delivre.
 * L'enregistrer comme tel a chaque apercu remplirait la table des documents
 * officiels de brouillons.
 *
 * Ce que ce code FAIT : ramener le papier au dossier. La fiche part au guichet,
 * revient signee, et quelqu'un doit alors retrouver l'eleve. Le code QR ouvre sa
 * fiche directement. Il n'expose rien : l'adresse mene a une page de
 * l'application, qui demande de s'identifier. Qui scanne sans compte voit un
 * ecran de connexion, pas un dossier.
 */
class CodeQrDocument
{
    /**
     * Imprime-t-on un code QR sur les documents ?
     *
     * Une ecole peut ne pas en vouloir — papier deja charge, scanners absents,
     * usage jugee inutile. Le defaut est « oui » : le code ne coute rien a qui
     * l'ignore, et il fait gagner du temps a qui s'en sert.
     */
    public const REGLAGE_ACTIF = 'documents_code_qr_actif';

    /**
     * Cote du code, en points.
     *
     * Assez grand pour etre lu par un telephone sur une impression laser
     * ordinaire, assez petit pour tenir dans un coin de fiche a cote de la
     * signature.
     */
    private const COTE = 110;

    /**
     * Le code QR d'une adresse, en donnee embarquee, ou null.
     *
     * `isRemoteEnabled` est a false dans nos rendus PDF, et c'est voulu : le
     * moteur n'ira chercher aucune URL. L'image doit donc voyager DANS le
     * document.
     *
     * Rend null plutot que d'echouer : un document sans code QR reste imprimable
     * et signable, un document qui ne sort pas ne l'est pas.
     */
    public function __construct(private CodeQr $codes)
    {
    }

    public function pour(?string $adresse): ?string
    {
        if (! $this->actif()) {
            return null;
        }

        // La fabrique rend null si la bibliotheque manque : le document part
        // alors sans son code, ce qui reste imprimable et signable.
        return $this->codes->svg($adresse, self::COTE);
    }

    public function actif(): bool
    {
        return $this->interpreterBooleen(SettingsHelper::get(self::REGLAGE_ACTIF, true), true);
    }

    /**
     * Les reglages sont stockes en texte : « 0 », « false » et « non » valent
     * non. Toute autre valeur renseignee vaut oui, l'absence vaut le defaut.
     */
    private function interpreterBooleen(mixed $valeur, bool $defaut): bool
    {
        if ($valeur === null || $valeur === '') {
            return $defaut;
        }

        if (is_bool($valeur)) {
            return $valeur;
        }

        if (is_int($valeur)) {
            return $valeur !== 0;
        }

        return ! in_array(mb_strtolower(trim((string) $valeur)), ['0', 'false', 'non', 'no', 'off'], true);
    }
}
