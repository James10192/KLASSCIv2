<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPPaiement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RelanceCalculationService
{
    private ?Collection $categories = null;
    private ?Collection $subscriptions = null;
    private ?Collection $configurations = null;
    private array $echeancierStates = [];

    public function __construct(
        private readonly EcheancierComputationService $echeancierComputation,
        private readonly EcheancierSnapshotService $snapshotService,
    ) {}

    /**
     * Charge les données de référence une seule fois pour un batch d'inscriptions.
     * Appeler avant de boucler sur calculerTotalDu / getRiskLevel.
     */
    public function preloadForInscriptions(Collection $inscriptions): self
    {
        $this->categories = ESBTPFraisCategory::where('is_active', true)->get();

        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $this->subscriptions = ESBTPFraisSubscription::with(['selectedOption.assignments'])
            ->where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->groupBy('inscription_id');

        $this->configurations = ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $this->categories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $this->echeancierStates = [];

        return $this;
    }

    /**
     * Charge les données pour une seule inscription (mode fiche étudiant).
     */
    public function preloadForSingle(ESBTPInscription $inscription): self
    {
        $inscription->loadMissing([
            'fraisSubscriptions.selectedOption.assignments',
            'paiements' => fn($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ]);

        $this->categories = ESBTPFraisCategory::where('is_active', true)->get();

        $this->subscriptions = ESBTPFraisSubscription::with(['selectedOption.assignments'])
            ->where('is_active', true)
            ->where('inscription_id', $inscription->id)
            ->get()
            ->groupBy('inscription_id');

        $this->configurations = ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $this->categories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $this->echeancierStates = [];

        try {
            $this->snapshotService->refreshForInscription($inscription);
        } catch (\Throwable $e) {
            // Silent fallback: calcul runtime only
        }

        return $this;
    }

    /**
     * Calcule le total dû pour une inscription.
     * preload() doit avoir été appelé avant.
     */
    public function calculerTotalDu(ESBTPInscription $inscription): float
    {
        return (float) ($this->getEcheancierState($inscription)['total_due'] ?? 0.0);
    }

    /**
     * Calcule le détail des frais par catégorie pour une inscription.
     */
    public function calculerFraisDetail(ESBTPInscription $inscription): Collection
    {
        $state = $this->getEcheancierState($inscription);

        return collect($state['categories'] ?? [])->map(function ($category) {
            return [
                'name' => $category['category_name'] ?? 'N/A',
                'amount' => (float) ($category['total_due'] ?? 0),
                'paye' => (float) ($category['total_paid'] ?? 0),
            ];
        });
    }

    /**
     * Détermine le niveau de risque d'une inscription.
     *
     * @return array{risk: string, label: string, color: string}
     */
    public function getRiskLevel(float $totalDu, float $totalPaye): array
    {
        $soldeRestant = max(0, $totalDu - $totalPaye);

        if ($soldeRestant <= 0) {
            return ['risk' => 'low', 'label' => 'À jour', 'color' => '#10b981'];
        }
        if ($totalDu > 0 && ($soldeRestant / $totalDu) <= 0.25) {
            return ['risk' => 'medium', 'label' => 'Presque soldé', 'color' => '#5e91de'];
        }
        if ($totalPaye > 0) {
            return ['risk' => 'high', 'label' => 'En cours', 'color' => '#0453cb'];
        }

        return ['risk' => 'critical', 'label' => 'Impayé', 'color' => '#1e293b'];
    }

    /**
     * Construit un objet résumé financier pour une inscription.
     * Nécessite que paiements soient eager-loaded.
     */
    public function buildRow(ESBTPInscription $inscription): object
    {
        $state = $this->getEcheancierState($inscription);

        $totalDu            = (float) ($state['total_due'] ?? 0);
        $totalPaye          = (float) ($state['total_paid_validated'] ?? 0);
        $totalPayeEnAttente = $inscription->paiements->where('status', 'en_attente')->sum('montant');
        $soldeRestant       = (float) ($state['remaining_total'] ?? max(0, $totalDu - $totalPaye));
        $pourcentage        = $totalDu > 0 ? min(100, round($totalPaye / $totalDu * 100)) : 100;

        $riskInfo = $this->getRiskLevel($totalDu, $totalPaye);

        return (object) [
            'inscription'       => $inscription,
            'totalDu'           => $totalDu,
            'totalPaye'         => $totalPaye,
            'totalPayeEnAttente'=> $totalPayeEnAttente,
            'soldeRestant'      => $soldeRestant,
            'pourcentage'       => $pourcentage,
            'expectedDueToDate' => (float) ($state['expected_due_to_date'] ?? 0),
            'paidDueToDate'     => (float) ($state['paid_due_to_date'] ?? 0),
            'overdueAmount'     => (float) ($state['overdue_amount'] ?? 0),
            'overdueDays'       => (int) ($state['overdue_days'] ?? 0),
            'risk'              => $riskInfo['risk'],
            'riskLabel'         => $riskInfo['label'],
        ];
    }

    /**
     * Construit les rows pour un batch d'inscriptions + KPIs globaux.
     *
     * @return array{rows: Collection, kpis: array}
     */
    public function buildBatch(Collection $inscriptions): array
    {
        $rows = $inscriptions->map(fn($ins) => $this->buildRow($ins));

        $allRowsWithDebt = $rows->filter(fn($r) => $r->soldeRestant > 0);

        $kpis = [
            'total_impaye'    => $allRowsWithDebt->sum(fn($r) => $r->soldeRestant),
            'total_en_attente'=> $rows->sum(fn($r) => $r->totalPayeEnAttente),
            'count_critical'  => $rows->where('risk', 'critical')->count(),
            'count_high'      => $rows->where('risk', 'high')->count(),
            'count_medium'    => $rows->where('risk', 'medium')->count(),
            'count_low'       => $rows->where('risk', 'low')->count(),
            'total_etudiants' => $allRowsWithDebt->count(),
        ];

        return ['rows' => $rows, 'kpis' => $kpis];
    }

    /**
     * Calcule la dette d'un étudiant via ses inscriptions actives.
     * Alternative à l'ancien calculerDette() qui utilisait ESBTPFacture.
     */
    public function calculerDetteEtudiant(\App\Models\ESBTPEtudiant $etudiant): float
    {
        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeActive) return 0;

        $inscriptions = ESBTPInscription::with([
            'fraisSubscriptions',
            'paiements' => fn($q) => $q->where('status', 'validé')->whereNull('deleted_at'),
        ])
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get();

        if ($inscriptions->isEmpty()) return 0;

        $this->preloadForInscriptions($inscriptions);

        $totalDette = 0;
        foreach ($inscriptions as $inscription) {
            $totalDu   = $this->calculerTotalDu($inscription);
            $totalPaye = $inscription->paiements->sum('montant');
            $totalDette += max(0, $totalDu - $totalPaye);
        }

        return $totalDette;
    }

    public function getCategories(): ?Collection
    {
        return $this->categories;
    }

    /**
     * Retourne l'état financier calculé selon l'échéancier (dette totale + retard réel).
     *
     * @return array<string, mixed>
     */
    public function getFinancialState(ESBTPInscription $inscription): array
    {
        return $this->getEcheancierState($inscription);
    }

    /**
     * Calcule la date d'échéance d'une inscription.
     * = inscription.created_at + min(payment_deadline_days) de toutes les catégories obligatoires.
     *
     * Priorité : config filière/niveau > catégorie > défaut 30j
     */
    public function getDateEcheance(ESBTPInscription $inscription): \Carbon\Carbon
    {
        $state = $this->getEcheancierState($inscription);
        $lines = collect($state['due_lines'] ?? []);

        if ($lines->isNotEmpty()) {
            $earliest = $lines->map(function ($line) {
                return Carbon::parse($line['due_date'])->addDays((int) ($line['grace_days'] ?? 0));
            })->sort()->first();

            if ($earliest) {
                return $earliest;
            }
        }

        return $inscription->created_at->copy()->addDays(30);
    }

    /**
     * Calcule le nombre de jours de retard d'une inscription.
     * Retourne 0 si pas encore en retard.
     */
    public function getJoursRetard(ESBTPInscription $inscription): int
    {
        $state = $this->getEcheancierState($inscription);
        return (int) ($state['overdue_days'] ?? 0);
    }

    /**
     * Vérifie si une inscription est en retard de paiement.
     */
    public function isOverdue(ESBTPInscription $inscription): bool
    {
        $state = $this->getEcheancierState($inscription);
        return (float) ($state['overdue_amount'] ?? 0) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function getEcheancierState(ESBTPInscription $inscription): array
    {
        $this->ensurePreloaded($inscription);

        $cacheKey = (int) $inscription->id;
        if (isset($this->echeancierStates[$cacheKey])) {
            return $this->echeancierStates[$cacheKey];
        }

        $inscriptionSubscriptions = $this->subscriptions?->get($inscription->id, collect()) ?? collect();

        $schedule = $this->echeancierComputation->buildScheduleForInscription(
            $inscription,
            $this->categories ?? collect(),
            $this->configurations ?? collect(),
            $inscriptionSubscriptions
        );

        $validatedPayments = $inscription->relationLoaded('paiements')
            ? $inscription->paiements->where('status', 'validé')
            : ESBTPPaiement::query()
                ->where('inscription_id', $inscription->id)
                ->where('status', 'validé')
                ->whereNull('deleted_at')
                ->get();

        $state = $this->echeancierComputation->computeOverdueForSchedule(
            $schedule,
            $validatedPayments,
            Carbon::now()
        );

        $this->echeancierStates[$cacheKey] = $state;

        return $state;
    }

    private function ensurePreloaded(ESBTPInscription $inscription): void
    {
        if ($this->categories !== null && $this->subscriptions !== null && $this->configurations !== null) {
            return;
        }

        $this->preloadForSingle($inscription);
    }
}
