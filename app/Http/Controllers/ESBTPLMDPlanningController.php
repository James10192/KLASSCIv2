<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdatePlanificationRequest;
use App\Http\Requests\UpdatePlanificationRequest;
use App\Http\Requests\UpdateUeResponsableRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\VolumeBudgetService;
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

        // PR6 chantier emploi-temps-lmd-unification : ajouter section "Examens planifiés".
        // Scope query sur esbtp_seance_cours.type_seance IN (EXAMEN, PARTIEL, RATTRAPAGE, SOUTENANCE).
        // Phase 1 : pas de table dédiée — utilise infrastructure existante seance_cours.
        // Phase 2 (PR8-13) : migration vers esbtp_examens_planifies pour workflow scolarité complet.
        $ctx['examensRows'] = $this->loadExamensRows($ctx['filters'] ?? []);

        return view('esbtp.lmd.planning.index', $ctx);
    }

    /**
     * Charge les examens planifiés pour le filtre courant (parcours/niveau/semestre).
     * Scope query sur seance_cours avec type_seance ∈ evaluationCases() (PR6).
     *
     * @return \Illuminate\Support\Collection
     */
    private function loadExamensRows(array $filters): \Illuminate\Support\Collection
    {
        $filiereId = optional($this->resolveFiliereFromParcours($filters['parcours_id'] ?? null))->id;

        $query = \App\Models\ESBTPSeanceCours::query()
            ->whereIn('type_seance', \App\Enums\TypeSeance::evaluationCases())
            ->with(['matiere.uniteEnseignement', 'emploiTemps.classe', 'teacher.user'])
            ->whereHas('emploiTemps.classe', function ($q) use ($filiereId, $filters) {
                $q->where('systeme_academique', 'LMD');
                if ($filiereId) {
                    $q->where('filiere_id', $filiereId);
                }
                if (!empty($filters['niveau_id'])) {
                    $q->where('niveau_etude_id', $filters['niveau_id']);
                }
            })
            ->orderBy('date_seance', 'asc')
            ->orderBy('heure_debut', 'asc');

        return $query->get();
    }

    /**
     * Helper : résout la filière depuis un parcours_id (pour scope examens).
     */
    private function resolveFiliereFromParcours(?int $parcoursId)
    {
        if (!$parcoursId) {
            return null;
        }
        $parcours = ESBTPLMDParcours::find($parcoursId);
        return $parcours ? $parcours->filiere : null;
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
     *   - filiere_id : celle du parcours affiché, acceptée seulement si ce
     *     parcours voit l'ECUE ; sinon 422, sans repli (anti-IDOR, M3).
     *     Voir filiereDePlanification()
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
        if ($context['refus_filiere']) {
            return response()->json(['success' => false, 'message' => $context['refus_filiere']], 422);
        }
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
     * PATCH /esbtp/lmd/ues/{ue}/responsable — assigne / désassigne le
     * responsable d'une UE (directive UEMOA 03/2007/CM : 1 responsable par UE).
     *
     * Le payload accepte `responsable_ue_id` (entier user) ou null pour
     * désassigner. La permission `lmd.planning.edit` est vérifiée côté
     * FormRequest (authorize). On vérifie aussi que l'utilisateur cible
     * porte le rôle `enseignant` (cohérent avec updatePlanification).
     *
     * Audit automatique via le trait Auditable du modèle
     * (whitelist `$auditInclude` inclut `responsable_ue_id`).
     */
    public function updateUeResponsable(UpdateUeResponsableRequest $request, int $ueId): JsonResponse
    {
        $ue = ESBTPUniteEnseignement::findOrFail($ueId);

        $responsableId = $request->filled('responsable_ue_id')
            ? $request->integer('responsable_ue_id')
            : null;

        if ($responsableId !== null && !$this->teacherExists($responsableId)) {
            return response()->json([
                'success' => false,
                'message' => "L'utilisateur sélectionné n'est pas un enseignant.",
            ], 422);
        }

        $ue->responsable_ue_id = $responsableId;
        $ue->updated_by = $request->user()?->id;
        $ue->save();

        $ue->load('responsableUe:id,name');

        return response()->json([
            'success' => true,
            'ue' => [
                'id'                  => $ue->id,
                'responsable_ue_id'   => $ue->responsable_ue_id,
                'responsable_name'    => $ue->responsableUe?->name,
            ],
        ]);
    }

    /**
     * POST /esbtp/lmd/planifications/bulk-update — applique une serie de
     * champs (volumes, credits, coefficient, enseignant) a un ensemble
     * d'ECUE en une seule transaction.
     *
     * Securites :
     *   - max 50 ECUE par appel (validation FormRequest + abort_if defensive)
     *   - chaque ECUE est valide individuellement (LMD only, filiere par
     *     filiereDePlanification : refus si le parcours affiche ne voit pas
     *     l'ECUE) — meme protection IDOR que updatePlanification
     *   - enseignant valide une seule fois si present
     *   - transaction unique : si un ECUE plante, on continue les autres et
     *     on remonte les erreurs partielles dans la reponse JSON
     *   - audit log automatique via le trait Auditable du modele (Agent A)
     */
    public function bulkUpdatePlanification(BulkUpdatePlanificationRequest $request): JsonResponse
    {
        $ecueIds = $request->validated('ecue_ids');
        $fields  = $request->validated('fields');

        abort_if(count($ecueIds) > 50, 422, 'Maximum 50 ECUE par operation en masse.');
        abort_if(empty($fields), 422, 'Aucun champ a appliquer.');

        if (array_key_exists('enseignant_principal_id', $fields)
            && !empty($fields['enseignant_principal_id'])
            && !$this->teacherExists((int) $fields['enseignant_principal_id'])) {
            return response()->json([
                'success' => false,
                'message' => "L'utilisateur selectionne n'est pas un enseignant.",
            ], 422);
        }

        $context = $this->resolvePlanificationContext($request, null);
        if (!$context['niveau_id'] || !$context['semestre']) {
            return response()->json([
                'success' => false,
                'message' => 'Contexte niveau/semestre manquant — selectionnez un niveau et un semestre avant l\'edition en masse.',
            ], 422);
        }

        $updated = 0;
        $errors  = [];

        DB::transaction(function () use ($ecueIds, $fields, $context, &$updated, &$errors) {
            foreach ($ecueIds as $ecueId) {
                try {
                    $this->upsertPlanificationFields((int) $ecueId, $fields, $context);
                    $updated++;
                } catch (\Throwable $e) {
                    $errors[] = ['ecue_id' => $ecueId, 'message' => $e->getMessage()];
                    Log::warning('LMD bulk planif failed for ECUE', [
                        'ecue_id'   => $ecueId,
                        'user_id'   => auth()->id(),
                        'context'   => $context,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        });

        return response()->json([
            'success' => empty($errors),
            'partial' => $updated > 0 && !empty($errors),
            'updated' => $updated,
            'total'   => count($ecueIds),
            'errors'  => $errors,
        ]);
    }

    /**
     * Variante de `upsertPlanification()` qui prend directement un tableau de
     * champs (au lieu d'un FormRequest) pour servir le bulk-update. Reutilise
     * la meme strategie : filiereDePlanification (refus sans repli), lockForUpdate, fill
     * controle, recalcul du total, audit auto via le modele Auditable.
     */
    private function upsertPlanificationFields(int $ecueId, array $fields, array $contextHint): void
    {
        $matiere = ESBTPMatiere::find($ecueId);
        if (!$matiere || !$matiere->unite_enseignement_id) {
            throw new \RuntimeException("ECUE {$ecueId} non LMD ou introuvable.");
        }

        $demandee = isset($contextHint['filiere_id']) ? (int) $contextHint['filiere_id'] : null;
        [$filiereId, $refus] = $this->filiereDePlanification($matiere, $demandee);
        if ($refus || !$filiereId) {
            throw new \RuntimeException($refus ?? "Filiere indisponible pour ECUE {$ecueId}.");
        }

        [$planif, $wasCreated] = $this->lockOrInitPlanification($ecueId, $filiereId, $contextHint);

        // Whitelist des champs editables en bulk — meme set que la FormRequest.
        $allowed = [
            'volume_horaire_cm', 'volume_horaire_td', 'volume_horaire_tp',
            'volume_horaire_projet', 'volume_horaire_tpe',
            'coefficient', 'credits_ects', 'enseignant_principal_id',
        ];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $planif->{$key} = $fields[$key];
            }
        }

        if ($wasCreated) {
            $planif->created_by = auth()->id();
        }
        $planif->updated_by = auth()->id();

        $planif->volume_horaire_total = ($planif->volume_horaire_cm ?? 0)
            + ($planif->volume_horaire_td ?? 0)
            + ($planif->volume_horaire_tp ?? 0)
            + ($planif->volume_horaire_projet ?? 0)
            + ($planif->volume_horaire_tpe ?? 0);

        $planif->save();
    }

    /**
     * Lock or init a planification row for the given (ecue, filiere, contexte)
     * triple. Returns [$planif, $wasCreated]. Used by bulk path only.
     */
    private function lockOrInitPlanification(int $ecueId, int $filiereId, array $ctx): array
    {
        $planif = ESBTPPlanificationAcademique::query()
            ->where('matiere_id', $ecueId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $ctx['niveau_id'])
            ->where('semestre', $ctx['semestre'])
            ->where('annee_universitaire_id', $ctx['annee_id'])
            ->lockForUpdate()
            ->first();

        if ($planif) {
            return [$planif, false];
        }

        $planif = new ESBTPPlanificationAcademique([
            'matiere_id'             => $ecueId,
            'filiere_id'             => $filiereId,
            'niveau_etude_id'        => $ctx['niveau_id'],
            'semestre'               => $ctx['semestre'],
            'annee_universitaire_id' => $ctx['annee_id'],
        ]);
        $planif->statut    = ESBTPPlanificationAcademique::STATUT_PLANIFIE;
        $planif->is_active = true;
        // La colonne vaut 0 par defaut, et l'ecran lit « planif ?? ECUE » : une
        // ligne creee par la saisie d'heures affichait donc 0 credit a la place
        // de ceux de l'ECUE, et faussait le total CECT du parcours.
        $planif->credits_ects = $this->creditDeLEcue($ecueId);

        return [$planif, true];
    }

    /** Le credit de l'ECUE : celui de sa ligne de maquette, sinon celui de la matiere. */
    private function creditDeLEcue(int $ecueId): int
    {
        $pivot = DB::table('esbtp_ue_matiere')->where('matiere_id', $ecueId)
            ->whereNotNull('credit_ecue')->orderByDesc('parcours_id')->value('credit_ecue');

        return (int) ($pivot ?? ESBTPMatiere::whereKey($ecueId)->value('credit_ecue') ?? 0);
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
        // Meme initialisation que l'edition en masse : une seule source, sinon
        // l'une pose les credits de l'ECUE et l'autre les laisse a zero.
        [$planif, $wasCreated] = $this->lockOrInitPlanification($ecueId, (int) $context['filiere_id'], $context);

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
     * IMPORTANT (M3, anti-IDOR) : `filiere_id` est celle du parcours affiché,
     * acceptée seulement si ce parcours voit l'ECUE (filiereDePlanification) ;
     * sinon le contexte porte un refus et la requête répond 422, sans repli.
     * La filière de la fiche de l'UE ne sert que si aucune n'est envoyée ET
     * que l'UE ne sert qu'un parcours.
     */
    private function resolvePlanificationContext(Request $request, ?ESBTPMatiere $matiere = null): array
    {
        $clientFiliereId = $request->integer('filiere_id') ?: null;

        // La filiere du parcours AFFICHE. La fiche d'une UE partagee ne porte
        // que la filiere du premier parcours importe : l'imposer ecrivait les
        // heures saisies sur la maquette LPA dans la planification de LPV
        // (USAT) — perdues pour l'une, ecrasees pour l'autre. Une filiere dont
        // aucun parcours ne voit l'ECUE est refusee (422), jamais redirigee.
        // Sans ECUE (edition en masse), la filiere se tranche ECUE par ECUE dans
        // upsertPlanificationFields().
        [$filiereId, $refus] = $matiere
            ? $this->filiereDePlanification($matiere, $clientFiliereId)
            : [$clientFiliereId, null];

        return [
            'filiere_id' => $filiereId,
            'refus_filiere' => $refus,
            'niveau_id' => $request->integer('niveau_id') ?: null,
            'semestre' => $request->integer('semestre') ?: 1,
            'annee_id' => $request->integer('annee_universitaire_id')
                ?: optional(ESBTPAnneeUniversitaire::where('is_current', true)->first())->id,
        ];
    }

    /**
     * La filiere dans laquelle ecrire les heures de cet ECUE, ou le refus a dire.
     *
     * La filiere demandee est celle du parcours affiche. Elle est retenue si un
     * parcours de cette filiere voit l'ECUE dans sa maquette : une UE qu'il
     * utilise, et l'ECUE commun ou reserve a CE parcours. Sinon on refuse.
     * Retomber sur la filiere de la fiche de l'UE, ici, ecrirait les heures dans
     * la maquette d'un autre parcours sans le dire : c'est le defaut corrige.
     *
     * Sans filiere demandee, la fiche ne vaut que si l'UE ne sert qu'un parcours.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function filiereDePlanification(ESBTPMatiere $matiere, ?int $demandee): array
    {
        $ueIds = DB::table('esbtp_ue_matiere')->where('matiere_id', $matiere->id)
            ->pluck('unite_enseignement_id')
            ->push($matiere->unite_enseignement_id)
            ->filter()->unique()->values();

        if ($demandee) {
            $voit = DB::table('esbtp_lmd_parcours_ue as pu')
                ->join('esbtp_lmd_parcours as p', 'p.id', '=', 'pu.parcours_id')
                ->whereIn('pu.unite_enseignement_id', $ueIds)
                ->where('p.filiere_id', $demandee)
                ->where(function ($q) use ($matiere) {
                    $q->whereExists(fn ($sub) => $sub->selectRaw('1')->from('esbtp_ue_matiere as um')
                        ->whereColumn('um.unite_enseignement_id', 'pu.unite_enseignement_id')
                        ->where('um.matiere_id', $matiere->id)
                        ->where(fn ($w) => $w->where('um.parcours_id', 0)->orWhereColumn('um.parcours_id', 'p.id')))
                        // Tenu par la seule cle etrangere : commun, donc vu par tous.
                        ->orWhere(fn ($w) => $w->where('pu.unite_enseignement_id', $matiere->unite_enseignement_id)
                            ->whereNotExists(fn ($sub) => $sub->selectRaw('1')->from('esbtp_ue_matiere as um2')
                                ->whereColumn('um2.unite_enseignement_id', 'pu.unite_enseignement_id')
                                ->where('um2.matiere_id', $matiere->id)));
                })
                ->exists();

            return $voit
                ? [$demandee, null]
                : [null, "« {$matiere->name} » n'est pas dans la maquette du parcours affiché : ses heures ne peuvent pas y être enregistrées."];
        }

        $parcours = DB::table('esbtp_lmd_parcours_ue')->whereIn('unite_enseignement_id', $ueIds)
            ->distinct()->count('parcours_id');

        return $parcours <= 1
            ? [$this->deriveFiliereIdFromEcue($matiere), null]
            : [null, "Ce parcours n'a pas de filière : les heures de « {$matiere->name} », partagé entre plusieurs parcours, ne peuvent pas être rangées. Rattachez une filière au parcours."];
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
        // Force le reload de la relation enseignantPrincipal pour que le nom soit
        // a jour APRES un save() (sinon la relation peut etre stale et retourner
        // null, ce qui empechait le refresh realtime du bouton + Assigner sur UI).
        if ($planif->enseignant_principal_id) {
            $planif->load('enseignantPrincipal:id,name');
        } else {
            $planif->setRelation('enseignantPrincipal', null);
        }

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
     * GET /esbtp/lmd/planning/volumes
     * Returns realized vs planned hours per ECUE for the current filters,
     * aggregated across all LMD classes of the filière.
     * Used by the lpv-* widget in _listing.blade.php.
     */
    public function volumes(Request $request, VolumeBudgetService $service): JsonResponse
    {
        $this->authorize('lmd.planning.view');

        $filiereId = $request->integer('filiere_id');
        $niveauId  = $request->integer('niveau_id');
        $semestre  = $request->integer('semestre');
        $anneeId   = $request->integer('annee_id')
            ?: optional(ESBTPAnneeUniversitaire::where('is_current', true)->first())->id;

        if (!$filiereId || !$niveauId || !$semestre || !$anneeId) {
            return response()->json(['budgets' => []]);
        }

        $budgets = $service->forFiliere($filiereId, $niveauId, $semestre, $anneeId);

        return response()->json(['budgets' => $budgets]);
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
        $niveaux = ESBTPNiveauEtude::whereIn('type', ESBTPNiveauEtude::CYCLES_LMD)
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
        // expose la plage canonique L1 a M2 pour que le user puisse toujours
        // sélectionner quelque chose.
        if (empty($availableSemestres)) {
            $availableSemestres = ESBTPNiveauEtude::semestresLmd();
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
     *     <niveauId> => semestres de l'annee (L1=[1,2], M1 annee 4=[7,8]),
     *     ...
     *   ]
     */
    private function buildSemestresMap(Collection $niveaux): array
    {
        $map = ['all' => []];
        $allSet = [];

        foreach ($niveaux as $niveau) {
            $semestres = $niveau->semestres() ?: ESBTPNiveauEtude::semestresLmd();

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

        // La composition depend de la maquette : une unite partagee peut porter des
        // elements propres a un autre parcours, qui n'ont rien a faire dans ce
        // planning, et des elements surcharges qui y figureraient deux fois.
        $parcoursId = (int) $parcours->id;

        $matiereIds = $ues->flatMap(fn (ESBTPUniteEnseignement $ue) => $ue->getEcuesEffectifs($parcoursId))
            ->pluck('id')->unique();
        $planifs = $this->loadPlanifications($matiereIds, $parcours, $filters);

        return $ues->map(function (ESBTPUniteEnseignement $ue) use ($planifs, $parcoursId) {
            $ecues = $ue->getEcuesEffectifs($parcoursId)->map(fn ($ecue) => [
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
        // Pivot entier : la maquette se tranche dans getEcuesEffectifs(), qui a
        // besoin de voir toutes les lignes pour ne pas confondre « sans pivot »
        // et « reserve a un autre parcours ».
        $query = $parcours->unitesEnseignement()
            ->with(['ecues', 'matieres', 'responsableUe:id,name'])
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
