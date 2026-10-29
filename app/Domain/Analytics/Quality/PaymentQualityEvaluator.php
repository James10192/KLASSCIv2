<?php

namespace App\Domain\Analytics\Quality;

use Carbon\CarbonImmutable;

/**
 * Dit si les paiements saisis permettent une prevision, et pourquoi.
 *
 * Fonction pure : elle recoit des agregats deja calcules, sans base de
 * donnees. Trois constats possibles :
 *
 *  - donnees perimees   : aucun paiement saisi depuis N jours. Toute prevision
 *                         prolongerait un passe qui ne se renouvelle plus ;
 *  - saisie de rattrapage : une part importante des paiements a ete saisie en
 *                         masse, longtemps apres la date de paiement. Les mois
 *                         concernes mesurent la saisie, pas l'encaissement ;
 *  - echantillon trop petit : trop peu de paiements pour en tirer une tendance.
 *
 * Un groupe de saisie est « de rattrapage » quand un meme compte, un meme
 * jour, saisit au moins `catchup_min_per_day` paiements dont la majorite date
 * de plus de `catchup_lag_days` jours. Les deux criteres ensemble : un gros
 * jour de caisse a la rentree n'est pas un rattrapage, un paiement ancien
 * saisi isolement non plus.
 */
class PaymentQualityEvaluator
{
    public const FIABLE = 'fiable';
    public const A_VERIFIER = 'a_verifier';
    public const INSUFFISANT = 'insuffisant';

    /** Valeurs recommandees, reglables par ecole (settings analytics.fiabilite.*). */
    public const DEFAULTS = [
        'stale_days' => 30,
        'min_sample' => 30,
        'lookback_months' => 12,
        'catchup_min_per_day' => 40,
        'catchup_lag_days' => 7,
        'catchup_alert_pct' => 30.0,
    ];

    /**
     * @param array<int, array{jour: string, user_id: ?int, nombre: int, anciens: int}> $groups
     *        Paiements saisis dans la fenetre, groupes par jour de saisie et compte.
     *        `anciens` = paiements dont la date precede la saisie de plus de catchup_lag_days.
     * @param array{stale_days: int, min_sample: int, catchup_min_per_day: int, catchup_lag_days: int, catchup_alert_pct: float} $seuils
     */
    public function evaluate(array $groups, ?CarbonImmutable $lastEntryAt, CarbonImmutable $now, array $seuils): array
    {
        $total = array_sum(array_column($groups, 'nombre'));
        $catchUpGroups = array_values(array_filter($groups, fn (array $g) => $this->isCatchUp($g, $seuils)));
        $catchUpCount = array_sum(array_column($catchUpGroups, 'nombre'));
        $catchUpPct = $total > 0 ? round(100 * $catchUpCount / $total, 1) : 0.0;
        $daysSinceLast = $lastEntryAt ? (int) $lastEntryAt->startOfDay()->diffInDays($now->startOfDay()) : null;

        $constats = [];
        if ($lastEntryAt === null) {
            $constats[] = $this->constat('aucune_saisie', 'Aucun paiement saisi', "Aucun paiement n'a encore été enregistré : il n'y a rien à prévoir.");
        } elseif ($daysSinceLast > $seuils['stale_days']) {
            $constats[] = $this->constat(
                'donnees_perimees',
                'Plus aucun paiement saisi depuis ' . $daysSinceLast . ' jours',
                'Le dernier paiement a été saisi le ' . $lastEntryAt->format('d/m/Y') . '. Les encaissements récents se font peut-être hors de KLASSCI : les prévisions prolongeraient un passé qui ne se renouvelle plus.'
            );
        }
        if ($catchUpPct >= $seuils['catchup_alert_pct']) {
            $constats[] = $this->constat(
                'saisie_rattrapage',
                $catchUpPct . ' % des paiements saisis en rattrapage',
                sprintf(
                    '%d paiements ont été saisis en série, sur %d journée%s, plus de %d jours après leur date. Sur ces mois, les chiffres reflètent la saisie plus que l\'encaissement réel.',
                    $catchUpCount,
                    count(array_unique(array_column($catchUpGroups, 'jour'))),
                    count(array_unique(array_column($catchUpGroups, 'jour'))) > 1 ? 's' : '',
                    $seuils['catchup_lag_days']
                )
            );
        }
        if ($lastEntryAt !== null && $total < $seuils['min_sample']) {
            $constats[] = $this->constat(
                'echantillon_insuffisant',
                'Trop peu de paiements pour une tendance',
                sprintf('%d paiements saisis sur la période analysée, il en faut au moins %d.', $total, $seuils['min_sample'])
            );
        }

        return [
            'niveau' => $this->level($constats),
            'constats' => $constats,
            'mesures' => [
                'paiements_analyses' => $total,
                'paiements_rattrapage' => $catchUpCount,
                'part_rattrapage_pct' => $catchUpPct,
                'derniere_saisie' => $lastEntryAt?->toDateString(),
                'jours_depuis_derniere_saisie' => $daysSinceLast,
            ],
        ];
    }

    private function isCatchUp(array $group, array $seuils): bool
    {
        return $group['nombre'] >= $seuils['catchup_min_per_day']
            && $group['anciens'] * 2 > $group['nombre'];
    }

    private function level(array $constats): string
    {
        $codes = array_column($constats, 'code');
        if (array_intersect($codes, ['aucune_saisie', 'echantillon_insuffisant'])) {
            return self::INSUFFISANT;
        }

        return $codes === [] ? self::FIABLE : self::A_VERIFIER;
    }

    private function constat(string $code, string $titre, string $detail): array
    {
        return ['code' => $code, 'titre' => $titre, 'detail' => $detail];
    }
}
