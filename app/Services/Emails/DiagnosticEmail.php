<?php

namespace App\Services\Emails;

use App\Enums\EtatEmail;

/**
 * L'analyse hors ligne, completee par le DNS.
 *
 * Une faute probable dont le domaine ne recoit rien devient une faute de
 * frappe (on la refuse avec sa suggestion) ; une adresse valide dont le
 * domaine ne recoit rien devient « sans MX ». Si le DNS ne sait pas, l'analyse
 * hors ligne fait foi.
 *
 * `classerDomaine()` sert les inventaires : il classe un DOMAINE, memorise par
 * processus, car le meme domaine revient dans plusieurs tables.
 */
class DiagnosticEmail
{
    /** @var array<string, AnalyseEmail> */
    private array $memo = [];

    public function __construct(
        private readonly AnalyseurEmail $analyseur,
        private readonly VerificateurMx $mx,
    ) {}

    public function diagnostiquer(?string $email, bool $avecMx = true): AnalyseEmail
    {
        $analyse = $this->analyseur->analyser($email);

        if (! $avecMx || ! in_array($analyse->etat, [EtatEmail::Valide, EtatEmail::FauteProbable], true)) {
            return $analyse;
        }

        if ($this->mx->recoitDuCourrier((string) $analyse->domaine) !== false) {
            return $analyse;
        }

        return $analyse->etat === EtatEmail::FauteProbable
            ? new AnalyseEmail(EtatEmail::FauteDeFrappe, $analyse->domaine, $analyse->suggestion)
            : new AnalyseEmail(EtatEmail::SansMx, $analyse->domaine);
    }

    public function classerDomaine(string $domaine, bool $avecMx = true): AnalyseEmail
    {
        return $this->memo[($avecMx ? 'mx:' : '').$domaine] ??= $domaine === ''
            ? new AnalyseEmail(EtatEmail::Invalide)
            : $this->diagnostiquer('x@'.$domaine, $avecMx);
    }
}
