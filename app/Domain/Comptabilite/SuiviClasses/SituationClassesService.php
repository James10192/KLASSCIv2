<?php

namespace App\Domain\Comptabilite\SuiviClasses;

use App\Actions\Comptabilite\GetImpayesAgingAction;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\Frais\MontantsParFrais;
use App\Services\RelanceCalculationService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Lecture seule, par inscription et par année. La classe n'appartient pas à
 * l'année : ce sont les inscriptions qui portent cette information.
 *
 * Réutilise l'échéancier canonique (montants obligatoires/configurés,
 * options effectivement souscrites, exonérations et paiements ventilés).
 * Ne déplace pas les reliquats d'une année à une autre.
 */
final class SituationClassesService
{
    public const PAGE_SIZE = 20;
    public const PDF_MAX_ROWS = 1000;
    public const EXCEL_MAX_ROWS = 50000;

    public function __construct(
        private readonly RelanceCalculationService $calculs,
        private readonly MontantsParFrais $montantsParFrais,
    )
    {
    }

    /** @param array<string, mixed> $filters */
    public function build(array $filters, bool $avecEtudiants = false, bool $pagination = true): array
    {
        $anneeId = (int) ($filters['annee'] ?? 0);
        $classeId = (int) ($filters['classe'] ?? 0);
        $categoryId = (int) ($filters['categorie'] ?? 0);
        $classes = ESBTPClasse::query()
            ->with(['filiere', 'niveau'])
            ->when($classeId, fn ($q) => $q->whereKey($classeId))
            ->when($filters['filiere'] ?? null, fn ($q, $id) => $q->where('filiere_id', $id))
            ->when($filters['niveau'] ?? null, fn ($q, $id) => $q->where('niveau_etude_id', $id))
            ->when($filters['systeme'] ?? null, fn ($q, $s) => $q->where('systeme_academique', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('name', 'like', '%'.addcslashes($s, '%_\\').'%'))
            ->orderBy('name')
            ->get();

        $rows = [];
        foreach ($classes as $classe) {
            $rows[(int) $classe->id] = [
                'classe_id' => (int) $classe->id,
                'classe' => (string) $classe->name,
                'filiere' => (string) ($classe->filiere?->name ?? '—'),
                'niveau' => (string) ($classe->niveau?->name ?? '—'),
                'effectif' => 0,
                'attendu' => 0.0,
                'regle' => 0.0,
                'encaisse' => 0.0,
                'ajustements' => 0.0,
                'restant' => 0.0,
                'echu' => 0.0,
                'etudiants' => [],
                'taux' => null,
            ];
        }

        if ($anneeId > 0 && $rows !== []) {
            $uniques = [];
            // Un lot réduit la mémoire et pré-charge paiements + références.
            ESBTPInscription::query()
                ->with([
                    'etudiant',
                    'paiements' => fn ($q) => $q->where('status', 'validé')->whereNull('deleted_at'),
                ])
                ->where('annee_universitaire_id', $anneeId)
                ->whereIn('classe_id', array_keys($rows))
                ->whereIn('status', GetImpayesAgingAction::ACTIVE_INSCRIPTION_STATUSES)
                ->chunkById(250, function ($lot) use (&$rows, &$uniques, $categoryId, $avecEtudiants) {
                    $ids = $lot->pluck('id')->map(fn ($id) => (int) $id)->all();
                    // Source canonique des allocations et des avoirs, hors reliquats.
                    // Une seule agrégation par lot évite une requête par étudiant.
                    $parFrais = $this->montantsParFrais->netParInscriptionEtFrais($ids);
                    $nets = ESBTPPaiement::query()
                        ->whereIn('inscription_id', $ids)
                        ->valides()
                        ->horsReliquat()
                        ->selectRaw('inscription_id, SUM('.ESBTPPaiement::sqlStudentPaidCase().') AS total_net')
                        ->groupBy('inscription_id')
                        ->pluck('total_net', 'inscription_id');

                    $this->calculs->preloadForInscriptions($lot);
                    foreach ($lot as $inscription) {
                        $id = (int) $inscription->classe_id;
                        $state = $this->calculs->getFinancialState($inscription);
                        $montants = $this->montants(
                            $state,
                            $inscription,
                            $categoryId,
                            $parFrais[(int) $inscription->id] ?? [],
                            (float) ($nets[(int) $inscription->id] ?? 0),
                        );
                        $row = &$rows[$id];

                        $studentId = (int) $inscription->etudiant_id;
                        if ($studentId > 0 && ! isset($uniques[$id][$studentId])) {
                            $uniques[$id][$studentId] = true;
                            $row['effectif']++;
                        }

                        foreach (['attendu', 'regle', 'restant', 'echu'] as $field) {
                            $row[$field] += $montants[$field];
                        }
                        if ($categoryId === 0) {
                            $row['encaisse'] += $montants['encaisse'];
                            $row['ajustements'] += $montants['ajustements'];
                        }
                        if ($avecEtudiants) {
                            $row['etudiants'][] = [
                                'inscription_id' => (int) $inscription->id,
                                'nom' => (string) ($inscription->etudiant?->nom_complet ?? 'Étudiant inconnu'),
                                'matricule' => (string) ($inscription->etudiant?->matricule ?? '—'),
                                ...$montants,
                            ];
                        }
                        unset($row);
                    }
                });
        }

        foreach ($rows as &$row) {
            foreach (['attendu', 'regle', 'encaisse', 'ajustements', 'restant', 'echu'] as $field) {
                $row[$field] = round($row[$field], 2);
            }
            $row['taux'] = $row['attendu'] > 0
                ? round(min(100, 100 * $row['regle'] / $row['attendu']), 1)
                : null;
        }
        unset($row);

        $totaux = [
            'classes' => count($rows), 'effectif' => 0,
            'attendu' => 0.0, 'regle' => 0.0, 'encaisse' => 0.0,
            'ajustements' => 0.0, 'restant' => 0.0, 'echu' => 0.0, 'taux' => null,
        ];
        foreach ($rows as $row) {
            $totaux['effectif'] += $row['effectif'];
            foreach (['attendu', 'regle', 'encaisse', 'ajustements', 'restant', 'echu'] as $k) {
                $totaux[$k] += $row[$k];
            }
        }
        $totaux['taux'] = $totaux['attendu'] > 0
            ? round(min(100, 100 * $totaux['regle'] / $totaux['attendu']), 1)
            : null;

        $liste = array_values($rows);
        $page = max(1, (int) ($filters['page'] ?? 1));
        return [
            'classes' => $pagination
                ? new LengthAwarePaginator(array_slice($liste, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE), count($liste), self::PAGE_SIZE, $page, [
                    'path' => request()->url(), 'query' => request()->except('page'),
                ])
                : collect($liste),
            'totaux' => $totaux,
            'filtered_category' => $categoryId > 0,
        ];
    }

    /**
     * Les lignes d'échéancier font foi pour la dette ; leurs « paid_amount »
     * ne font PAS foi pour le montant net, car le moteur historique de tranches
     * peut y appliquer positivement des avoirs et des paiements de reliquat.
     * La ventilation par frais est donc relue depuis MontantsParFrais, puis
     * appliquée chronologiquement aux échéances.
     *
     * @param array<int, float> $payeParFrais
     * @return array{attendu:float,regle:float,restant:float,echu:float,encaisse:float,ajustements:float}
     */
    private function montants(
        array $state,
        ESBTPInscription $inscription,
        int $categoryId,
        array $payeParFrais,
        float $netGlobal
    ): array {
        $lignes = array_values(array_filter(
            $state['due_lines'] ?? [],
            fn (array $l) => $categoryId === 0 || (int) ($l['category_id'] ?? 0) === $categoryId
        ));
        usort($lignes, fn (array $a, array $b) =>
            (string) ($a['due_date'] ?? '') <=> (string) ($b['due_date'] ?? '')
        );

        $pools = [];
        foreach ($payeParFrais as $fraisId => $net) {
            $pools[(string) $fraisId] = max(0.0, (float) $net);
        }
        $netNonVentile = $categoryId > 0
            ? 0.0
            : max(0.0, $netGlobal - array_sum($pools));

        // 1. Imputer les montants nets par frais, sans aucun effet des reliquats.
        foreach ($lignes as &$ligne) {
            $key = (string) ($ligne['category_id'] ?? 0);
            $montant = max(0.0, (float) ($ligne['amount'] ?? 0));
            $verse = min($montant, $pools[$key] ?? 0.0);
            $pools[$key] = max(0.0, ($pools[$key] ?? 0.0) - $verse);
            $ligne['report_paid'] = $verse;
            $ligne['report_remaining'] = $montant - $verse;
        }
        unset($ligne);

        // 2. Les paiements globalement non affectés peuvent couvrir les frais
        //    du même dossier, par ordre d'échéance. Jamais dans un filtre de frais.
        if ($netNonVentile > 0) {
            foreach ($lignes as &$ligne) {
                $verse = min($netNonVentile, $ligne['report_remaining']);
                $ligne['report_paid'] += $verse;
                $ligne['report_remaining'] -= $verse;
                $netNonVentile -= $verse;
                if ($netNonVentile <= 0.001) {
                    break;
                }
            }
            unset($ligne);
        }

        $attendu = 0.0;
        $regle = 0.0;
        $restant = 0.0;
        $echu = 0.0;
        foreach ($lignes as $ligne) {
            $attendu += max(0.0, (float) ($ligne['amount'] ?? 0));
            $regle += $ligne['report_paid'];
            $restant += $ligne['report_remaining'];
            if (! empty($ligne['due_date']) && Carbon::parse($ligne['due_date'])
                ->addDays((int) ($ligne['grace_days'] ?? 0))->startOfDay()->lte(today())) {
                $echu += $ligne['report_remaining'];
            }
        }

        // Le montant de caisse est indépendant de l'imputation sur une tranche.
        // Une remise/avoir interne n'enlève de la caisse que s'il s'agit d'un refund.
        $encaisse = $categoryId > 0 ? 0.0 : ESBTPPaiement::netCashFrom(
            $inscription->paiements->filter(fn ($p) => $p->type_paiement !== 'reliquat')
        );

        return [
            'attendu' => round($attendu, 2),
            'regle' => round($regle, 2),
            'restant' => round($restant, 2),
            'echu' => round($echu, 2),
            'encaisse' => round($encaisse, 2),
            'ajustements' => round($regle - $encaisse, 2),
        ];
    }
}
