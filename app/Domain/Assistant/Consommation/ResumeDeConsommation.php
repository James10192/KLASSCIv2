<?php

namespace App\Domain\Assistant\Consommation;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Ce que l'assistant a coûté sur une période : totaux, puis par modèle, palier,
 * fonction, jour et personne. Servi à klassci-cli et remonté à adminKlassci.
 */
class ResumeDeConsommation
{
    public function __construct(private BudgetAssistant $budget)
    {
    }

    public function pour(CarbonInterface $depuis, CarbonInterface $jusqua): array
    {
        $base = fn () => LigneDeConsommation::query()->whereBetween('assistant_consommations.created_at', [$depuis, $jusqua]);
        $sommes = self::sommes('assistant_consommations');

        $total = $base()->selectRaw($sommes)->first();
        $par = fn (string $colonne) => $base()->selectRaw("{$colonne} as cle, {$sommes}")
            ->groupBy($colonne)->orderByDesc('cout_fcfa')->get()->map(fn ($l) => $this->ligne($l))->all();

        $parPersonne = $base()->leftJoin('users', 'users.id', '=', 'assistant_consommations.user_id')
            ->selectRaw('assistant_consommations.user_id as cle, MAX(users.name) as nom, ' . $sommes)
            ->groupBy('assistant_consommations.user_id')->orderByDesc('cout_fcfa')->limit(10)->get()
            ->map(fn ($l) => $this->ligne($l) + ['nom' => $l->nom])->all();

        $parJour = $base()->selectRaw('DATE(assistant_consommations.created_at) as cle, ' . $sommes)
            ->groupBy(DB::raw('DATE(assistant_consommations.created_at)'))->orderBy('cle')->get()->map(fn ($l) => $this->ligne($l))->all();

        return [
            'periode' => ['depuis' => $depuis->toDateString(), 'jusqua' => $jusqua->toDateString()],
            'total' => $this->ligne($total),
            'par_modele' => $par('modele'),
            'par_palier' => $par('palier'),
            'par_fonction' => $par('fonction'),
            'par_jour' => $parJour,
            'par_personne' => $parPersonne,
            'budget' => [
                'mensuel_fcfa' => $this->budget->budgetMensuelFcfa(),
                'depense_du_mois_fcfa' => round($this->budget->depenseDuMois(), 2),
                'etat' => $this->budget->etat(),
            ],
        ];
    }

    private static function sommes(string $t): string
    {
        return "COUNT(DISTINCT {$t}.message_id) as echanges, SUM({$t}.appels) as appels, "
            . "SUM({$t}.tokens_entree) as tokens_entree, SUM({$t}.tokens_sortie) as tokens_sortie, "
            . "SUM({$t}.cout_usd) as cout_usd, SUM({$t}.cout_fcfa) as cout_fcfa";
    }

    private function ligne($l): array
    {
        return [
            'cle' => $l->cle ?? null,
            'echanges' => (int) ($l->echanges ?? 0),
            'appels' => (int) ($l->appels ?? 0),
            'tokens_entree' => (int) ($l->tokens_entree ?? 0),
            'tokens_sortie' => (int) ($l->tokens_sortie ?? 0),
            'cout_usd' => round((float) ($l->cout_usd ?? 0), 6),
            'cout_fcfa' => round((float) ($l->cout_fcfa ?? 0), 2),
        ];
    }
}
