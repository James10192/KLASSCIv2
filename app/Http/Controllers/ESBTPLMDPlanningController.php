<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePlanificationRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Planning LMD : affichage hiérarchie UE -> ECUE par parcours/niveau/semestre
 * (volumes horaires UEMOA depuis `esbtp_planifications_academiques`).
 *
 * Édition inline (PR LMD-2 Phase 2) :
 *   - PATCH /esbtp/lmd/planifications/{ecueId} → updatePlanification()
 *     Met à jour ou crée la planification de l'ECUE ; recalcule
 *     volume_horaire_total automatiquement depuis CM+TD+TP+Projet+TPE.
 *   - GET /esbtp/lmd/planning/enseignants → enseignants()
 *     JSON liste des users role=enseignant pour le picker.
 */
class ESBTPLMDPlanningController extends Controller
{
    /** Types `esbtp_niveau_etudes.type` identifiant les niveaux LMD (valeurs canoniques de niveaux-etudes/create). */
    private const LMD_TYPES = ['Licence', 'Master', 'Doctorat'];

    public function index(Request $request): View
    {
        $ctx = $this->buildContext($request);

        // Pour le modal d'assignation enseignant : charger une fois les users
        // role=enseignant. Si la perm `lmd.planning.edit` n'est pas accordée,
        // on n'envoie pas la liste (la vue ne rendra pas le picker).
        $ctx['enseignants'] = $request->user()?->can('lmd.planning.edit')
            ? User::role('enseignant')
                ->select('id', 'name', 'email', 'username')
                ->with('roles:id,name')
                ->orderBy('name')
                ->get()
            : collect();

        return view('esbtp.lmd.planning.index', $ctx);
    }

    /**
     * GET /esbtp/lmd/planning/partial — returns JSON {kpis, listing, filters_semestre, filters} for AJAX reload.
     *
     * `filters_semestre` is the rendered HTML of the semestre filter dropdown,
     * already filtered server-side to the semestres available for the current
     * niveau_id (server-side cascade — no Alpine option mutation magic needed).
     */
    public function partial(Request $request)
    {
        $ctx = $this->buildContext($request);

        return response()->json([
            'kpis' => view('esbtp.lmd.planning._kpis', $ctx)->render(),
            'listing' => view('esbtp.lmd.planning._listing', $ctx)->render(),
            'filters_semestre' => view('esbtp.lmd.planning._filter_semestre', $ctx)->render(),
            'filters' => $ctx['filters'],
            'filiere_id' => $ctx['parcoursSelected']?->filiere_id,
        ]);
    }

    /**
     * PATCH /esbtp/lmd/planifications/{ecueId} — édition inline d'un champ
     * de la planification LMD (volume CM/TD/TP/Projet/TPE, coefficient,
     * crédits ECTS, enseignant principal).
     *
     * Le paramètre {ecueId} est l'ID de la matière/ECUE. Si aucune
     * planification n'existe pour le triplet (filière, niveau, semestre)
     * passé en query string, on la crée avec les valeurs envoyées.
     *
     * Recalcule volume_horaire_total = CM+TD+TP+Projet+TPE après chaque save.
     *
     * Sécurités appliquées :
     *   - assert ECUE LMD (matiere.unite_enseignement_id != null) — les
     *     matières BTS legacy ne sont pas planifiables ici (Silent #10)
     *   - filiere_id dérivée server-side depuis l'UE de l'ECUE pour
     *     éviter l'IDOR (M3) — la valeur client est seulement utilisée
     *     comme « hint » et validée contre la vérité server-side
     *   - DB::transaction + lockForUpdate sur l'unique composite pour
     *     éviter la double-création en race condition (M2)
     *   - created_by/updated_by assignés APRÈS le fill() pour qu'une
     *     éventuelle extension de la FormRequest ne puisse pas les
     *     écraser (M1, défensif)
     *   - QueryException catchée + Log::error structuré (Silent #2)
     */
    public function updatePlanification(UpdatePlanificationRequest $request, int $ecueId): JsonResponse
    {
        $matiere = ESBTPMatiere::findOrFail($ecueId);

        // Silent #10 : seules les matières liées à une UE (= ECUE LMD) peuvent
        // être planifiées via cette route. Les matières BTS legacy ont leur
        // propre tooling (planification.classes via ESBTPPlanningConfigController).
        abort_if(!$matiere->unite_enseignement_id, 422, "Cette matière n'est pas un ECUE LMD.");

        $context = $this->resolvePlanificationContext($request, $matiere);
        if (!$context['filiere_id'] || !$context['niveau_id']) {
            return response()->json(['success' => false, 'message' => 'Contexte filière/niveau manquant — sélectionnez un niveau et un semestre avant l\'édition.'], 422);
        }

        if ($request->filled('enseignant_principal_id') && !$this->teacherExists($request->integer('enseignant_principal_id'))) {
            return response()->json(['success' => false, 'message' => "L'utilisateur sélectionné n'est pas un enseignant."], 422);
        }

        try {
            [$planif, $wasCreated] = DB::transaction(
                fn () => $this->upsertPlanification($request, $ecueId, $context)
            );
        } catch (QueryException $e) {
            return $this->handlePlanifQueryException($e, $ecueId, $context);
        }

        $planif->load('enseignantPrincipal:id,name');

        // Silent #1 : signaler distinctement création vs mise à jour pour
        // que l'UI puisse afficher un toast contextuel.
        return response()->json([
            'success'       => true,
            'created'       => $wasCreated,
            'planification' => $this->serializePlanification($planif),
        ]);
    }

    /**
     * Vérifie qu'un user existe ET porte le rôle `enseignant`.
     */
    private function teacherExists(int $userId): bool
    {
        $user = User::find($userId);
        return $user !== null && $user->hasRole('enseignant');
    }

    /**
     * Upsert atomique d'une planification dans une transaction
     * (à appeler depuis DB::transaction). Retourne `[$planif, $wasCreated]`.
     */
    private function upsertPlanification(UpdatePlanificationRequest $request, int $ecueId, array $context): array
    {
        // M2 : lockForUpdate pose un row lock SELECT...FOR UPDATE sur la
        // (potentiellement absente) ligne. Si deux requêtes parallèles
        // attaquent le même 5-uplet unique, la seconde attendra que la
        // première commit avant de relire — la contrainte unique composite
        // `uniq_planif_academique` reste le filet ultime.
        $planif = ESBTPPlanificationAcademique::query()
            ->where('matiere_id', $ecueId)
            ->where('filiere_id', $context['filiere_id'])
            ->where('niveau_etude_id', $context['niveau_id'])
            ->where('semestre', $context['semestre'])
            ->where('annee_universitaire_id', $context['annee_id'])
            ->lockForUpdate()
            ->first();

        $wasCreated = false;
        if (!$planif) {
            $planif = new ESBTPPlanificationAcademique([
                'matiere_id' => $ecueId,
                'filiere_id' => $context['filiere_id'],
                'niveau_etude_id' => $context['niveau_id'],
                'semestre' => $context['semestre'],
                'annee_universitaire_id' => $context['annee_id'],
            ]);
            $planif->statut = ESBTPPlanificationAcademique::STATUT_PLANIFIE;
            $planif->is_active = true;
            $wasCreated = true;
        }

        // M1 : fill() AVANT l'assignation created_by/updated_by pour que ces
        // deux colonnes ne puissent jamais être écrasées par une payload
        // client si la FormRequest était étendue un jour avec ces clés.
        $planif->fill($request->validated());

        if ($wasCreated) {
            $planif->created_by = auth()->id();
        }
        $planif->updated_by = auth()->id();

        // Recalcul total = somme des cinq sous-volumes (UEMOA).
        $planif->volume_horaire_total = ($planif->volume_horaire_cm ?? 0)
            + ($planif->volume_horaire_td ?? 0)
            + ($planif->volume_horaire_tp ?? 0)
            + ($planif->volume_horaire_projet ?? 0)
            + ($planif->volume_horaire_tpe ?? 0);

        $planif->save();

        return [$planif, $wasCreated];
    }

    /**
     * Convertit une QueryException en JsonResponse adaptée :
     *   - 1062 / SQLSTATE 23000 → 409 Conflict (race condition unicité)
     *   - autre → 500 + Log::error structuré
     */
    private function handlePlanifQueryException(QueryException $e, int $ecueId, array $context): JsonResponse
    {
        $isUniqueViolation = ($e->errorInfo[1] ?? null) === 1062
            || ($e->errorInfo[0] ?? null) === '23000';

        Log::error('LMD planif update failed', [
            'ecue_id'   => $ecueId,
            'user_id'   => auth()->id(),
            'context'   => $context,
            'sqlstate'  => $e->errorInfo[0] ?? null,
            'sql_code'  => $e->errorInfo[1] ?? null,
            'exception' => $e->getMessage(),
        ]);

        if ($isUniqueViolation) {
            return response()->json([
                'success' => false,
                'message' => 'La planification a été modifiée par un autre utilisateur, rechargez la page.',
            ], 409);
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreur d\'enregistrement de la planification. Réessayez ou contactez le support.',
        ], 500);
    }

    /**
     * Résout le contexte de planification (filiere/niveau/semestre/année).
     *
     * IMPORTANT (M3, anti-IDOR) : `filiere_id` est dérivé server-side depuis
     * l'UE de l'ECUE et NON pris tel quel du client. La valeur client est
     * acceptée seulement si elle correspond à la filière de l'UE de l'ECUE
     * — sinon on retombe sur la valeur server-side.
     *
     * Chaîne canonique : ECUE.unite_enseignement_id → UE.filiere_id (FK directe
     * sur esbtp_unites_enseignement). Fallback via UE.parcours.filiere_id si
     * l'UE n'a pas de filière directe (rare mais autorisé par le schéma).
     */
    private function resolvePlanificationContext(Request $request, ?ESBTPMatiere $matiere = null): array
    {
        $serverFiliereId = $this->deriveFiliereIdFromEcue($matiere);
        $clientFiliereId = $request->integer('filiere_id') ?: null;

        // Si client envoie une filière qui ne matche pas celle dérivée
        // server-side, on prend toujours la server-side. Si server-side
        // n'a pas pu être dérivée (UE sans filière + sans parcours.filière),
        // on accepte la client mais ce cas est pathologique.
        $filiereId = $serverFiliereId
            ?? $clientFiliereId;

        return [
            'filiere_id' => $filiereId,
            'niveau_id' => $request->integer('niveau_id') ?: null,
            'semestre' => $request->integer('semestre') ?: 1,
            'annee_id' => $request->integer('annee_universitaire_id')
                ?: optional(ESBTPAnneeUniversitaire::where('is_current', true)->first())->id,
        ];
    }

    /**
     * Dérive la filière côté serveur depuis l'ECUE → UE → (filière directe
     * ou via parcours). Retourne null si la chaîne est cassée (ECUE orphelin).
     */
    private function deriveFiliereIdFromEcue(?ESBTPMatiere $matiere): ?int
    {
        if (!$matiere || !$matiere->unite_enseignement_id) {
            return null;
        }

        $ue = ESBTPUniteEnseignement::with('parcours:id,filiere_id')
            ->find($matiere->unite_enseignement_id);

        if (!$ue) {
            return null;
        }

        return $ue->filiere_id
            ?: optional($ue->parcours)->filiere_id;
    }

    private function serializePlanification(ESBTPPlanificationAcademique $planif): array
    {
        return [
            'id' => $planif->id,
            'volume_horaire_cm' => $planif->volume_horaire_cm,
            'volume_horaire_td' => $planif->volume_horaire_td,
            'volume_horaire_tp' => $planif->volume_horaire_tp,
            'volume_horaire_projet' => $planif->volume_horaire_projet,
            'volume_horaire_tpe' => $planif->volume_horaire_tpe,
            'volume_horaire_total' => $planif->volume_horaire_total,
            'coefficient' => $planif->coefficient,
            'credits_ects' => $planif->credits_ects,
            'enseignant_principal_id' => $planif->enseignant_principal_id,
            'enseignant_name' => $planif->enseignantPrincipal?->name,
        ];
    }

    /**
     * GET /esbtp/lmd/planning/enseignants — JSON liste des users role=enseignant
     * pour alimenter le picker. Triés par nom, eager-load roles pour le
     * groupement par rôle dans `<x-au-user-picker>`.
     */
    public function enseignants(): JsonResponse
    {
        $users = User::role('enseignant')
            ->select('id', 'name', 'email', 'username')
            ->with('roles:id,name')
            ->orderBy('name')
            ->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Shared resolver for index/partial — loads parcours + niveaux + filters
     * + cascade semestre map + planning rows + kpis. Single source of truth.
     */
    private function buildContext(Request $request): array
    {
        $parcours = ESBTPLMDParcours::with(['filiere', 'mention.domaine'])
            ->where('is_active', true)->orderBy('name')->get();

        // Charge TOUS les niveaux LMD actifs (Licence/Master/Doctorat) — la liste
        // doit être indépendante du parcours sélectionné, sinon L3 (et autres
        // niveaux dont le parcours n'a pas encore d'UE liée) disparaît du dropdown.
        $niveaux = ESBTPNiveauEtude::whereIn('type', self::LMD_TYPES)
            ->where('is_active', true)
            ->orderBy('type')->orderBy('year')->get();

        $parcoursId = $request->integer('parcours_id') ?: null;
        $parcoursSelected = $parcoursId ? $parcours->firstWhere('id', $parcoursId) : null;

        // Map des semestres VALIDES pour chaque niveau selon le standard UEMOA
        // (year * 2 - 1, year * 2). On NE filtre PAS par "UEs déjà liées" sinon
        // S5/S6 disparaissent quand le parcours TC Droit n'a d'UEs que sur L1/L2.
        $semestresMap = $this->buildSemestresMap($niveaux);
        $niveauId = $this->validateNiveauId($request->integer('niveau_id'), $niveaux);

        // Server-side cascade : the semestres allowed depend on the niveau_id.
        // If a niveau is picked, restrict to its semestres ; else union of all.
        $availableSemestres = $niveauId && isset($semestresMap[$niveauId])
            ? $semestresMap[$niveauId]
            : ($semestresMap['all'] ?? []);

        // Defensive fallback : si la map ne contient rien (cas pathologique),
        // expose la plage canonique 1..6 (couverture L1 à M2 standard UEMOA)
        // pour que le user puisse toujours sélectionner quelque chose.
        if (empty($availableSemestres)) {
            $availableSemestres = range(1, 6);
        }

        $filters = [
            'parcours_id' => $parcoursId,
            'niveau_id' => $niveauId,
            'semestre' => $this->validateSemestre($request->integer('semestre'), $availableSemestres),
        ];

        $rows = $parcoursSelected ? $this->buildPlanningRows($parcoursSelected, $filters) : collect();

        $kpis = [
            'ue_count' => $rows->count(),
            'ecue_count' => $rows->sum(fn ($row) => $row['ecues']->count()),
            'cect_total' => $rows->sum('cect'),
        ];

        return compact('parcours', 'niveaux', 'parcoursSelected', 'semestresMap', 'availableSemestres', 'filters', 'rows', 'kpis');
    }

    /**
     * Defensively reject a niveau_id from URL/query that is NOT in the LMD set
     * (typically a stale URL after the type-filter shipped). Falls back to null
     * (= "tous niveaux") rather than silently returning empty results.
     */
    private function validateNiveauId(?int $niveauId, $allowedNiveaux): ?int
    {
        if (!$niveauId) {
            return null;
        }
        return $allowedNiveaux->firstWhere('id', $niveauId) ? $niveauId : null;
    }

    /**
     * Reject a semestre that is NOT actually present in the parcours pivot
     * (option E cascade — keep the dropdown semantically consistent with the
     * imported maquette). Null = "all semestres".
     */
    private function validateSemestre(?int $semestre, array $allowedSemestres): ?int
    {
        if (!$semestre) {
            return null;
        }
        return in_array($semestre, $allowedSemestres, true) ? $semestre : null;
    }

    /**
     * Build the cascade map for the Semestre dropdown — basée sur le standard
     * UEMOA (year * 2 - 1, year * 2) et NON sur les UEs déjà liées au parcours.
     *
     * Pourquoi : si on filtre par "UEs liées", S5/S6 disparaissent dès que le
     * parcours TC Droit n'a d'UEs que sur L1/L2 (les L3 spé sont sur parcours
     * séparés Droit Privé/Public). On veut que la directrice puisse SAISIR des
     * UEs sur L3 même si aucune n'existe encore — donc map data-driven UEMOA.
     *
     * Returns shape:
     *   [
     *     'all'      => [1, 2, 3, 4, 5, 6, ...],  // union de tous les niveaux LMD
     *     <niveauId> => [year*2 - 1, year*2],     // UEMOA : L1=[1,2], L2=[3,4], L3=[5,6]
     *                                              // M1=[1,2] (M1 redémarre), M2=[3,4]
     *     ...
     *   ]
     *
     * Note : pour Master/Doctorat la numérotation des semestres redémarre (M1 = S1+S2)
     * conformément à la convention LMD UEMOA — d'où le calcul basé uniquement sur
     * `year` du niveau.
     */
    private function buildSemestresMap(Collection $niveaux): array
    {
        $map = ['all' => []];
        $allSet = [];

        foreach ($niveaux as $niveau) {
            $year = (int) ($niveau->year ?? 0);
            if ($year > 0) {
                $semestres = [$year * 2 - 1, $year * 2];
            } else {
                // Niveau sans year défini → fallback large (rare, défensif).
                $semestres = range(1, 6);
            }

            $map[(int) $niveau->id] = $semestres;
            foreach ($semestres as $sem) {
                $allSet[$sem] = true;
            }
        }

        $map['all'] = array_keys($allSet);
        sort($map['all']);

        return $map;
    }

    /**
     * @return Collection<int, array{ue: ESBTPUniteEnseignement, cect: int, ecues: Collection<int, array>}>
     */
    private function buildPlanningRows(ESBTPLMDParcours $parcours, array $filters): Collection
    {
        $ues = $this->loadUesForParcours($parcours, $filters['semestre'], $filters['niveau_id']);

        if ($ues->isEmpty()) {
            return collect();
        }

        $matiereIds = $ues->flatMap->getEcuesEffectifs()->pluck('id')->unique();
        $planifs = $this->loadPlanifications($matiereIds, $parcours, $filters);

        return $ues->map(function (ESBTPUniteEnseignement $ue) use ($planifs) {
            $ecues = $ue->getEcuesEffectifs()->map(fn ($ecue) => [
                'ecue' => $ecue,
                'planif' => $planifs->get($ecue->id),
            ])->values();

            return [
                'ue' => $ue,
                'cect' => (int) ($ue->credit ?? 0),
                'ecues' => $ecues,
            ];
        })->values();
    }

    private function loadUesForParcours(ESBTPLMDParcours $parcours, ?int $semestre, ?int $niveauId = null): Collection
    {
        $query = $parcours->unitesEnseignement()
            ->with(['ecues', 'matieres'])
            ->where('esbtp_unites_enseignement.is_active', true);

        if ($semestre) {
            $query->wherePivot('semestre', $semestre);
        }
        if ($niveauId) {
            $query->where('esbtp_unites_enseignement.niveau_id', $niveauId);
        }

        return $query->orderBy('esbtp_unites_enseignement.name')->get();
    }

    private function loadPlanifications(Collection $matiereIds, ESBTPLMDParcours $parcours, array $filters): Collection
    {
        if ($matiereIds->isEmpty() || !$parcours->filiere_id) {
            return collect();
        }

        $query = ESBTPPlanificationAcademique::query()
            ->with('enseignantPrincipal:id,name')
            ->where('filiere_id', $parcours->filiere_id)
            ->whereIn('matiere_id', $matiereIds);

        if ($filters['niveau_id']) {
            $query->where('niveau_etude_id', $filters['niveau_id']);
        }
        if ($filters['semestre']) {
            $query->where('semestre', $filters['semestre']);
        }

        return $query->get()->keyBy('matiere_id');
    }
}
