<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPInscription;
use App\Services\RelanceCalculationService;

/**
 * Calcule les impayés par ancienneté (aging buckets 0-30 / 31-60 / 61-90 / 90+).
 * Délègue les calculs financiers à RelanceCalculationService (canonical).
 */
class GetImpayesAgingAction
{
    private const ACTIVE_INSCRIPTION_STATUSES = ['active', 'en_attente', 'validée'];
    private const STUDENT_PREVIEW_PER_BUCKET = 5;
    private const TOP_ECHUS = 5;

    public function __construct(
        private readonly RelanceCalculationService $relanceCalc,
    ) {}

    /**
     * Aging buckets pour les filtres donnés.
     *
     * @return array<string, array{count:int, amount:float, students:array}>
     */
    public function __invoke(ComptabiliteFilters $filters): array
    {
        return $this->analyse($filters)['buckets'];
    }

    /**
     * Buckets ET plus gros impayés échus, en un seul passage sur les
     * inscriptions : le tableau de bord affiche les deux, et un second passage
     * recalculerait l'état financier de chaque inscription pour rien.
     *
     * @return array{buckets: array<string, array{count:int, amount:float, students:array}>, top: array<int, array>}
     */
    public function analyse(ComptabiliteFilters $filters): array
    {
        $inscriptions = ESBTPInscription::query()
            ->with([
                'etudiant',
                'classe:id,name',
                'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
            ])
            ->when($filters->anneeId, fn ($q) => $q->where('annee_universitaire_id', $filters->anneeId))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->where('classe_id', $filters->classeId))
            ->get();

        if ($inscriptions->isEmpty()) {
            return ['buckets' => $this->emptyBuckets(), 'top' => []];
        }

        $this->relanceCalc->preloadForInscriptions($inscriptions);

        $buckets = $this->emptyBuckets();
        $echus = [];

        foreach ($inscriptions as $inscription) {
            $state = $this->relanceCalc->getFinancialState($inscription);
            $soldeRestant = (float) ($state['overdue_amount'] ?? 0);

            if ($soldeRestant <= 0) {
                continue;
            }

            $joursRetard = (int) ($state['overdue_days'] ?? 0);
            $bucketKey = $this->bucketKeyFor($joursRetard);

            $ligne = [
                'id' => $inscription->etudiant->id ?? null,
                'inscription_id' => $inscription->id,
                'nom' => $inscription->etudiant->nom_complet ?? 'N/A',
                'classe' => $inscription->classe->name ?? null,
                'solde' => $soldeRestant,
                'jours' => $joursRetard,
            ];

            $buckets[$bucketKey]['count']++;
            $buckets[$bucketKey]['amount'] += $soldeRestant;
            if (count($buckets[$bucketKey]['students']) < self::STUDENT_PREVIEW_PER_BUCKET) {
                $buckets[$bucketKey]['students'][] = $ligne;
            }
            $echus[] = $ligne;
        }

        usort($echus, fn ($a, $b) => $b['solde'] <=> $a['solde']);

        return ['buckets' => $buckets, 'top' => array_slice($echus, 0, self::TOP_ECHUS)];
    }

    /**
     * Total dû agrégé sur les filtres (utilisé par BuildDashboardDataAction).
     *
     * @return array{totalDue:float, countDue:int, parClasse:array<int, float>}
     */
    public function totalDuForFilters(ComptabiliteFilters $filters): array
    {
        $inscriptions = ESBTPInscription::query()
            ->whereIn('status', self::ACTIVE_INSCRIPTION_STATUSES)
            ->when($filters->anneeId, fn ($q) => $q->where('annee_universitaire_id', $filters->anneeId))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->where('classe_id', $filters->classeId))
            ->get(['id', 'classe_id', 'filiere_id', 'niveau_id', 'affectation_status']);

        if ($inscriptions->isEmpty()) {
            return ['totalDue' => 0.0, 'countDue' => 0, 'parClasse' => []];
        }

        $this->relanceCalc->preloadForInscriptions($inscriptions);

        $totalDue = 0.0;
        $countDue = 0;
        $parClasse = [];
        foreach ($inscriptions as $inscription) {
            $montant = $this->relanceCalc->calculerTotalDu($inscription);
            if ($montant > 0) {
                $totalDue += $montant;
                $countDue++;
                $cle = (int) $inscription->classe_id;
                $parClasse[$cle] = ($parClasse[$cle] ?? 0.0) + $montant;
            }
        }

        return ['totalDue' => $totalDue, 'countDue' => $countDue, 'parClasse' => $parClasse];
    }

    private function emptyBuckets(): array
    {
        return [
            '0-30' => ['count' => 0, 'amount' => 0, 'students' => []],
            '31-60' => ['count' => 0, 'amount' => 0, 'students' => []],
            '61-90' => ['count' => 0, 'amount' => 0, 'students' => []],
            '90+' => ['count' => 0, 'amount' => 0, 'students' => []],
        ];
    }

    private function bucketKeyFor(int $joursRetard): string
    {
        return match (true) {
            $joursRetard <= 30 => '0-30',
            $joursRetard <= 60 => '31-60',
            $joursRetard <= 90 => '61-90',
            default => '90+',
        };
    }
}
