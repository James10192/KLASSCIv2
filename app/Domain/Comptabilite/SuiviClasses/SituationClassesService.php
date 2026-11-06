<?php

namespace App\Domain\Comptabilite\SuiviClasses;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Services\Frais\MontantsParFrais;
use App\Services\RelanceCalculationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

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
        private readonly SituationClasseMontants $calculateur,
    )
    {
    }

    /** Durée du cache des agrégats : changer de page ne recalcule pas tout l'établissement. */
    private const CACHE_SECONDES = 90;

    /** @param array<string, mixed> $filters */
    public function build(array $filters, bool $avecEtudiants = false, bool $pagination = true): array
    {
        $cle = $filters;
        // La page et le tri s'appliquent après le calcul : ils ne changent pas la clé du cache.
        unset($cle['page'], $cle['tri']);
        ksort($cle);
        // Le détail par étudiant n'est demandé que pour une classe : pas de cache.
        $calcul = $avecEtudiants
            ? $this->calculer($filters, true)
            : Cache::remember('suivi_classes:'.sha1(json_encode($cle)), self::CACHE_SECONDES,
                fn () => $this->calculer($filters, false));

        $liste = self::trier($calcul['liste'], (string) ($filters['tri'] ?? self::TRI_DEFAUT));
        $page = max(1, (int) ($filters['page'] ?? 1));
        return [
            'classes' => $pagination
                ? new LengthAwarePaginator(array_slice($liste, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE), count($liste), self::PAGE_SIZE, $page, [
                    'path' => request()->url(), 'query' => request()->except('page'),
                ])
                : collect($liste),
            'totaux' => $calcul['totaux'],
            'a_surveiller' => self::aSurveiller($calcul['liste']),
            'filtered_category' => (int) ($filters['categorie'] ?? 0) > 0,
        ];
    }

    /** Tris proposés à l'écran : clé => libellé. */
    public const TRIS = [
        'reste' => 'Reste à payer, du plus élevé',
        'echu' => 'Échu impayé, du plus élevé',
        'taux' => 'Recouvrement, du plus faible',
        'classe' => 'Nom de classe',
    ];

    public const TRI_DEFAUT = 'classe';

    /**
     * @param array<int, array<string, mixed>> $liste
     * @return array<int, array<string, mixed>>
     */
    public static function trier(array $liste, string $tri): array
    {
        $cle = match ($tri) {
            'reste' => fn (array $a, array $b) => $b['restant'] <=> $a['restant'],
            'echu' => fn (array $a, array $b) => $b['echu'] <=> $a['echu'],
            // Une classe sans montant attendu n'a pas de taux : elle passe en dernier.
            'taux' => fn (array $a, array $b) => ($a['taux'] ?? INF) <=> ($b['taux'] ?? INF),
            default => fn (array $a, array $b) => strnatcasecmp($a['classe'], $b['classe']),
        };
        usort($liste, $cle);

        return $liste;
    }

    /**
     * Les trois classes qui portent le plus d'échu impayé : ce que le comptable
     * doit regarder en premier.
     *
     * @param array<int, array<string, mixed>> $liste
     * @return array<int, array<string, mixed>>
     */
    public static function aSurveiller(array $liste): array
    {
        $liste = array_values(array_filter($liste, fn (array $l) => $l['echu'] > 0));
        usort($liste, fn (array $a, array $b) => $b['echu'] <=> $a['echu']);

        return array_slice($liste, 0, 3);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{liste: array<int, array<string, mixed>>, totaux: array<string, mixed>}
     */
    private function calculer(array $filters, bool $avecEtudiants): array
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
                ->whereIn('status', ESBTPInscription::STATUTS_ACTIFS_COMPTABLES)
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
                        $montants = $this->calculateur->calculer(
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
                                'etudiant_id' => (int) ($inscription->etudiant_id ?? 0),
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

        return ['liste' => array_values($rows), 'totaux' => $totaux];
    }

}
