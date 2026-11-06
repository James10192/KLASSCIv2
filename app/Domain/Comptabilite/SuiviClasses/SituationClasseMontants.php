<?php

namespace App\Domain\Comptabilite\SuiviClasses;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;

/** Calcule les montants sur les lignes d'échéancier à partir des règlements nets. */
final class SituationClasseMontants
{
    /**
     * Les lignes d'échéancier font foi pour la dette ; leurs « paid_amount »
     * ne font PAS foi pour le montant net, car le moteur historique de tranches
     * peut y appliquer positivement des avoirs et des paiements de reliquat.
     * La ventilation par frais est donc relue depuis MontantsParFrais, puis
     * appliquée chronologiquement aux échéances.
     *
     * @param array<int, float> $payeParFrais
     * @return array{attendu:float,regle:float,restant:float,echu:float,encaisse:float,ajustements:float}
     */
    public function calculer(
        array $state,
        ESBTPInscription $inscription,
        int $categoryId,
        array $payeParFrais,
        float $netGlobal
    ): array {
        $lignes = array_values(array_filter(
            $state['due_lines'] ?? [],
            fn (array $l) => $categoryId === 0 || (int) ($l['category_id'] ?? 0) === $categoryId
        ));
        usort($lignes, fn (array $a, array $b) =>
            (string) ($a['due_date'] ?? '') <=> (string) ($b['due_date'] ?? '')
        );

        $pools = [];
        foreach ($payeParFrais as $fraisId => $net) {
            $pools[(string) $fraisId] = max(0.0, (float) $net);
        }
        $netNonVentile = $categoryId > 0
            ? 0.0
            : max(0.0, $netGlobal - array_sum($pools));

        // 1. Imputer les montants nets par frais, sans aucun effet des reliquats.
        foreach ($lignes as &$ligne) {
            $key = (string) ($ligne['category_id'] ?? 0);
            $montant = max(0.0, (float) ($ligne['amount'] ?? 0));
            $verse = min($montant, $pools[$key] ?? 0.0);
            $pools[$key] = max(0.0, ($pools[$key] ?? 0.0) - $verse);
            $ligne['report_paid'] = $verse;
            $ligne['report_remaining'] = $montant - $verse;
        }
        unset($ligne);

        // 2. Les paiements globalement non affectés peuvent couvrir les frais
        //    du même dossier, par ordre d'échéance. Jamais dans un filtre de frais.
        if ($netNonVentile > 0) {
            foreach ($lignes as &$ligne) {
                $verse = min($netNonVentile, $ligne['report_remaining']);
                $ligne['report_paid'] += $verse;
                $ligne['report_remaining'] -= $verse;
                $netNonVentile -= $verse;
                if ($netNonVentile <= 0.001) {
                    break;
                }
            }
            unset($ligne);
        }

        $attendu = 0.0;
        $regle = 0.0;
        $restant = 0.0;
        $echu = 0.0;
        foreach ($lignes as $ligne) {
            $attendu += max(0.0, (float) ($ligne['amount'] ?? 0));
            $regle += $ligne['report_paid'];
            $restant += $ligne['report_remaining'];
            if (! empty($ligne['due_date']) && Carbon::parse($ligne['due_date'])
                ->addDays((int) ($ligne['grace_days'] ?? 0))->startOfDay()->lte(today())) {
                $echu += $ligne['report_remaining'];
            }
        }

        // Le montant de caisse est indépendant de l'imputation sur une tranche.
        // Une remise/avoir interne n'enlève de la caisse que s'il s'agit d'un refund.
        $encaisse = $categoryId > 0 ? 0.0 : ESBTPPaiement::netCashFrom(
            $inscription->paiements->filter(fn ($p) => $p->type_paiement !== 'reliquat')
        );

        return [
            'attendu' => round($attendu, 2),
            'regle' => round($regle, 2),
            'restant' => round($restant, 2),
            'echu' => round($echu, 2),
            'encaisse' => round($encaisse, 2),
            'ajustements' => round($regle - $encaisse, 2),
        ];
    }
}
