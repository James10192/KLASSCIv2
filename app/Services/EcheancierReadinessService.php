<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EcheancierReadinessService
{
    public const MODE_CONFIGURED = 'configured';
    public const MODE_FALLBACK = 'fallback';

    /**
     * Retourne null quand l'infra échéanciers est prête (tables + colonnes).
     * N'exige PAS la présence d'au moins une règle active : un fallback
     * basé sur `payment_deadline_days` (configuration de frais → catégorie
     * → 30j) est appliqué par EcheancierComputationService quand aucune
     * règle n'est résolue. Voir mode() pour distinguer les deux contextes.
     */
    public function unavailableReason(): ?string
    {
        try {
            foreach (['esbtp_echeancier_rules', 'esbtp_echeancier_rule_lines'] as $table) {
                if (!Schema::hasTable($table)) {
                    return $this->migrationReason();
                }
            }

            if (
                !Schema::hasColumn('esbtp_echeancier_rules', 'is_active')
                || !Schema::hasColumn('esbtp_echeancier_rule_lines', 'is_active')
            ) {
                return $this->migrationReason();
            }
        } catch (Throwable) {
            return $this->migrationReason();
        }

        return null;
    }

    /**
     * 'configured' = au moins une règle active avec lignes actives.
     * 'fallback'   = aucune règle, calcul basé sur payment_deadline_days
     *                (configuration de frais → catégorie → défaut 30j).
     */
    public function mode(): string
    {
        try {
            $hasActiveRules = ESBTPEcheancierRule::query()
                ->where('is_active', true)
                ->whereHas('lines', fn ($query) => $query->where('is_active', true))
                ->exists();

            return $hasActiveRules ? self::MODE_CONFIGURED : self::MODE_FALLBACK;
        } catch (Throwable) {
            return self::MODE_FALLBACK;
        }
    }

    /**
     * Note utilisateur explicite quand on est en mode fallback. Null sinon.
     */
    public function noteForMode(): ?string
    {
        if ($this->mode() === self::MODE_CONFIGURED) {
            return null;
        }

        return "Mode dégradé : calcul basé sur l'échéance par défaut de chaque catégorie de frais. "
            . "Configurez vos règles d'échéancier pour un suivi mensuel précis et étalé.";
    }

    private function migrationReason(): string
    {
        return "Les échéanciers ne sont pas encore installés complètement. Lancez les migrations puis configurez les règles d'échéance.";
    }
}
