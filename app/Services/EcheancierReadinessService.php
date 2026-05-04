<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EcheancierReadinessService
{
    /**
     * Returns null when echeanciers are ready for overdue-driven analytics.
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

            $hasActiveRules = ESBTPEcheancierRule::query()
                ->where('is_active', true)
                ->whereHas('lines', fn ($query) => $query->where('is_active', true))
                ->exists();
        } catch (Throwable) {
            return $this->migrationReason();
        }

        if (!$hasActiveRules) {
            return "Configurez au moins un echeancier actif avec ses tranches avant d'utiliser le risque de defaut.";
        }

        return null;
    }

    private function migrationReason(): string
    {
        return "Les echeanciers ne sont pas encore installes completement. Lancez les migrations puis configurez les regles d'echeance.";
    }
}
