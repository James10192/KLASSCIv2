<?php

namespace App\Services;

use App\Enums\TypeSeance;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPTeacherAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Source unique de vérité des HEURES PRÉCISES réalisées par un enseignant.
 *
 * Partagé par la pédagogie (pages teacher-attendance / coordinateur — heures
 * seulement) ET par la comptabilité (page Paie — heures × taux). Aucune logique
 * monétaire ici : ce service ne connaît pas les taux.
 *
 * Décisions design (grill juin 2026) :
 *  - On compte les HEURES réelles (durée des séances), jamais le nombre de séances.
 *  - Le planifié sert de baromètre (taux de réalisation), JAMAIS de base de paie.
 *  - Capture hybride : si la séance a un émargement enseignant, il fait foi ;
 *    sinon la saisie manuelle du coordinateur prendra le relais (PR2 — colonnes
 *    heure réelle). Pour l'instant la durée planifiée de la séance réalisée
 *    sert de meilleure estimation des heures réalisées.
 *  - Warnings de ponctualité (retard) et de séance non émargée sont exposés.
 *
 * @see App\Enums\TypeSeance — types et helper isVolumeTracked() (CM/TD/TP)
 */
class TeacherHoursService
{
    /**
     * Statuts d'émargement enseignant qui valent « séance réalisée ».
     * (les présences enseignants utilisent un vocabulaire mixte selon les écrans).
     */
    public const STATUTS_REALISES = ['present', 'présent', 'presente', 'late', 'retard', 'en_retard', 'fait'];

    /** Statuts d'émargement qui signalent un retard (warning ponctualité). */
    public const STATUTS_RETARD = ['late', 'retard', 'en_retard'];

    /**
     * Résumé des heures d'un enseignant sur une période, ventilé par type de séance.
     *
     * @param  array{classe_id?:int|null,type_seance?:string|null,annee_universitaire_id?:int|null}  $filtres
     * @return array{
     *     periode: array{from:string,to:string},
     *     par_type: array<string, array<string,mixed>>,
     *     totaux: array<string,float|int>,
     *     warnings: array<int, array<string,mixed>>,
     *     taux_realisation: float
     * }
     */
    public function summary(ESBTPTeacher $teacher, Carbon $from, Carbon $to, array $filtres = []): array
    {
        $this->bornerProlongations($from, $to);
        $seances = $this->seancesDeLaPeriode($teacher, $from, $to, $filtres);
        $emargements = $this->emargementsParSeance($seances->pluck('id')->all());

        $acc = $this->accumuler($seances, $emargements);
        $totaux = $this->totaux($acc['par_type']);

        return [
            'periode'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'par_type'         => array_values($acc['par_type']),
            'totaux'           => $totaux,
            'warnings'         => $acc['warnings'],
            'taux_realisation' => $this->tauxRealisation($totaux),
        ];
    }

    /**
     * Rapport global : heures par enseignant sur la période (page report coordination).
     *
     * Charge toutes les séances de la période en une requête, les émargements en une
     * autre, puis ventile par enseignant. Évite le N+1 d'un summary() par enseignant.
     *
     * @param  array<string,mixed>  $filtres  classe_id, matiere_id, type_seance, annee_universitaire_id, teacher_id
     * @return array{
     *     periode: array{from:string,to:string},
     *     enseignants: array<int, array<string,mixed>>,
     *     global: array<string,float|int>,
     *     taux_realisation: float,
     *     nb_warnings: int
     * }
     */
    public function report(Carbon $from, Carbon $to, array $filtres = []): array
    {
        $this->bornerProlongations($from, $to);
        $seances = $this->seancesGlobalesDeLaPeriode($from, $to, $filtres);
        $emargements = $this->emargementsParSeance($seances->pluck('id')->all());

        $parEnseignant = [];
        foreach ($seances->groupBy('teacher_id') as $teacherId => $sesEns) {
            if (!$teacherId) {
                continue;
            }
            $first = $sesEns->first();
            $acc = $this->accumuler($sesEns, $emargements);
            $totaux = $this->totaux($acc['par_type']);

            $parEnseignant[] = [
                'teacher_id'       => (int) $teacherId,
                'name'             => $first->teacher?->user?->name ?? $first->teacher?->name ?? 'Enseignant',
                'par_type'         => array_values($acc['par_type']),
                'totaux'           => $totaux,
                'taux_realisation' => $this->tauxRealisation($totaux),
                'nb_warnings'      => count($acc['warnings']),
            ];
        }

        // Tri par heures réalisées décroissantes (les plus actifs en tête).
        usort($parEnseignant, fn ($a, $b) => $b['totaux']['heures_realisees'] <=> $a['totaux']['heures_realisees']);

        $global = $this->totauxGlobaux($parEnseignant);

        return [
            'periode'          => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'enseignants'      => $parEnseignant,
            'global'           => $global,
            'taux_realisation' => $this->tauxRealisation($global),
            'nb_warnings'      => array_sum(array_column($parEnseignant, 'nb_warnings')),
        ];
    }

    /**
     * Cœur de calcul : ventile une collection de séances par type + collecte warnings.
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPSeanceCours>  $seances
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Support\Collection>  $emargements  groupés par course_id
     * @return array{par_type: array<string, array<string,mixed>>, warnings: array<int, array<string,mixed>>}
     */
    private function accumuler(Collection $seances, Collection $emargements): array
    {
        $parType = [];
        $warnings = [];

        foreach ($seances as $seance) {
            // ⚠️ Lire la valeur BRUTE : le cast enum du modèle lève une ValueError sur
            // les valeurs legacy (ex 'cours') stockées en DB. fromLegacy les normalise.
            $type = TypeSeance::fromLegacy($seance->getRawOriginal('type_seance'));
            $key = $type->value;

            if (!isset($parType[$key])) {
                $parType[$key] = [
                    'type'              => $key,
                    'label'             => $type->label(),
                    'facturable'        => $type->isVolumeTracked(),
                    'icon'              => $type->badgeIcon(),
                    'style'             => $type->badgeInlineStyle(),
                    'nb_seances'        => 0,
                    'nb_realisees'      => 0,
                    'heures_planifiees' => 0.0,
                    'heures_realisees'  => 0.0,
                ];
            }

            $dureeH = $this->dureeHeures($seance);
            $parType[$key]['nb_seances']++;
            $parType[$key]['heures_planifiees'] += $dureeH;

            $rowsEmargement = $emargements->get($seance->id, collect());
            $realisee = $this->estRealisee($rowsEmargement);

            if ($realisee) {
                $parType[$key]['nb_realisees']++;
                // Planifié ou émargé : c'est l'école qui choisit (paie_base_heures).
                $parType[$key]['heures_realisees'] += $this->heuresPayees($seance, $rowsEmargement, $dureeH);
            }

            foreach ($this->warningsSeance($seance, $rowsEmargement, $realisee) as $w) {
                $warnings[] = $w;
            }
        }

        foreach ($parType as $k => $row) {
            $parType[$k]['heures_planifiees'] = round($row['heures_planifiees'], 2);
            $parType[$k]['heures_realisees'] = round($row['heures_realisees'], 2);
        }

        // Tri stable : CM, TD, TP d'abord, puis le reste dans l'ordre de l'enum.
        $ordre = array_flip(TypeSeance::values());
        uasort($parType, fn ($a, $b) => ($ordre[$a['type']] ?? 99) <=> ($ordre[$b['type']] ?? 99));

        return ['par_type' => $parType, 'warnings' => $warnings];
    }

    private function tauxRealisation(array $totaux): float
    {
        return $totaux['heures_planifiees'] > 0
            ? round($totaux['heures_realisees'] / $totaux['heures_planifiees'] * 100, 1)
            : 0.0;
    }

    /**
     * Séances d'un enseignant sur la période (date_seance), filtrables.
     *
     * @param  array<string,mixed>  $filtres
     * @return \Illuminate\Support\Collection<int, ESBTPSeanceCours>
     */
    public function seancesDeLaPeriode(ESBTPTeacher $teacher, Carbon $from, Carbon $to, array $filtres = []): Collection
    {
        $query = ESBTPSeanceCours::query()
            ->where('teacher_id', $teacher->id)
            // Séances d'enseignement uniquement (exclut récréations / pauses déjeuner).
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereNotNull('date_seance')
            ->whereDate('date_seance', '>=', $from->toDateString())
            ->whereDate('date_seance', '<=', $to->toDateString());

        if (!empty($filtres['classe_id'])) {
            $query->where('classe_id', $filtres['classe_id']);
        }
        if (!empty($filtres['type_seance'])) {
            $query->where('type_seance', $filtres['type_seance']);
        }
        if (!empty($filtres['annee_universitaire_id'])) {
            $query->where('annee_universitaire_id', $filtres['annee_universitaire_id']);
        }

        return $query->with(['classe:id,name', 'matiere:id,name'])
            ->orderBy('date_seance')
            ->get();
    }

    /**
     * Séances de TOUS les enseignants sur la période (rapport global), filtrables.
     *
     * @param  array<string,mixed>  $filtres
     * @return \Illuminate\Support\Collection<int, ESBTPSeanceCours>
     */
    public function seancesGlobalesDeLaPeriode(Carbon $from, Carbon $to, array $filtres = []): Collection
    {
        $query = ESBTPSeanceCours::query()
            ->whereNotNull('teacher_id')
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereNotNull('date_seance')
            ->whereDate('date_seance', '>=', $from->toDateString())
            ->whereDate('date_seance', '<=', $to->toDateString());

        if (!empty($filtres['teacher_id'])) {
            $query->where('teacher_id', $filtres['teacher_id']);
        }
        if (!empty($filtres['classe_id'])) {
            $query->where('classe_id', $filtres['classe_id']);
        }
        if (!empty($filtres['matiere_id'])) {
            $query->where('matiere_id', $filtres['matiere_id']);
        }
        if (!empty($filtres['type_seance'])) {
            $query->where('type_seance', $filtres['type_seance']);
        }
        if (!empty($filtres['annee_universitaire_id'])) {
            $query->where('annee_universitaire_id', $filtres['annee_universitaire_id']);
        }

        return $query->with([
            'teacher:id,user_id',
            'teacher.user:id,name',
            'matiere:id,name',
            'classe:id,name',
        ])->orderBy('date_seance')->get();
    }

    /**
     * Durée précise (heures) d'une séance — exposée pour le rendu des listes.
     */
    public function dureeSeance(ESBTPSeanceCours $seance): float
    {
        return $this->dureeHeures($seance);
    }

    /**
     * Émargements groupés par séance (course_id).
     *
     * Les séances sont déjà scopées à l'enseignant (seance.teacher_id = esbtp_teachers.id),
     * donc un filtre sur course_id suffit. Rappel : l'émargement, lui, porte le
     * users.id du compte dans sa colonne teacher_id (FK) — ne jamais le comparer
     * à seance.teacher_id.
     *
     * @param  array<int>  $seanceIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, ESBTPTeacherAttendance>>
     */
    private function emargementsParSeance(array $seanceIds): Collection
    {
        if (empty($seanceIds)) {
            return collect();
        }

        return ESBTPTeacherAttendance::query()
            ->whereIn('course_id', $seanceIds)
            ->get()
            ->groupBy('course_id');
    }

    /** Durée planifiée d'une séance en heures (depuis heure_debut / heure_fin). */
    private function dureeHeures(ESBTPSeanceCours $seance): float
    {
        if (!$seance->heure_debut || !$seance->heure_fin) {
            return 0.0;
        }

        $minutes = abs($seance->heure_fin->diffInMinutes($seance->heure_debut));

        return round($minutes / 60, 2);
    }

    public const BASE_PLANIFIEE = 'planifiees';
    public const BASE_EMARGEE = 'emargees';

    /** @var array<int, array<string, int>>|null minutes de prolongation accordées, par séance puis date */
    private ?array $prolongations = null;

    /** @var array{0: string, 1: string}|null période dont on lit les prolongations */
    private ?array $bornesProlongations = null;

    /** Ne lire que les prolongations de la période calculée, pas tout l'historique. */
    private function bornerProlongations(Carbon $from, Carbon $to): void
    {
        $this->bornesProlongations = [$from->toDateString(), $to->toDateString()];
        $this->prolongations = null;
    }

    /**
     * Les heures payées d'une séance réalisée.
     *
     * - `planifiees` (défaut, comportement d'avant) : la durée prévue ;
     * - `emargees` : de l'émargement de début (jamais avant l'heure prévue) à la
     *   fin prévue, prolongations accordées comprises. 40 minutes de retard ne
     *   sont plus payées comme une heure faite, et une prolongation accordée l'est.
     *
     * L'émargement de fin ne raccourcit pas la séance : il s'ouvre avant la fin
     * prévue, et le payer à l'heure où il est signé pénaliserait l'enseignant
     * ponctuel.
     */
    private function heuresPayees(ESBTPSeanceCours $seance, Collection $rowsEmargement, float $dureePlanifiee): float
    {
        if (\App\Helpers\SettingsHelper::get('paie_base_heures', self::BASE_PLANIFIEE) !== self::BASE_EMARGEE) {
            return $dureePlanifiee;
        }

        $debut = $rowsEmargement
            ->filter(fn ($r) => ($r->type ?? 'start') === 'start' && in_array(strtolower((string) $r->status), self::STATUTS_REALISES, true))
            ->sortBy('validated_at')
            ->first();

        $brutDebut = $seance->getAttributes()['heure_debut'] ?? null;
        $brutFin = $seance->getAttributes()['heure_fin'] ?? null;
        if (! $debut || ! $debut->validated_at || ! $brutDebut || ! $brutFin) {
            return $dureePlanifiee;
        }

        $jour = Carbon::parse($debut->date ?? $debut->validated_at)->startOfDay();
        $debutPrevu = $jour->copy()->setTimeFromTimeString(substr((string) $brutDebut, 0, 8));
        $finPrevue = $jour->copy()->setTimeFromTimeString(substr((string) $brutFin, 0, 8))
            ->addMinutes($this->minutesProlongees((int) $seance->id, $jour->toDateString()));

        $depart = Carbon::parse($debut->validated_at)->max($debutPrevu);
        if ($depart->gte($finPrevue)) {
            return 0.0;
        }

        return round($depart->diffInMinutes($finPrevue) / 60, 2);
    }

    private function minutesProlongees(int $seanceId, string $date): int
    {
        if ($this->prolongations === null) {
            $this->prolongations = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('esbtp_prolongations_seance')) {
                $requete = \App\Models\ESBTPProlongationSeance::where('statut', \App\Models\ESBTPProlongationSeance::ACCORDEE);
                if ($this->bornesProlongations) {
                    $requete->whereBetween('date', $this->bornesProlongations);
                }
                foreach ($requete->get(['seance_cours_id', 'date', 'minutes']) as $p) {
                    $cle = $p->date->toDateString();
                    $this->prolongations[$p->seance_cours_id][$cle] = ($this->prolongations[$p->seance_cours_id][$cle] ?? 0) + (int) $p->minutes;
                }
            }
        }

        return $this->prolongations[$seanceId][$date] ?? 0;
    }

    /** Une séance est réalisée si au moins un émargement n'est pas une absence. */
    private function estRealisee(Collection $rowsEmargement): bool
    {
        if ($rowsEmargement->isEmpty()) {
            return false;
        }

        return $rowsEmargement->contains(function ($row) {
            return in_array(strtolower((string) $row->status), self::STATUTS_REALISES, true);
        });
    }

    /**
     * Warnings d'une séance : retard d'émargement, séance passée non émargée.
     * (Le dépassement d'horaire — overrun — arrivera en PR2 avec la durée réelle.)
     *
     * @return array<int, array<string,mixed>>
     */
    private function warningsSeance(ESBTPSeanceCours $seance, Collection $rowsEmargement, bool $realisee): array
    {
        $out = [];
        $estPassee = $seance->date_seance
            && Carbon::parse($seance->date_seance)->endOfDay()->isPast();

        $enRetard = $rowsEmargement->contains(function ($row) {
            return in_array(strtolower((string) $row->status), self::STATUTS_RETARD, true);
        });

        if ($enRetard) {
            $out[] = [
                'seance_id' => $seance->id,
                'type'      => 'retard',
                'severite'  => 'warning',
                'message'   => sprintf(
                    'Retard signalé — %s du %s',
                    $seance->matiere->name ?? 'séance',
                    Carbon::parse($seance->date_seance)->format('d/m/Y')
                ),
            ];
        }

        if ($estPassee && !$realisee) {
            $out[] = [
                'seance_id' => $seance->id,
                'type'      => 'non_emarge',
                'severite'  => 'danger',
                'message'   => sprintf(
                    'Séance non émargée — %s du %s',
                    $seance->matiere->name ?? 'séance',
                    Carbon::parse($seance->date_seance)->format('d/m/Y')
                ),
            ];
        }

        return $out;
    }

    /**
     * Agrège les totaux depuis la ventilation par type.
     *
     * @param  array<string, array<string,mixed>>  $parType
     * @return array<string, float|int>
     */
    private function totaux(array $parType): array
    {
        $t = [
            'nb_seances'                  => 0,
            'nb_realisees'                => 0,
            'heures_planifiees'           => 0.0,
            'heures_realisees'            => 0.0,
            'heures_realisees_facturables' => 0.0,
        ];

        foreach ($parType as $row) {
            $t['nb_seances']        += $row['nb_seances'];
            $t['nb_realisees']      += $row['nb_realisees'];
            $t['heures_planifiees'] += $row['heures_planifiees'];
            $t['heures_realisees']  += $row['heures_realisees'];
            if ($row['facturable']) {
                $t['heures_realisees_facturables'] += $row['heures_realisees'];
            }
        }

        $t['heures_planifiees'] = round($t['heures_planifiees'], 2);
        $t['heures_realisees']  = round($t['heures_realisees'], 2);
        $t['heures_realisees_facturables'] = round($t['heures_realisees_facturables'], 2);

        return $t;
    }

    /**
     * Agrège les totaux globaux depuis les lignes par enseignant.
     *
     * @param  array<int, array<string,mixed>>  $parEnseignant
     * @return array<string, float|int>
     */
    private function totauxGlobaux(array $parEnseignant): array
    {
        $g = [
            'nb_enseignants'               => count($parEnseignant),
            'nb_seances'                   => 0,
            'nb_realisees'                 => 0,
            'heures_planifiees'            => 0.0,
            'heures_realisees'             => 0.0,
            'heures_realisees_facturables' => 0.0,
        ];

        foreach ($parEnseignant as $ens) {
            $g['nb_seances']                   += $ens['totaux']['nb_seances'];
            $g['nb_realisees']                 += $ens['totaux']['nb_realisees'];
            $g['heures_planifiees']            += $ens['totaux']['heures_planifiees'];
            $g['heures_realisees']             += $ens['totaux']['heures_realisees'];
            $g['heures_realisees_facturables'] += $ens['totaux']['heures_realisees_facturables'];
        }

        $g['heures_planifiees']            = round($g['heures_planifiees'], 2);
        $g['heures_realisees']             = round($g['heures_realisees'], 2);
        $g['heures_realisees_facturables'] = round($g['heures_realisees_facturables'], 2);

        return $g;
    }
}
