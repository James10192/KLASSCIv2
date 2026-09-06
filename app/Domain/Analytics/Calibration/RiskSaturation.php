<?php

namespace App\Domain\Analytics\Calibration;

use App\Helpers\SettingsHelper;

/**
 * Dit si une cohorte « sature » le classement de risque, c'est-a-dire si le
 * score ne discrimine plus personne.
 *
 * Deux facons de saturer, et il suffit d'une seule :
 *  - la tranche « haut » seule depasse un seuil ;
 *  - les tranches « haut » + « moyen » reunies depassent un seuil total.
 *
 * Le second cas est celui que l'ancien test manquait : 60 % de haut et 40 % de
 * moyen, personne en bas, donc 100 % « a risque » — et pourtant declare non
 * sature parce qu'on ne regardait que le haut.
 *
 * Les seuils se lisent dans les reglages de l'instance
 * (analytics.default_risk.saturation_haut_pct / saturation_total_pct) ; les
 * constantes ci-dessous ne sont que les valeurs de depart.
 */
final class RiskSaturation
{
    /** Part de la cohorte en « haut » au-dela de laquelle le haut seul sature. */
    public const DEFAULT_SEUIL_HAUT_PCT = 70.0;

    /** Part de la cohorte en « haut » + « moyen » au-dela de laquelle l'ensemble sature. */
    public const DEFAULT_SEUIL_TOTAL_PCT = 90.0;

    public const REGLAGE_SEUIL_HAUT = 'saturation_haut_pct';
    public const REGLAGE_SEUIL_TOTAL = 'saturation_total_pct';

    public const DECLENCHEUR_HAUT = 'haut';
    public const DECLENCHEUR_TOTAL = 'total';

    public function __construct(
        public readonly float $seuilHautPct = self::DEFAULT_SEUIL_HAUT_PCT,
        public readonly float $seuilTotalPct = self::DEFAULT_SEUIL_TOTAL_PCT,
    ) {}

    /**
     * Seuils de l'instance, avec un jeu de surcharges pour les tests
     * (memes cles que les reglages, sans le prefixe).
     *
     * @param  array<string, float|int>  $surcharges
     */
    public static function depuisReglages(array $surcharges = []): self
    {
        return new self(
            seuilHautPct: self::lire(self::REGLAGE_SEUIL_HAUT, self::DEFAULT_SEUIL_HAUT_PCT, $surcharges),
            seuilTotalPct: self::lire(self::REGLAGE_SEUIL_TOTAL, self::DEFAULT_SEUIL_TOTAL_PCT, $surcharges),
        );
    }

    /**
     * @param  array{haut?:int, moyen?:int, bas?:int}  $buckets
     * @return array{
     *   is_saturated: bool,
     *   declencheur: ?string,
     *   haut_pct: float,
     *   moyen_pct: float,
     *   bas_pct: float,
     *   total_pct: float,
     *   seuils: array{haut_pct: float, total_pct: float}
     * }
     */
    public function evaluer(array $buckets, int $total): array
    {
        $pct = fn (int $n): float => $total > 0 ? round($n / $total * 100, 1) : 0.0;

        $haut = (int) ($buckets['haut'] ?? 0);
        $moyen = (int) ($buckets['moyen'] ?? 0);
        $bas = (int) ($buckets['bas'] ?? 0);

        $hautPct = $pct($haut);
        $totalPct = $pct($haut + $moyen);

        $declencheur = null;
        if ($total > 0) {
            if ($hautPct >= $this->seuilHautPct) {
                $declencheur = self::DECLENCHEUR_HAUT;
            } elseif ($totalPct >= $this->seuilTotalPct) {
                $declencheur = self::DECLENCHEUR_TOTAL;
            }
        }

        return [
            'is_saturated' => $declencheur !== null,
            'declencheur' => $declencheur,
            'haut_pct' => $hautPct,
            'moyen_pct' => $pct($moyen),
            'bas_pct' => $pct($bas),
            'total_pct' => $totalPct,
            'seuils' => [
                'haut_pct' => $this->seuilHautPct,
                'total_pct' => $this->seuilTotalPct,
            ],
        ];
    }

    /**
     * @param  array<string, float|int>  $surcharges
     */
    private static function lire(string $cle, float $defaut, array $surcharges): float
    {
        if (array_key_exists($cle, $surcharges)) {
            return (float) $surcharges[$cle];
        }

        return (float) SettingsHelper::get("analytics.default_risk.{$cle}", $defaut);
    }
}
