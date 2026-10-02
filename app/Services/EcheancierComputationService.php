<?php

namespace App\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EcheancierComputationService
{
    /**
     * Ce qui ne depend que des donnees de reference, retenu le temps d'un lot.
     *
     * La liste des relances calcule l'echeancier de TOUTE l'annee a chaque
     * arrivee sur la page. Les categories, configurations et regles y sont les
     * memes pour chaque eleve : seule l'inscription change. Refaire pour chaque
     * eleve la meme indexation des categories, le meme choix de configuration
     * (lecture de dates castees, tri) et le meme montant par statut coutait
     * l'essentiel du calcul. On le retient donc, attache a la collection de
     * reference elle-meme (WeakMap) : un nouveau chargement des references
     * repart d'une memoire vide, et le choix de configuration porte le jour,
     * comme le fait deja EcheancierResolverService pour les regles.
     *
     * @var \WeakMap<Collection, \stdClass>
     */
    private \WeakMap $memoire;

    /** @var array<string, list<array<string, mixed>>> tranches projetees, sans cle d'item */
    private array $projections = [];

    /** @var array<string, Carbon> date d'echeance + jours de grace, en debut de jour */
    private array $echeancesAvecGrace = [];

    public function __construct(
        private readonly EcheancierResolverService $resolver,
        private readonly EcheancierProjectionService $projection,
        private readonly EcheancierPaymentAllocationService $allocation,
    ) {
        $this->memoire = new \WeakMap();
    }

    /**
     * @param Collection<int, ESBTPFraisCategory> $categories
     * @param Collection<string|int, mixed> $configurations
     * @param Collection<int, mixed> $subscriptions
     * @return array{items: array<int, array<string, mixed>>, due_lines: array<int, array<string, mixed>>}
     */
    public function buildScheduleForInscription(
        ESBTPInscription $inscription,
        Collection $categories,
        Collection $configurations,
        Collection $subscriptions
    ): array {
        $items = [];
        $dueLines = [];
        $itemCounter = 0;

        // Les attributs d'un modele se relisent a chaque acces (casts) : sur un
        // lot de toute l'annee, ces lectures repetees pesaient plus que le
        // calcul. Categories et configurations sont lues une fois par lot,
        // l'inscription une fois par appel.
        $fiches = $this->fichesDesCategories($categories);
        $aujourdhui = now()->toDateString();
        $inscriptionId = $inscription->id;
        $filiereId = $inscription->filiere_id;
        $niveauId = $inscription->niveau_id;
        $statutBrut = $inscription->affectation_status;
        $statut = $statutBrut ?? ESBTPInscription::DEFAULT_AFFECTATION_STATUS;
        $dateInscription = null;

        // Premiere souscription par categorie, comme firstWhere('frais_category_id', …).
        $souscriptionParCategorie = [];
        foreach ($subscriptions as $souscription) {
            $souscriptionParCategorie[(string) $souscription->frais_category_id] ??= $souscription;
        }

        foreach ($fiches as $category) {
            if (!$category->obligatoire) {
                continue;
            }

            $subscription = $souscriptionParCategorie[(string) $category->id] ?? null;
            if ($subscription && $subscription->satisfied_in_kind) {
                continue;
            }
            $fiche = $this->ficheDeConfiguration($configurations, $category->id, $filiereId, $niveauId, $aujourdhui);
            $configuration = $fiche?->modele;

            if ($subscription) {
                $amount = (float) $subscription->amount;
                $sourceType = 'subscription_override';
                $sourceId = (int) $subscription->id;
            } else {
                $amount = $fiche
                    ? (float) ($fiche->montants[$statut] ??= $configuration->getMontantByStatus($statut))
                    : (float) ($category->montantParDefaut ?? 0);
                $sourceType = 'configuration';
                $sourceId = $fiche ? (int) $fiche->id : null;
            }

            $amount = round(max(0, $amount), 2);

            $rule = $this->resolver->resolveForConfiguration($configuration, $statutBrut);
            $ruleLines = $rule ? $rule->lines : collect();
            $fallbackDays = (int) ($fiche?->delai ?? $category->delai ?? 30);

            $itemCounter++;
            $itemKey = 'inscription:' . $inscriptionId . ':mandatory:' . $category->id . ':' . $itemCounter;

            // amount=0 (gratuit pour ce statut) → on émet quand même un item dans le snapshot
            // pour que la couverture analytics voie la catégorie comme "configurée et gratuite"
            // au lieu de "manquante / fallback". Mais on ne projette aucune tranche
            // (rien à payer = rien à projeter — projectDueLines retourne déjà [] pour amount=0).
            $projectedLines = $amount > 0
                ? $this->projeter(
                    $amount,
                    $rule,
                    $ruleLines,
                    // Une seule lecture par inscription : la projection copie la date, ne la modifie pas.
                    $dateInscription ??= ($inscription->date_inscription
                        ? Carbon::parse($inscription->date_inscription)
                        : Carbon::parse($inscription->created_at ?? now())),
                    $fallbackDays,
                    $itemKey,
                    $category->id,
                    $category->nom
                )
                : [];

            $items[] = [
                'item_key' => $itemKey,
                'label' => $category->nom,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'category_id' => $category->id,
                'category_name' => $category->nom,
                'amount' => $amount,
                'rule_id' => $rule?->id,
                'is_free' => $amount === 0.0,
            ];

            $dueLines = array_merge($dueLines, $projectedLines);
        }

        $optionalSubscriptions = $subscriptions->filter(function ($subscription) use ($fiches) {
            $category = $fiches[$subscription->frais_category_id] ?? null;
            return $category && !$category->obligatoire && (bool) $subscription->is_active && ! $subscription->satisfied_in_kind;
        })->values();

        foreach ($optionalSubscriptions as $subscription) {
            $category = $fiches[$subscription->frais_category_id] ?? null;
            if (!$category) {
                continue;
            }

            $amount = round(max(0, (float) $subscription->amount), 2);

            $option = $subscription->relationLoaded('selectedOption')
                ? $subscription->selectedOption
                : $subscription->selectedOption()->with('assignments')->first();

            $assignment = $this->resolver->findBestAssignmentForInscription($option, $inscription);
            $rule = $this->resolver->resolveForOptionAssignment($assignment, $statutBrut);
            $ruleLines = $rule ? $rule->lines : collect();
            $fallbackDays = (int) ($category->delai ?? 30);

            $itemCounter++;
            $itemKey = 'inscription:' . $inscriptionId . ':optional:' . $subscription->id . ':' . $itemCounter;

            // Cf bloc mandatory ci-dessus : amount=0 = subscription gratuite, on émet
            // l'item dans le snapshot mais sans tranche à projeter.
            $projectedLines = $amount > 0
                ? $this->projeter(
                    $amount,
                    $rule,
                    $ruleLines,
                    $subscription->subscribed_at
                        ? Carbon::parse($subscription->subscribed_at)
                        : Carbon::parse($inscription->created_at ?? now()),
                    $fallbackDays,
                    $itemKey,
                    $category->id,
                    $category->nom
                )
                : [];

            $items[] = [
                'item_key' => $itemKey,
                'label' => $category->nom . ($option ? ' - ' . $option->name : ''),
                'source_type' => 'subscription',
                'source_id' => (int) $subscription->id,
                'category_id' => $category->id,
                'category_name' => $category->nom,
                'amount' => $amount,
                'rule_id' => $rule?->id,
                'option_assignment_id' => $assignment?->id,
                'is_free' => $amount === 0.0,
            ];

            $dueLines = array_merge($dueLines, $projectedLines);
        }

        return [
            'items' => $items,
            'due_lines' => $dueLines,
        ];
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, due_lines: array<int, array<string, mixed>>} $schedule
     * @return array<string, mixed>
     */
    public function computeOverdueForSchedule(array $schedule, Collection $validatedPayments, ?Carbon $asOf = null): array
    {
        $asOf = $asOf ?: now();
        $dueLines = $schedule['due_lines'] ?? [];

        $allocatedLines = $this->allocation->allocate($dueLines, $validatedPayments);

        $totalDue = round(collect($allocatedLines)->sum(fn ($line) => (float) ($line['amount'] ?? 0)), 2);
        $totalPaidOnSchedule = round(collect($allocatedLines)->sum(fn ($line) => (float) ($line['paid_amount'] ?? 0)), 2);
        $totalPaidValidated = round(\App\Models\ESBTPPaiement::netStudentPaidFrom($validatedPayments), 2);
        $remainingTotal = round(max(0, $totalDue - $totalPaidOnSchedule), 2);
        $creditAmount = round(max(0, $totalPaidOnSchedule - $totalDue), 2);

        $expectedDueToDate = 0.0;
        $paidDueToDate = 0.0;
        $oldestOverdueDate = null;
        $debutDuJour = $asOf->copy()->startOfDay();

        foreach ($allocatedLines as $line) {
            $dueWithGrace = $this->echeanceAvecGrace((string) $line['due_date'], (int) ($line['grace_days'] ?? 0));

            $amount = (float) ($line['amount'] ?? 0);
            $paid = (float) ($line['paid_amount'] ?? 0);
            $remaining = (float) ($line['remaining_amount'] ?? 0);

            if ($dueWithGrace->lte($debutDuJour)) {
                $expectedDueToDate += $amount;
                $paidDueToDate += min($amount, $paid);

                if ($remaining > 0) {
                    if ($oldestOverdueDate === null || $dueWithGrace->lt($oldestOverdueDate)) {
                        $oldestOverdueDate = $dueWithGrace->copy();
                    }
                }
            }
        }

        $expectedDueToDate = round($expectedDueToDate, 2);
        $paidDueToDate = round($paidDueToDate, 2);
        $overdueAmount = round(max(0, $expectedDueToDate - $paidDueToDate), 2);
        $overdueDays = $overdueAmount > 0 && $oldestOverdueDate
            ? max(0, $oldestOverdueDate->diffInDays($asOf, false))
            : 0;

        $categoriesSummary = [];
        foreach ($allocatedLines as $line) {
            $categoryId = $line['category_id'] ?? 'global';
            $bucketKey = (string) $categoryId;

            if (!isset($categoriesSummary[$bucketKey])) {
                $categoriesSummary[$bucketKey] = [
                    'category_id' => $categoryId,
                    'category_name' => $line['category_name'] ?? 'Global',
                    'total_due' => 0.0,
                    'total_paid' => 0.0,
                    'total_remaining' => 0.0,
                ];
            }

            $categoriesSummary[$bucketKey]['total_due'] += (float) ($line['amount'] ?? 0);
            $categoriesSummary[$bucketKey]['total_paid'] += (float) ($line['paid_amount'] ?? 0);
            $categoriesSummary[$bucketKey]['total_remaining'] += (float) ($line['remaining_amount'] ?? 0);
        }

        $categoriesSummary = array_values(array_map(function ($category) {
            $category['total_due'] = round($category['total_due'], 2);
            $category['total_paid'] = round($category['total_paid'], 2);
            $category['total_remaining'] = round($category['total_remaining'], 2);
            $category['coverage_rate'] = $category['total_due'] > 0
                ? round(min(100, ($category['total_paid'] / $category['total_due']) * 100), 2)
                : 100.0;

            return $category;
        }, $categoriesSummary));

        return [
            'items' => $schedule['items'] ?? [],
            'due_lines' => $allocatedLines,
            'total_due' => $totalDue,
            'total_paid' => $totalPaidOnSchedule,
            'total_paid_validated' => $totalPaidValidated,
            'remaining_total' => $remainingTotal,
            'credit_amount' => $creditAmount,
            'expected_due_to_date' => $expectedDueToDate,
            'paid_due_to_date' => $paidDueToDate,
            'overdue_amount' => $overdueAmount,
            'overdue_days' => $overdueDays,
            'is_overdue' => $overdueAmount > 0,
            'overdue_since' => $oldestOverdueDate?->toDateString(),
            'categories' => $categoriesSummary,
            'as_of' => $asOf->toDateString(),
        ];
    }

    /**
     * Projection des tranches, retenue par ce dont elle depend vraiment : la
     * regle (et la generation des regles, que toute ecriture sur une regle ou
     * une ligne fait avancer), le montant, le JOUR de reference (la projection
     * ne lit que la date : debut de jour, annee), le delai de repli et la
     * categorie. Seules les cles d'item different d'un eleve a l'autre : la
     * projection est faite avec une cle vide, puis la cle de l'eleve est
     * apposee (line_key = cle d'item . ':line:N', comme projectDueLines).
     *
     * @return list<array<string, mixed>>
     */
    private function projeter(
        float $amount,
        ?ESBTPEcheancierRule $rule,
        Collection $ruleLines,
        Carbon $reference,
        int $fallbackDays,
        string $itemKey,
        int $categoryId,
        string $categoryName
    ): array {
        $cle = ($rule?->id ?? 0) . '|' . EcheancierResolverService::generationDesRegles() . '|' . $amount
            . '|' . $reference->toDateString() . '|' . $fallbackDays . '|' . $categoryId . '|' . $categoryName;

        $modele = $this->projections[$cle]
            ??= $this->projection->projectDueLines($amount, $ruleLines, $reference, $fallbackDays, '', $categoryId, $categoryName);

        return array_map(
            fn (array $ligne) => ['line_key' => $itemKey . $ligne['line_key'], 'item_key' => $itemKey] + $ligne,
            $modele
        );
    }

    private function memoirePour(Collection $reference): \stdClass
    {
        return $this->memoire[$reference] ??= (object) ['fiches' => null, 'configurations' => []];
    }

    /**
     * La configuration retenue pour (categorie, filiere, niveau) ce jour-la,
     * et ce qu'on en lit : choisie et lue une fois par lot.
     */
    private function ficheDeConfiguration(Collection $configurations, int $categoryId, $filiereId, $niveauId, string $aujourdhui): ?\stdClass
    {
        $memoire = $this->memoirePour($configurations);
        $cle = $categoryId . '_' . $filiereId . '_' . $niveauId . '|' . $aujourdhui;
        if (! array_key_exists($cle, $memoire->configurations)) {
            $configuration = $this->resolveConfiguration($configurations, $categoryId, $filiereId, $niveauId);
            $memoire->configurations[$cle] = $configuration
                ? (object) ['modele' => $configuration, 'id' => $configuration->id, 'delai' => $configuration->payment_deadline_days, 'montants' => []]
                : null;
        }

        return $memoire->configurations[$cle];
    }

    /**
     * Les categories du lot, lues une fois, dans leur ordre, indexees par id.
     *
     * @return array<int, object{id: int, obligatoire: bool, nom: string, delai: mixed, montantParDefaut: mixed}>
     */
    private function fichesDesCategories(Collection $categories): array
    {
        $memoire = $this->memoirePour($categories);
        if ($memoire->fiches === null) {
            $memoire->fiches = [];
            foreach ($categories as $category) {
                $memoire->fiches[$category->id] ??= (object) [
                    'id' => (int) $category->id,
                    'obligatoire' => (bool) $category->is_mandatory,
                    'nom' => (string) $category->name,
                    'delai' => $category->payment_deadline_days,
                    'montantParDefaut' => $category->default_amount,
                ];
            }
        }

        return $memoire->fiches;
    }

    /**
     * Date d'echeance plus grace, en debut de jour. Ne depend que de ses deux
     * arguments : retenue sans limite de jour. L'objet rendu est partage, il ne
     * doit jamais etre modifie (copy() avant toute arithmetique).
     */
    private function echeanceAvecGrace(string $dueDate, int $graceDays): Carbon
    {
        return $this->echeancesAvecGrace[$dueDate . '|' . $graceDays]
            ??= Carbon::parse($dueDate)->addDays($graceDays)->startOfDay();
    }

    private function resolveConfiguration(Collection $configurations, int $categoryId, int $filiereId, int $niveauId): ?ESBTPFraisConfiguration
    {
        $key = $categoryId . '_' . $filiereId . '_' . $niveauId;

        if ($configurations->has($key)) {
            $group = $configurations->get($key);
            if ($group instanceof Collection) {
                return $this->pickValidConfiguration($group);
            }
            if ($group instanceof ESBTPFraisConfiguration) {
                return $group;
            }
        }

        // Fallback flat-collection scan : ne s'applique que si le caller passe
        // une collection NON groupée. RelanceCalculationService et
        // EcheancierSnapshotService passent toujours du groupBy, donc on est
        // safe — mais on défend contre un appelant futur.
        $matches = $configurations->filter(function ($configuration) use ($categoryId, $filiereId, $niveauId) {
            if (! $configuration instanceof ESBTPFraisConfiguration) {
                return false;
            }

            return (int) $configuration->frais_category_id === $categoryId
                && (int) $configuration->filiere_id === $filiereId
                && (int) $configuration->niveau_id === $niveauId;
        });

        if ($matches->isEmpty()) {
            return null;
        }

        return $this->pickValidConfiguration($matches);
    }

    private function pickValidConfiguration(Collection $configurations): ?ESBTPFraisConfiguration
    {
        $today = now()->toDateString();

        $valid = $configurations->filter(function ($configuration) use ($today) {
            if (!(bool) $configuration->is_active) {
                return false;
            }

            $effectiveOk = empty($configuration->effective_date) || (string) $configuration->effective_date <= $today;
            $expiryOk = empty($configuration->expiry_date) || (string) $configuration->expiry_date >= $today;

            return $effectiveOk && $expiryOk;
        });

        if ($valid->isEmpty()) {
            return $configurations->first();
        }

        return $valid->sortByDesc('effective_date')->first();
    }
}
