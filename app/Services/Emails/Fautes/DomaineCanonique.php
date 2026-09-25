<?php

namespace App\Services\Emails\Fautes;

use App\Enums\EtatEmail;
use App\Services\Emails\AnalyseurEmail;
use App\Services\Emails\DiagnosticEmail;

/**
 * Pour les PROPOSITIONS (simulation) : vers quel domaine un domaine fautif se
 * corrigerait, et avec quelle certitude.
 *
 * - `connue` : tables explicites des listes partagees (fautes connues,
 *   extensions fautives). Toujours proposee.
 * - `probable` : distance d'edition vers une messagerie de reference, dont le
 *   domaine ne recoit aucun courrier d'apres la verification MX habituelle.
 *   Proposee seulement avec `inclure_probables` ; l'ecriture exigera en plus
 *   un NXDOMAIN frais (ControleEcriture).
 *
 * Domaines compares apres `trim` et passage en minuscules.
 */
class DomaineCanonique
{
    public const CONNUE = 'connue';

    public const PROBABLE = 'probable';

    public function __construct(
        private readonly AnalyseurEmail $analyseur,
        private readonly DiagnosticEmail $classement,
    ) {}

    /** @return array{domaine: string, nature: string}|null */
    public function pour(string $domaine, bool $inclureProbables): ?array
    {
        $domaine = mb_strtolower(trim($domaine));
        if ($domaine === '') {
            return null;
        }
        $hors = $this->analyseur->analyser('x@'.$domaine);
        $suggere = $hors->domaineSuggere();
        if ($suggere === null) {
            return null;
        }
        if ($hors->etat === EtatEmail::FauteDeFrappe) {
            return ['domaine' => $suggere, 'nature' => self::CONNUE];
        }
        if ($hors->etat === EtatEmail::FauteProbable && $inclureProbables
            && $this->classement->classerDomaine($domaine, true)->etat === EtatEmail::FauteDeFrappe) {
            return ['domaine' => $suggere, 'nature' => self::PROBABLE];
        }

        return null;
    }

    public static function domaineDe(?string $email): string
    {
        $email = trim((string) $email);
        $arobase = strrpos($email, '@');

        return $arobase === false ? '' : mb_strtolower(substr($email, $arobase + 1));
    }

    /** La partie locale telle quelle, le domaine remplace. */
    public static function corriger(string $email, string $domaine): string
    {
        $email = trim($email);

        return substr($email, 0, (int) strrpos($email, '@')).'@'.$domaine;
    }
}
