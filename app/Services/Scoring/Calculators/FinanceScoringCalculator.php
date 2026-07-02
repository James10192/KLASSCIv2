<?php

namespace App\Services\Scoring\Calculators;

use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;

class FinanceScoringCalculator
{
    use ScoresDatabaseActivity;

    public function calculate(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        return match ($dimension) {
            'payments_validation' => $this->paymentsValidation($dimension, $definition, $user, $start, $end),
            'finance_reporting' => $this->financeReporting($dimension, $definition, $user, $start, $end),
            default => new PersonnelScoreResult($dimension, $definition['label'], 0, $definition['weight'] ?? 0),
        };
    }

    private function paymentsValidation(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $validated = $this->countRows('esbtp_paiements', $start, $end, fn ($query) => $query
            ->where('validateur_id', $user->id)
            ->whereIn('status', ['validé', 'validated', 'payé', 'paid']));

        $rejected = $this->countRows('esbtp_paiements', $start, $end, fn ($query) => $query
            ->where('validateur_id', $user->id)
            ->whereIn('status', ['rejeté', 'rejected']));

        $total = $validated + $rejected;
        $quality = $total > 0 ? $this->scoreByRate($validated, $total) : 0;
        $volume = $this->scoreByTarget($total, 30);
        $score = min(100, ($quality * 0.65) + ($volume * 0.35));

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'paiements_valides' => $validated,
            'paiements_rejetes' => $rejected,
            'paiements_traites' => $total,
        ]);
    }

    private function financeReporting(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $audits = $this->countRows('audits', $start, $end, fn ($query) => $query
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('auditable_type', 'like', '%Paiement%')
                    ->orWhere('auditable_type', 'like', '%Reconciliation%')
                    ->orWhere('tags', 'like', '%compt%');
            }));

        $score = $this->scoreByTarget($audits, 10);

        return new PersonnelScoreResult($dimension, $definition['label'], $score, $definition['weight'], [
            'actions_financieres_auditees' => $audits,
        ]);
    }
}
