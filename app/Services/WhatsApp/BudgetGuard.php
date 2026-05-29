<?php

namespace App\Services\WhatsApp;

use App\Helpers\SettingsHelper;
use App\Models\ParentNotificationLog;
use Illuminate\Support\Facades\Log;

/**
 * Budget guard mensuel par tenant (Phase 4 Plan v4 — hardening).
 *
 * Vérifie si le tenant a dépassé son budget mensuel WhatsApp.
 * Auto-suspension à 150% pour éviter dérapage cost shock.
 */
class BudgetGuard
{
    public function isOverBudget(string $tenantCode, ?int $budgetXof = null): bool
    {
        $budget = $budgetXof ?? (int) SettingsHelper::get('whatsapp.monthly_budget_fcfa', 50000);
        $current = $this->currentMonthCost($tenantCode);

        return $current > $budget;
    }

    public function isCriticalOverage(string $tenantCode, ?int $budgetXof = null): bool
    {
        $budget = $budgetXof ?? (int) SettingsHelper::get('whatsapp.monthly_budget_fcfa', 50000);
        $current = $this->currentMonthCost($tenantCode);

        // 150% budget = auto-suspension threshold
        return $current >= ($budget * 1.5);
    }

    public function currentMonthCost(string $tenantCode): float
    {
        return (float) ParentNotificationLog::where('channel', 'whatsapp')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('cost_fcfa');
    }

    public function snapshot(string $tenantCode): array
    {
        $budget = (int) SettingsHelper::get('whatsapp.monthly_budget_fcfa', 50000);
        $current = $this->currentMonthCost($tenantCode);

        return [
            'budget_fcfa' => $budget,
            'used_fcfa' => $current,
            'remaining_fcfa' => max(0, $budget - $current),
            'percentage' => $budget > 0 ? round(($current / $budget) * 100, 2) : 0,
            'over_budget' => $current > $budget,
            'critical_overage' => $current >= ($budget * 1.5),
        ];
    }

    /**
     * Alerte logging pour Slack/notification ops si dépassement.
     */
    public function logIfOver(string $tenantCode): void
    {
        $snap = $this->snapshot($tenantCode);

        if ($snap['critical_overage']) {
            Log::critical('[wa-budget] CRITICAL — auto-suspension recommandée', [
                'tenant' => $tenantCode,
                'snapshot' => $snap,
            ]);
        } elseif ($snap['over_budget']) {
            Log::warning('[wa-budget] Budget dépassé', [
                'tenant' => $tenantCode,
                'snapshot' => $snap,
            ]);
        }
    }
}
