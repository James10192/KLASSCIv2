<?php

namespace App\Services;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Collection;

class RelanceCalculationService
{
    private ?Collection $categories = null;
    private ?Collection $subscriptions = null;
    private ?Collection $configurations = null;

    /**
     * Charge les données de référence une seule fois pour un batch d'inscriptions.
     * Appeler avant de boucler sur calculerTotalDu / getRiskLevel.
     */
    public function preloadForInscriptions(Collection $inscriptions): self
    {
        $this->categories = ESBTPFraisCategory::where('is_active', true)->get();

        $inscriptionIds = $inscriptions->pluck('id')->toArray();

        $this->subscriptions = ESBTPFraisSubscription::where('is_active', true)
            ->whereIn('inscription_id', $inscriptionIds)
            ->get()
            ->groupBy('inscription_id');

        $this->configurations = ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $this->categories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        return $this;
    }

    /**
     * Charge les données pour une seule inscription (mode fiche étudiant).
     */
    public function preloadForSingle(ESBTPInscription $inscription): self
    {
        $this->categories = ESBTPFraisCategory::where('is_active', true)->get();

        $this->subscriptions = ESBTPFraisSubscription::where('is_active', true)
            ->where('inscription_id', $inscription->id)
            ->get()
            ->groupBy('inscription_id');

        $this->configurations = ESBTPFraisConfiguration::where('is_active', true)
            ->whereIn('frais_category_id', $this->categories->pluck('id'))
            ->get()
            ->groupBy(fn($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        return $this;
    }

    /**
     * Calcule le total dû pour une inscription.
     * preload() doit avoir été appelé avant.
     */
    public function calculerTotalDu(ESBTPInscription $inscription): float
    {
        $inscriptionSubs = $this->subscriptions->get($inscription->id, collect());
        $totalDu = 0;

        foreach ($this->categories as $category) {
            $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();

            if ($category->is_mandatory) {
                if ($sub) {
                    $montant = $sub->amount;
                } else {
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $config = $this->configurations->get($configKey, collect())->first();
                    $montant = $config
                        ? $config->getMontantByStatus($inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
                        : $category->default_amount;
                }
            } else {
                $montant = $sub ? $sub->amount : 0;
            }

            if ($montant > 0) {
                $totalDu += $montant;
            }
        }

        return (float) $totalDu;
    }

    /**
     * Calcule le détail des frais par catégorie pour une inscription.
     */
    public function calculerFraisDetail(ESBTPInscription $inscription): Collection
    {
        $inscriptionSubs = $this->subscriptions->get($inscription->id, collect());
        $detail = [];

        foreach ($this->categories as $category) {
            $sub = $inscriptionSubs->where('frais_category_id', $category->id)->first();

            if ($category->is_mandatory) {
                if ($sub) {
                    $montant = $sub->amount;
                } else {
                    $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
                    $config = $this->configurations->get($configKey, collect())->first();
                    $montant = $config
                        ? $config->getMontantByStatus($inscription->affectation_status ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS)
                        : $category->default_amount;
                }
            } else {
                $montant = $sub ? $sub->amount : 0;
            }

            if ($montant <= 0) continue;

            $paye = $inscription->paiements
                ->where('frais_category_id', $category->id)
                ->sum('montant');

            $detail[] = [
                'name'   => $category->name,
                'amount' => $montant,
                'paye'   => $paye,
            ];
        }

        return collect($detail);
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
        $totalDu            = $this->calculerTotalDu($inscription);
        $totalPaye          = $inscription->paiements->where('status', 'validé')->sum('montant');
        $totalPayeEnAttente = $inscription->paiements->where('status', 'en_attente')->sum('montant');
        $soldeRestant       = max(0, $totalDu - $totalPaye);
        $pourcentage        = $totalDu > 0 ? min(100, round($totalPaye / $totalDu * 100)) : 100;

        $riskInfo = $this->getRiskLevel($totalDu, $totalPaye);

        return (object) [
            'inscription'       => $inscription,
            'totalDu'           => $totalDu,
            'totalPaye'         => $totalPaye,
            'totalPayeEnAttente'=> $totalPayeEnAttente,
            'soldeRestant'      => $soldeRestant,
            'pourcentage'       => $pourcentage,
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
     * Calcule la date d'échéance d'une inscription.
     * = inscription.created_at + min(payment_deadline_days) de toutes les catégories obligatoires.
     *
     * Priorité : config filière/niveau > catégorie > défaut 30j
     */
    public function getDateEcheance(ESBTPInscription $inscription): \Carbon\Carbon
    {
        $minDeadline = null;

        foreach ($this->categories as $category) {
            if (!$category->is_mandatory) continue;

            // Chercher l'override config filière/niveau
            $configKey = $category->id . '_' . $inscription->filiere_id . '_' . $inscription->niveau_id;
            $config = $this->configurations->get($configKey, collect())->first();

            $deadline = $config && $config->payment_deadline_days
                ? $config->payment_deadline_days
                : ($category->payment_deadline_days ?? 30);

            if ($minDeadline === null || $deadline < $minDeadline) {
                $minDeadline = $deadline;
            }
        }

        return $inscription->created_at->copy()->addDays($minDeadline ?? 30);
    }

    /**
     * Calcule le nombre de jours de retard d'une inscription.
     * Retourne 0 si pas encore en retard.
     */
    public function getJoursRetard(ESBTPInscription $inscription): int
    {
        $echeance = $this->getDateEcheance($inscription);
        $jours = $echeance->diffInDays(now(), false);
        return max(0, (int) $jours);
    }

    /**
     * Vérifie si une inscription est en retard de paiement.
     */
    public function isOverdue(ESBTPInscription $inscription): bool
    {
        return $this->getJoursRetard($inscription) > 0;
    }
}
