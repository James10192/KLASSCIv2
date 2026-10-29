<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Models\ESBTPSeanceCours;
use App\Services\TeacherHoursService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ce qui justifie chaque chiffre de l'activité d'une personne : les séances
 * non émargées, les évaluations incomplètes, les paiements restés en attente.
 * Un chiffre qu'on ne peut pas ouvrir ne se défend pas devant la personne.
 */
final class PreuvesDActivite
{
    private const LIMITE = 12;

    public function __construct(private readonly ActiviteDuPersonnel $activite) {}

    /** @return array<string, mixed> */
    public function pour(int $userId, FenetreDActivite $fenetre): array
    {
        return [
            'seances_non_emargees' => $this->seancesNonEmargees($userId, $fenetre),
            'evaluations_incompletes' => $this->evaluationsIncompletes($userId, $fenetre),
            'paiements_en_attente' => $this->paiementsEnAttente($userId),
        ];
    }

    private function seancesNonEmargees(int $userId, FenetreDActivite $fenetre): Collection
    {
        $realises = TeacherHoursService::STATUTS_REALISES;

        return DB::table('esbtp_seance_cours as s')
            ->join('esbtp_teachers as t', 't.id', '=', 's.teacher_id')
            ->leftJoin('esbtp_classes as c', 'c.id', '=', 's.classe_id')
            ->leftJoin('esbtp_matieres as m', 'm.id', '=', 's.matiere_id')
            ->where('t.user_id', $userId)
            ->whereNull('s.deleted_at')
            ->whereNotIn('s.type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereBetween('s.date_seance', [$fenetre->du(), $fenetre->au()])
            ->whereNotExists(fn ($q) => $q->from('esbtp_teacher_attendances as ta')
                ->whereColumn('ta.course_id', 's.id')->where('ta.type', 'start')
                ->whereIn(DB::raw('LOWER(ta.status)'), $realises))
            ->orderByDesc('s.date_seance')
            ->limit(self::LIMITE)
            ->get(['s.id', 's.date_seance', 's.heure_debut', 's.heure_fin', 'c.name as classe', 'm.name as matiere']);
    }

    private function evaluationsIncompletes(int $userId, FenetreDActivite $fenetre): Collection
    {
        return DB::query()->fromSub($this->activite->evaluationsPassees($fenetre, $userId), 'x')
            ->whereColumn('x.recues', '<', 'x.attendues')
            ->leftJoin('esbtp_classes as c', 'c.id', '=', 'x.classe_id')
            ->leftJoin('esbtp_matieres as m', 'm.id', '=', 'x.matiere_id')
            ->orderBy('x.date_evaluation')
            ->limit(self::LIMITE)
            ->get(['x.id', 'x.titre', 'x.date_evaluation', 'x.recues', 'x.attendues', 'x.classe_id', 'c.name as classe', 'm.name as matiere']);
    }

    private function paiementsEnAttente(int $userId): Collection
    {
        return $this->activite->requeteEnAttente()
            ->where('created_by', $userId)
            ->orderBy('created_at')
            ->limit(self::LIMITE)
            ->get(['id', 'montant', 'created_at', 'numero_recu', 'etudiant_id']);
    }
}
