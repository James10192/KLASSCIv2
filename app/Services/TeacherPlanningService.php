<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use Illuminate\Support\Facades\DB;

/**
 * Calculs métier liés au planning d'un enseignant.
 *
 * Service stateless : chaque méthode reçoit toutes ses dépendances en arguments.
 * Extrait depuis ESBTPEnseignantController pour réduire la complexité du controller
 * et permettre la réutilisation (commands CLI, jobs, autres controllers).
 */
class TeacherPlanningService
{
    /**
     * Heures couvertes par la grille de disponibilité (8h → 18h).
     *
     * @var array<int, int>
     */
    private const AVAILABILITY_HOURS_RANGE = [8, 18];

    /**
     * Jours de la semaine (clés ISO anglais), alignés sur day_of_week 0..6 = Lundi..Dimanche.
     *
     * @var array<int, string>
     */
    private const AVAILABILITY_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    private const AVAILABILITY_DAY_LABELS = [
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',
        'sunday' => 'Dimanche',
    ];

    /**
     * Calcule le planning détaillé d'un enseignant pour une période donnée :
     * classes encadrées, matières, heures planifiées vs réalisées, statistiques globales.
     *
     * @param ESBTPTeacher $enseignant
     * @param ESBTPAnneeUniversitaire|null $anneeCourante  Si null, retourne un planning vide.
     * @param string $periode  'annee' | 'semestre1' | 'semestre2'
     * @return array{classes: \Illuminate\Support\Collection, stats: array<string, int|float>}
     */
    public function getPlanning(
        ESBTPTeacher $enseignant,
        ?ESBTPAnneeUniversitaire $anneeCourante,
        string $periode = 'annee',
    ): array {
        if (!$anneeCourante) {
            return [
                'classes' => collect(),
                'stats' => [
                    'classes' => 0,
                    'heures_planifiees' => 0,
                    'heures_realisees' => 0,
                    'nb_seances' => 0,
                    'taux_realisation' => 0,
                ],
            ];
        }

        $seancesRealisees = $this->fetchSeancesRealisees($enseignant, $anneeCourante, $periode);
        $classIds = $seancesRealisees->pluck('classe_id')->filter()->unique();

        $classes = ESBTPClasse::with(['filiere', 'niveau'])
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        $planifications = $this->fetchPlanifications($classes, $anneeCourante, $periode);
        $planificationsByCombo = $planifications->groupBy(
            fn ($planification) => $planification->filiere_id . '_' . $planification->niveau_etude_id,
        );

        $matiereIds = $planifications
            ->pluck('matiere_id')
            ->merge($seancesRealisees->pluck('matiere_id'))
            ->filter()
            ->unique();
        $matieres = ESBTPMatiere::whereIn('id', $matiereIds)
            ->get()
            ->keyBy('id');

        $classesData = $classes
            ->values()
            ->map(fn ($classe) => $this->buildClasseData(
                $classe,
                $seancesRealisees,
                $planificationsByCombo,
                $matieres,
            ))
            ->values();

        return [
            'classes' => $classesData,
            'stats' => $this->aggregateGlobalStats($classesData),
        ];
    }

    /**
     * Construit la matrice de disponibilité d'un enseignant pour la grille jours × heures.
     *
     * Fusionne les anciennes méthodes `prepareAvailabilityData` et `buildAvailabilityViewData`
     * du controller (la première était un sous-ensemble de la seconde).
     *
     * @param ESBTPTeacher $enseignant  La relation `availabilities` est chargée si absente.
     * @return array{
     *     enseignant: ESBTPTeacher,
     *     availability: array<string, array<int, string>>,
     *     hours: array<int, int>,
     *     days: array<int, string>,
     *     joursNoms: array<string, string>,
     *     stats: array{available: int, preferred: int, unavailable: int},
     * }
     */
    public function getAvailabilityMatrix(ESBTPTeacher $enseignant): array
    {
        $enseignant->loadMissing('availabilities');

        $hours = range(self::AVAILABILITY_HOURS_RANGE[0], self::AVAILABILITY_HOURS_RANGE[1]);
        $days = self::AVAILABILITY_DAYS;

        // Initialisation : tous les créneaux indisponibles par défaut.
        $availability = [];
        foreach ($days as $day) {
            $availability[$day] = array_fill(0, count($hours), 'unavailable');
        }

        // Remplir avec les vraies données — résolution heure par heure.
        foreach ($enseignant->availabilities as $avail) {
            $dayName = $days[$avail->day_of_week] ?? null;
            if (!$dayName) {
                continue;
            }

            [$startHour, $endHour] = $this->parseAvailabilityHours($avail);

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                $hourIndex = $hour - self::AVAILABILITY_HOURS_RANGE[0];
                if ($hourIndex >= 0 && $hourIndex < count($hours)) {
                    $availability[$dayName][$hourIndex] = $avail->availability_type;
                }
            }
        }

        // Statistiques globales (utilisées par les vues d'édition pour afficher les compteurs).
        $stats = ['available' => 0, 'preferred' => 0, 'unavailable' => 0];
        foreach ($availability as $daySlots) {
            foreach ($daySlots as $status) {
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
        }

        return [
            'enseignant' => $enseignant,
            'availability' => $availability,
            'hours' => $hours,
            'days' => $days,
            'joursNoms' => self::AVAILABILITY_DAY_LABELS,
            'stats' => $stats,
        ];
    }

    /**
     * Extrait start_hour et end_hour d'une availability, peu importe le format
     * (Carbon, string TIME, string DATETIME).
     *
     * @return array{0: int, 1: int}
     */
    private function parseAvailabilityHours($availability): array
    {
        if ($availability->start_time instanceof \Carbon\Carbon) {
            return [$availability->start_time->hour, $availability->end_time->hour];
        }

        if (is_string($availability->start_time)) {
            return [
                (int) substr($availability->start_time, 0, 2),
                (int) substr($availability->end_time, 0, 2),
            ];
        }

        return [
            (int) substr((string) $availability->start_time, 0, 2),
            (int) substr((string) $availability->end_time, 0, 2),
        ];
    }

    /**
     * Récupère les séances réalisées (passées) pour un enseignant, avec exclusion
     * des séances marquées "absent" via la dernière teacher_attendance "start".
     *
     * @return \Illuminate\Support\Collection
     */
    private function fetchSeancesRealisees(
        ESBTPTeacher $enseignant,
        ESBTPAnneeUniversitaire $anneeCourante,
        string $periode,
    ): \Illuminate\Support\Collection {
        $seancesQuery = ESBTPSeanceCours::query()
            ->join(
                'esbtp_emploi_temps',
                'esbtp_seance_cours.emploi_temps_id',
                '=',
                'esbtp_emploi_temps.id',
            )
            ->leftJoin(
                DB::raw('(
                SELECT ta1.course_id, ta1.status
                FROM esbtp_teacher_attendances ta1
                INNER JOIN (
                    SELECT course_id,
                           MAX(CASE
                               WHEN DATE(date) = CURDATE() THEN CONCAT("1_", created_at)
                               WHEN DATE(date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = course_id) THEN CONCAT("2_", created_at)
                               ELSE CONCAT("3_", created_at)
                           END) as max_priority
                    FROM esbtp_teacher_attendances
                    WHERE type = "start"
                    GROUP BY course_id
                ) ta2 ON ta1.course_id = ta2.course_id
                     AND CONCAT(
                         CASE
                             WHEN DATE(ta1.date) = CURDATE() THEN "1_"
                             WHEN DATE(ta1.date) = (SELECT DATE(date_seance) FROM esbtp_seance_cours WHERE id = ta1.course_id) THEN "2_"
                             ELSE "3_"
                         END, ta1.created_at
                     ) = ta2.max_priority
                WHERE ta1.type = "start"
            ) as latest_attendance'),
                'latest_attendance.course_id',
                '=',
                'esbtp_seance_cours.id',
            )
            ->where(function ($query) {
                $query
                    ->whereNull('latest_attendance.status')
                    ->orWhere('latest_attendance.status', '!=', 'absent');
            })
            ->where('esbtp_seance_cours.teacher_id', $enseignant->id)
            ->where('esbtp_emploi_temps.annee_universitaire_id', $anneeCourante->id)
            ->select(
                'esbtp_seance_cours.matiere_id',
                'esbtp_seance_cours.classe_id',
                DB::raw('COUNT(DISTINCT esbtp_seance_cours.id) as nb_seances'),
                DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(esbtp_seance_cours.heure_fin, esbtp_seance_cours.heure_debut))/3600) as total_heures'),
            )
            ->groupBy('esbtp_seance_cours.matiere_id', 'esbtp_seance_cours.classe_id')
            ->whereRaw('(
                esbtp_seance_cours.date_seance < CURDATE()
                OR (
                    esbtp_seance_cours.date_seance = CURDATE()
                    AND TIME(esbtp_seance_cours.heure_fin) <= TIME(NOW())
                )
            )');

        $this->applySemestreFilter($seancesQuery, 'esbtp_emploi_temps.semestre', $periode);

        return $seancesQuery->get();
    }

    /**
     * Récupère les planifications académiques pour les classes données, agrégées
     * par (matiere_id, filiere_id, niveau_etude_id).
     */
    private function fetchPlanifications(
        \Illuminate\Support\Collection $classes,
        ESBTPAnneeUniversitaire $anneeCourante,
        string $periode,
    ): \Illuminate\Support\Collection {
        $planificationsQuery = ESBTPPlanificationAcademique::with(['matiere'])
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->whereIn('filiere_id', $classes->pluck('filiere_id')->unique())
            ->whereIn('niveau_etude_id', $classes->pluck('niveau_etude_id')->unique())
            ->select(
                'matiere_id',
                'filiere_id',
                'niveau_etude_id',
                DB::raw('SUM(volume_horaire_total) as heures_planifiees'),
            )
            ->groupBy('matiere_id', 'filiere_id', 'niveau_etude_id');

        $this->applySemestreFilter($planificationsQuery, 'semestre', $periode, includeNull: true);

        return $planificationsQuery->get();
    }

    /**
     * Applique un filtre de semestre tolérant aux différents formats stockés
     * (1, "1", "S1", "Semestre 1", "semestre1", etc).
     *
     * @param bool $includeNull  Inclut aussi les rows avec semestre NULL (planifications
     *                           qui s'appliquent aux deux semestres).
     */
    private function applySemestreFilter($query, string $column, string $periode, bool $includeNull = false): void
    {
        $variants = match ($periode) {
            'semestre1' => ['1', 1, 'S1', 'Semestre 1', 'semestre1', 'SEMESTRE 1', 'Semestre1', 's1'],
            'semestre2' => ['2', 2, 'S2', 'Semestre 2', 'semestre2', 'SEMESTRE 2', 'Semestre2', 's2'],
            default => null,
        };

        if ($variants === null) {
            return;
        }

        $query->where(function ($q) use ($column, $variants, $includeNull) {
            $q->whereIn($column, $variants);
            if ($includeNull) {
                $q->orWhereNull($column);
            }
        });
    }

    /**
     * Construit le bloc de données par classe (matières, heures planifiées vs réalisées, stats).
     *
     * @return array{classe: ESBTPClasse, matieres: \Illuminate\Support\Collection, stats: array}
     */
    private function buildClasseData(
        ESBTPClasse $classe,
        \Illuminate\Support\Collection $seancesRealisees,
        \Illuminate\Support\Collection $planificationsByCombo,
        \Illuminate\Support\Collection $matieres,
    ): array {
        $comboKey = $classe->filiere_id . '_' . $classe->niveau_etude_id;
        $planificationsCombo = $planificationsByCombo
            ->get($comboKey, collect())
            ->keyBy('matiere_id');
        $seancesClasse = $seancesRealisees->where('classe_id', $classe->id);

        $matiereIdsClasse = $seancesClasse->pluck('matiere_id')->filter()->unique();

        $matieresData = $matiereIdsClasse
            ->map(fn ($matiereId) => $this->buildMatiereData(
                $matiereId,
                $planificationsCombo,
                $seancesClasse,
                $matieres,
            ))
            ->filter()
            ->sortBy(fn ($item) => $item['matiere']->name ?? '')
            ->values();

        $totalPlanifiees = $matieresData->sum('heures_planifiees');
        $totalRealisees = $matieresData->sum('heures_realisees');
        $totalSeances = $matieresData->sum('nb_seances');
        $taux = $totalPlanifiees > 0
            ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
            : 0;

        return [
            'classe' => $classe,
            'matieres' => $matieresData,
            'stats' => [
                'heures_planifiees' => round($totalPlanifiees, 2),
                'heures_realisees' => round($totalRealisees, 2),
                'nb_seances' => (int) $totalSeances,
                'taux_realisation' => $taux,
            ],
        ];
    }

    /**
     * Construit le bloc de données pour une matière au sein d'une classe.
     */
    private function buildMatiereData(
        $matiereId,
        \Illuminate\Support\Collection $planificationsCombo,
        \Illuminate\Support\Collection $seancesClasse,
        \Illuminate\Support\Collection $matieres,
    ): array {
        $planification = $planificationsCombo->get($matiereId);
        $heuresPlanifiees = $planification ? (float) $planification->heures_planifiees : 0;

        $seancesMatiere = $seancesClasse->where('matiere_id', $matiereId);
        $totalHeures = (float) $seancesMatiere->sum('total_heures');
        $nbSeances = (int) $seancesMatiere->sum('nb_seances');

        $heuresRestantes = max(0, $heuresPlanifiees - $totalHeures);

        return [
            'matiere' => $matieres->get($matiereId),
            'heures_planifiees' => round($heuresPlanifiees, 2),
            'heures_realisees' => round($totalHeures, 2),
            'heures_restantes' => round($heuresRestantes, 2),
            'nb_seances' => $nbSeances,
            'pourcentage_realise' => $heuresPlanifiees > 0
                ? round(($totalHeures / $heuresPlanifiees) * 100, 1)
                : 0,
            'est_configure' => $heuresPlanifiees > 0,
        ];
    }

    /**
     * Agrège les statistiques globales depuis les blocs par classe.
     */
    private function aggregateGlobalStats(\Illuminate\Support\Collection $classesData): array
    {
        $totalPlanifiees = $classesData->sum(fn ($item) => $item['stats']['heures_planifiees'] ?? 0);
        $totalRealisees = $classesData->sum(fn ($item) => $item['stats']['heures_realisees'] ?? 0);
        $totalSeances = $classesData->sum(fn ($item) => $item['stats']['nb_seances'] ?? 0);
        $tauxGlobal = $totalPlanifiees > 0
            ? round(($totalRealisees / $totalPlanifiees) * 100, 1)
            : 0;

        return [
            'classes' => $classesData->count(),
            'heures_planifiees' => round($totalPlanifiees, 2),
            'heures_realisees' => round($totalRealisees, 2),
            'nb_seances' => (int) $totalSeances,
            'taux_realisation' => $tauxGlobal,
        ];
    }
}
