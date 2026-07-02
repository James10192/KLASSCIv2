<?php

namespace App\Services\Scoring\Calculators;

use App\Models\User;
use App\Services\Scoring\Concerns\ScoresDatabaseActivity;
use App\Services\Scoring\PersonnelScoreResult;
use Carbon\CarbonInterface;

class CashierScoringCalculator
{
    use ScoresDatabaseActivity;

    public function calculate(string $dimension, array $definition, User $user, CarbonInterface $start, CarbonInterface $end): PersonnelScoreResult
    {
        $created = $this->countRows('esbtp_paiements', $start, $end, fn ($query) => $query->where('created_by', $user->id));
        $validated = $this->countRows('esbtp_paiements', $start, $end, fn ($query) => $query
            ->where('created_by', $user->id)
            ->whereIn('status', ['validé', 'validated', 'payé', 'paid']));
        $rejected = $this->countRows('esbtp_paiements', $start, $end, fn ($query) => $query
            ->where('created_by', $user->id)
            ->whereIn('status', ['rejeté', 'rejected']));

        $volume = $this->scoreByTarget($created, 30);
        $quality = $created > 0 ? $this->scoreByRate($validated, max(1, $validated + $rejected)) : 0;
        $score = min(100, ($volume * 0.55) + ($quality * 0.45));

        return new PersonnelScoreResult($dimension, $definition['label'], (int) round($score), $definition['weight'], [
            'paiements_crees' => $created,
            'paiements_valides' => $validated,
            'paiements_rejetes' => $rejected,
        ]);
    }
}
