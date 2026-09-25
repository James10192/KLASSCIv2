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
 *      `assistant.budget_mensuel_fcfa` de la réponse /tenants/{code}/limits. Lue dans
 *      le cache que PaywallMiddleware remplit (5 min) ; cache froid — l'assistant
 *      ne passe pas par ce middleware — on interroge le master (3 s au plus). La
 *      dernière valeur connue est gardée sans limite de durée : un master
 *      injoignable ne rend jamais la main au réglage de l'école ;
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
        $valeur = $this->lire()['valeur'];

        return $valeur > 0 ? $valeur : null;
    }

    /** D'où vient le budget en vigueur : master (adminKlassci), ecole (réglage) ou env. */
    public function source(): string
    {
        return $this->lire()['source'];
    }

    /** @return array{valeur: float, source: string} */
    private function lire(): array
    {
        $master = $this->budgetDuMaster();
        if ($master !== null) {
            return ['valeur' => $master, 'source' => 'master'];
        }

        try {
            $reglage = SettingsHelper::get('assistant.budget_mensuel_fcfa');
        } catch (\Throwable $e) {
            Log::warning('assistant.budget_illisible', ['erreur' => $e->getMessage()]);
            $reglage = null;
        }
        if ($reglage !== null && $reglage !== '') {
            return ['valeur' => (float) $reglage, 'source' => 'ecole'];
        }

        return ['valeur' => (float) config('assistant.budget.mensuel_fcfa', 0), 'source' => 'env'];
    }

    /** Budget posé dans adminKlassci ; null si le master ne l'a pas fixé (ou n'a jamais répondu). */
    private function budgetDuMaster(): ?float
    {
        $code = config('app.tenant_code');
        if (! $code) {
            return null;
        }

        $limites = Cache::get('paywall_limits_' . $code) ?? $this->limitesDuMaster($code);
        $connu = 'assistant.budget_master.' . $code;
        if (is_array($limites)) {
            // Une réponse du master fait foi, y compris quand elle ne fixe rien.
            $valeur = $limites['assistant']['budget_mensuel_fcfa'] ?? null;
            Cache::forever($connu, ['valeur' => is_numeric($valeur) ? (float) $valeur : null]);
        }

        $retenu = Cache::get($connu);

        return is_array($retenu) && is_numeric($retenu['valeur'] ?? null) ? (float) $retenu['valeur'] : null;
    }

    /**
     * Cache froid : on interroge le master comme PaywallMiddleware, et on remplit
     * la même clé. Un échec est retenu une minute pour ne pas ralentir chaque échange.
     */
    private function limitesDuMaster(string $code): ?array
    {
        $url = config('services.master.api_url');
        $jeton = config('services.master.api_token');
        if (! $url || ! $jeton || Cache::has('assistant.master_injoignable')) {
            return null;
        }

        try {
            $reponse = \Illuminate\Support\Facades\Http::withToken($jeton)->timeout(3)->get(rtrim($url, '/') . '/tenants/' . $code . '/limits');
            if ($reponse->successful() && is_array($donnees = $reponse->json())) {
                Cache::put('paywall_limits_' . $code, $donnees, 300);

                return $donnees;
            }
            Log::warning('assistant.budget_master_illisible', ['statut' => $reponse->status()]);
        } catch (\Throwable $e) {
            Log::warning('assistant.budget_master_injoignable', ['erreur' => $e->getMessage()]);
        }
        Cache::put('assistant.master_injoignable', true, 60);

        return null;
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
