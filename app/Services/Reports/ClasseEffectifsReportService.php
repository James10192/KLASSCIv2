<?php

namespace App\Services\Reports;

use App\Domain\Students\StudentCountService;
use App\Models\ESBTPClasse;
use Illuminate\Pagination\LengthAwarePaginator;

/** Effectifs par classe : l'année appartient aux inscriptions, jamais aux classes. */
class ClasseEffectifsReportService
{
    public const PER_PAGE = 20;
    public const PDF_MAX_ROWS = 1000;

    public function __construct(private readonly StudentCountService $students)
    {
    }

    /** @return array<string, mixed> */
    public function build(array $filters, bool $paginate = true): array
    {
        $yearId = $filters['annee_universitaire_id'] ?? null;
        $page = max(1, (int) ($filters['page'] ?? 1));
        if (!$yearId) {
            return $this->empty($filters, $page, $paginate);
        }

        // Le service métier applique année, active, etudiant_cree et SoftDeletes.
        $registrations = $this->students->requeteInscrits((int) $yearId)
            ->whereNotNull('esbtp_inscriptions.etudiant_id')
            ->whereNotNull('esbtp_inscriptions.classe_id')
            ->selectRaw('esbtp_inscriptions.classe_id, COUNT(DISTINCT esbtp_inscriptions.etudiant_id) AS effectif')
            ->groupBy('esbtp_inscriptions.classe_id');

        $query = ESBTPClasse::query()->setEagerLoads([])
            ->leftJoinSub($registrations->toBase(), 'inscrits', 'inscrits.classe_id', '=', 'esbtp_classes.id')
            ->leftJoin('esbtp_filieres AS fil', function ($join) {
                $join->on('fil.id', '=', 'esbtp_classes.filiere_id')->whereNull('fil.deleted_at');
            })
            ->leftJoin('esbtp_niveau_etudes AS niv', function ($join) {
                $join->on('niv.id', '=', 'esbtp_classes.niveau_etude_id')->whereNull('niv.deleted_at');
            })
            ->where(function ($builder) {
                // Garder les classes historiques inactives si elles ont des inscrits.
                $builder->where('esbtp_classes.is_active', true)
                    ->orWhereRaw('COALESCE(inscrits.effectif, 0) > 0');
            });

        if ($filters['filiere_id'] ?? null) {
            $query->where('esbtp_classes.filiere_id', $filters['filiere_id']);
        }
        if ($filters['niveau_id'] ?? null) {
            $query->where('esbtp_classes.niveau_etude_id', $filters['niveau_id']);
        }

        $query->select([
            'esbtp_classes.id', 'esbtp_classes.name', 'esbtp_classes.code',
            'fil.name AS filiere', 'niv.name AS niveau',
        ])->selectRaw('COALESCE(inscrits.effectif, 0) AS effectif');

        // Cet indicateur distingue des classes existantes mais sous le seuil
        // d'une véritable absence de classes pour les filtres structurels.
        $classesBeforeThreshold = (clone $query)->count('esbtp_classes.id');
        $query->whereRaw('COALESCE(inscrits.effectif, 0) >= ?', [(int) $filters['min_inscrits']]);

        if (($filters['sort'] ?? '') === 'nom_asc') {
            $query->orderBy('esbtp_classes.name')->orderBy('esbtp_classes.id');
        } else {
            $query->orderByDesc('effectif')->orderBy('esbtp_classes.name')->orderBy('esbtp_classes.id');
        }

        // Quelques centaines de classes au plus sont matérialisées, jamais les
        // dossiers individuels. Le plafond s'applique AVANT la pagination.
        $eligibleCount = (clone $query)->reorder()->count('esbtp_classes.id');
        $limit = $filters['nombre_classes'] === 'all' ? null : (int) $filters['nombre_classes'];
        if ($limit !== null) {
            $query->limit($limit);
        }
        $rows = $query->get()->map(function ($row) {
            $row->effectif = (int) $row->effectif;
            return $row;
        });

        $ids = $rows->pluck('id')->all();
        $uniqueStudents = $ids === [] ? 0 : $this->students->requeteInscrits((int) $yearId)
            ->whereIn('esbtp_inscriptions.classe_id', $ids)
            ->whereNotNull('esbtp_inscriptions.etudiant_id')
            ->distinct()->count('esbtp_inscriptions.etudiant_id');

        $total = $rows->count();
        return [
            'rows' => $paginate
                ? new LengthAwarePaginator($rows->forPage($page, self::PER_PAGE)->values(), $total, self::PER_PAGE, $page, [
                    // Un lien de pagination reste une vraie page HTML sans JS.
                    'path' => route('esbtp.rapports.effectifs.index'), 'query' => request()->except('page'),
                ])
                : $rows,
            'eligible_count' => $eligibleCount,
            'retained_count' => $total,
            'unique_students' => (int) $uniqueStudents,
            'sum_class_enrollments' => (int) $rows->sum('effectif'),
            'has_classes_below_threshold' => $total === 0 && $classesBeforeThreshold > 0 && (int) $filters['min_inscrits'] > 0,
            'filters' => $filters,
        ];
    }

    private function empty(array $filters, int $page, bool $paginate): array
    {
        $rows = collect();
        return [
            'rows' => $paginate ? new LengthAwarePaginator($rows, 0, self::PER_PAGE, $page) : $rows,
            'eligible_count' => 0, 'retained_count' => 0, 'unique_students' => 0,
            'sum_class_enrollments' => 0, 'has_classes_below_threshold' => false,
            'filters' => $filters,
        ];
    }
}
