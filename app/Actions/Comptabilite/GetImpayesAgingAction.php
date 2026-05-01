<?php

namespace App\Actions\Comptabilite;

use App\DTOs\Comptabilite\ComptabiliteFilters;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;

/**
 * Calcule les impayés par ancienneté (aging buckets 0-30 / 31-60 / 61-90 / 90+).
 * Ancienneté basée sur l'échéance de paiement (deadline_days par catégorie),
 * pas sur la date d'inscription.
 *
 * Extracted from ESBTPComptabiliteController::getImpayesAging.
 */
class GetImpayesAgingAction
{
    private const DEFAULT_DEADLINE_DAYS = 30;
    private const STUDENT_PREVIEW_PER_BUCKET = 5;

    public function __construct(
        private readonly CalculerTotalDuAction $calculerTotalDu,
    ) {}

    public function __invoke(ComptabiliteFilters $filters): array
    {
        $allCategories = ESBTPFraisCategory::where('is_active', true)->get();

        $inscriptions = ESBTPInscription::query()
            ->with([
                'etudiant',
                'fraisSubscriptions',
                'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
            ])
            ->when($filters->anneeId, fn ($q) => $q->where('annee_universitaire_id', $filters->anneeId))
            ->when($filters->filiereId, fn ($q) => $q->whereHas('classe', fn ($q2) => $q2->where('filiere_id', $filters->filiereId)))
            ->when($filters->classeId, fn ($q) => $q->where('classe_id', $filters->classeId))
            ->get();

        if ($inscriptions->isEmpty()) {
            return $this->emptyBuckets();
        }

        $allSubscriptions = ESBTPFraisSubscription::query()
            ->where('is_active', true)
            ->whereIn('inscription_id', $inscriptions->pluck('id')->all())
            ->get()
            ->groupBy('inscription_id');

        $allConfigurations = ESBTPFraisConfiguration::query()
            ->where('is_active', true)
            ->whereIn('frais_category_id', $allCategories->pluck('id')->all())
            ->get()
            ->groupBy(fn ($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        $buckets = $this->emptyBuckets();

        foreach ($inscriptions as $inscription) {
            $totalDu = $this->calculerTotalDu->calculerParInscription(
                $inscription,
                $allCategories,
                $allSubscriptions,
                $allConfigurations,
            );
            $totalPaye = (float) $inscription->paiements->where('status', 'validé')->sum('montant');
            $soldeRestant = max(0.0, $totalDu - $totalPaye);

            if ($soldeRestant <= 0) {
                continue;
            }

            $minDeadlineDays = $this->resolveDeadlineDays($inscription, $allSubscriptions, $allCategories);
            $dateEcheance = $inscription->created_at->copy()->addDays($minDeadlineDays);

            if ($dateEcheance->isFuture()) {
                continue;
            }

            $joursRetard = (int) $dateEcheance->diffInDays(now());
            $bucketKey = $this->bucketKeyFor($joursRetard);
            $etudiantData = [
                'id' => $inscription->etudiant->id ?? null,
                'inscription_id' => $inscription->id,
                'nom' => $inscription->etudiant->nom_complet ?? 'N/A',
                'solde' => $soldeRestant,
                'jours' => $joursRetard,
            ];

            $buckets[$bucketKey]['count']++;
            $buckets[$bucketKey]['amount'] += $soldeRestant;
            if (count($buckets[$bucketKey]['students']) < self::STUDENT_PREVIEW_PER_BUCKET) {
                $buckets[$bucketKey]['students'][] = $etudiantData;
            }
        }

        return $buckets;
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

    private function resolveDeadlineDays(
        ESBTPInscription $inscription,
        Collection $allSubscriptions,
        Collection $allCategories,
    ): int {
        $inscriptionSubs = $allSubscriptions->get($inscription->id, collect());
        if ($inscriptionSubs->isEmpty()) {
            return self::DEFAULT_DEADLINE_DAYS;
        }

        $deadlineDays = $inscriptionSubs->map(function ($sub) use ($allCategories) {
            $cat = $allCategories->firstWhere('id', $sub->frais_category_id);
            return $cat ? ($cat->payment_deadline_days ?? self::DEFAULT_DEADLINE_DAYS) : self::DEFAULT_DEADLINE_DAYS;
        })->min();

        return $deadlineDays !== null ? (int) $deadlineDays : self::DEFAULT_DEADLINE_DAYS;
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
