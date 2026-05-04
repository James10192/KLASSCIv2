<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use Illuminate\Support\Facades\Schema;

class EcheancierReadinessService
{
    /**
     * Returns null when échéanciers are ready for overdue-driven analytics.
     */
    public function unavailableReason(): ?string
    {
        foreach (['esbtp_echeancier_rules', 'esbtp_echeancier_rule_lines'] as $table) {
            if (!Schema::hasTable($table)) {
                return "Les échéanciers ne sont pas encore installés. Lancez les migrations puis configurez les règles d'échéance.";
            }
        }

        $hasActiveRules = ESBTPEcheancierRule::query()
            ->where('is_active', true)
            ->whereHas('lines', fn ($query) => $query->where('is_active', true))
            ->exists();

        if (!$hasActiveRules) {
            return "Configurez au moins un échéancier actif avec ses tranches avant d'utiliser le risque de défaut.";
        }

        return null;
    }
}
