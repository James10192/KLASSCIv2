<?php

namespace App\Domain\Assistant\Consommation;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Budget mensuel d'IA de l'école, en francs CFA.
 *
 *   normal      sous le budget
 *   economique  budget atteint : l'assistant ne prend plus que le palier le moins cher
 *   pause       seuil de pause atteint : l'assistant se repose jusqu'au mois suivant
 *
 * Pas de budget déclaré (vide ou 0) = pas de limite. D'où vient le budget, dans l'ordre :
 *   1. adminKlassci, qui pilote les coûts de toutes les écoles : champ
 *      `assistant.budget_mensuel_fcfa` de la réponse /tenants/{code}/limits, lue dans
 *      le cache que PaywallMiddleware remplit (5 min) ; aucun appel réseau ici ;
 *   2. le réglage d'instance `assistant.budget_mensuel_fcfa` (klassci-cli) ;
 *   3. le .env.
 */
class BudgetAssistant
{
    public const NORMAL = 'normal';
    public const ECONOMIQUE = 'economique';
    public const PAUSE = 'pause';

    private const CLE_CACHE = 'assistant.depense_du_mois';

    public function budgetMensuelFcfa(): ?float
    {
        $master = $this->budgetDuMaster();
        if ($master !== null) {
            return $master > 0 ? $master : null;
        }

        try {
            $reglage = SettingsHelper::get('assistant.budget_mensuel_fcfa');
        } catch (\Throwable $e) {
            Log::warning('assistant.budget_illisible', ['erreur' => $e->getMessage()]);
            $reglage = null;
        }
        $valeur = (float) (($reglage !== null && $reglage !== '') ? $reglage : config('assistant.budget.mensuel_fcfa', 0));

        return $valeur > 0 ? $valeur : null;
    }

    /** Budget posé dans adminKlassci, s'il a déjà été lu ; null si le master ne l'a pas fixé. */
    private function budgetDuMaster(): ?float
    {
        $code = config('app.tenant_code');
        $limites = $code ? Cache::get('paywall_limits_' . $code) : null;
        $valeur = is_array($limites) ? ($limites['assistant']['budget_mensuel_fcfa'] ?? null) : null;

        return is_numeric($valeur) ? (float) $valeur : null;
    }

    public function depenseDuMois(): float
    {
        return (float) Cache::remember(self::CLE_CACHE . '.' . now()->format('Y-m'), 60, fn () => (float) LigneDeConsommation::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('cout_fcfa'));
    }

    public function etat(): string
    {
        $budget = $this->budgetMensuelFcfa();
        if ($budget === null) {
            return self::NORMAL;
        }

        $part = $this->depenseDuMois() / $budget * 100;

        return match (true) {
            $part >= (float) config('assistant.budget.seuil_pause', 120) => self::PAUSE,
            $part >= (float) config('assistant.budget.seuil_economique', 100) => self::ECONOMIQUE,
            default => self::NORMAL,
        };
    }

    public function oublier(): void
    {
        Cache::forget(self::CLE_CACHE . '.' . now()->format('Y-m'));
    }
}
