<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPExamenSurveillant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de planification des examens UEMOA.
 *
 * Pattern : génération auto par scope (annee + classe + semestre + session),
 * détection des conflits multi-classes étudiants, assignation surveillants,
 * lock notes anti-tampering.
 */
class ExamenSchedulingService
{
    /**
     * Génère les examens pour une session donnée à partir des matières/ECUE
     * planifiées sur le triplet (filiere + niveau + semestre).
     *
     * Idempotent : ne recrée pas un examen si déjà présent pour le même
     * scope (annee+classe+matiere+session+type).
     *
     * @return Collection<int, ESBTPExamenPlanifie>
     */
    public function genererExamensSession(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        int $semestre,
        string $typeExamen = 'EXAMEN',
        ?int $sessionId = null,
        ?Carbon $datePremierExamen = null
    ): Collection {
        $matieres = $this->getMatieresForScope($classe, $semestre);
        $created = collect();
        $base = $datePremierExamen ? $datePremierExamen->copy() : now()->addWeeks(2);

        DB::transaction(function () use (
            $matieres, $classe, $annee, $semestre, $typeExamen, $sessionId, $base, &$created
        ) {
            foreach ($matieres as $offset => $matiere) {
                $existing = ESBTPExamenPlanifie::where([
                    'annee_universitaire_id' => $annee->id,
                    'classe_id' => $classe->id,
                    'matiere_id' => $matiere->id,
                    'semestre' => $semestre,
                    'type_examen' => $typeExamen,
                ])
                    ->when($sessionId, fn ($q) => $q->where('session_id', $sessionId))
                    ->first();

                if ($existing) {
                    continue;
                }

                $debut = $base->copy()->addDays($offset);
                $exam = ESBTPExamenPlanifie::create([
                    'annee_universitaire_id' => $annee->id,
                    'classe_id' => $classe->id,
                    'matiere_id' => $matiere->id,
                    'parcours_id' => $classe->parcours_id,
                    'semestre' => $semestre,
                    'session_id' => $sessionId,
                    'type_examen' => $typeExamen,
                    'titre' => $this->buildTitre($matiere->name ?? 'Matière', $typeExamen, $semestre),
                    'date_debut' => $debut->copy()->setTime(9, 0),
                    'date_fin' => $debut->copy()->setTime(11, 0),
                    'duree_minutes' => 120,
                    'coefficient' => 1,
                    'bareme' => 20,
                    'status' => 'planned',
                    'created_by' => optional(auth()->user())->id,
                ]);
                $exam->numero_convocation = $this->genererNumeroConvocation($exam);
                $exam->save();
                $created->push($exam);
            }
        });

        return $created;
    }

    /**
     * Détecte les conflits d'horaire pour étudiants inscrits dans
     * plusieurs classes simultanées.
     *
     * Cas réel : un étudiant peut avoir 2 examens chevauchants si la
     * planification overlap. Retourne un Collection de paires.
     *
     * @return Collection<int, array{etudiant_id:int, examen_a:ESBTPExamenPlanifie, examen_b:ESBTPExamenPlanifie}>
     */
    public function detecterConflitsEtudiants(Collection $examens): Collection
    {
        $conflits = collect();

        $byEtudiant = $this->indexExamensByEtudiant($examens);

        foreach ($byEtudiant as $etudiantId => $exams) {
            $sorted = collect($exams)->sortBy('date_debut')->values();

            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                for ($j = $i + 1; $j < $sorted->count(); $j++) {
                    if ($this->examsOverlap($sorted[$i], $sorted[$j])) {
                        $conflits->push([
                            'etudiant_id' => $etudiantId,
                            'examen_a' => $sorted[$i],
                            'examen_b' => $sorted[$j],
                        ]);
                    }
                }
            }
        }

        return $conflits;
    }

    /**
     * Assigne des surveillants à un examen (idempotent par paire examen+user).
     *
     * @param  array<int>  $userIds
     */
    public function assignerSurveillants(
        ESBTPExamenPlanifie $examen,
        array $userIds,
        string $role = 'surveillant'
    ): int {
        $assigned = 0;
        foreach ($userIds as $userId) {
            $existing = ESBTPExamenSurveillant::where([
                'examen_id' => $examen->id,
                'user_id' => $userId,
            ])->first();

            if ($existing) {
                if ($existing->role !== $role) {
                    $existing->update(['role' => $role]);
                }
                continue;
            }

            ESBTPExamenSurveillant::create([
                'examen_id' => $examen->id,
                'user_id' => $userId,
                'role' => $role,
            ]);
            $assigned++;
        }

        return $assigned;
    }

    /**
     * Lock anti-tampering des notes après l'examen.
     * Empêche modification des notes existantes pour cet examen.
     */
    public function lockNotesAfterExam(ESBTPExamenPlanifie $examen, ?User $by = null): bool
    {
        if ($examen->notes_locked) {
            return false;
        }

        $examen->forceFill([
            'notes_locked' => true,
            'notes_locked_at' => now(),
            'notes_locked_by' => optional($by ?? auth()->user())->id,
            'status' => 'notes_locked',
        ])->save();

        Log::info('[ExamenSchedulingService] notes locked', [
            'examen_id' => $examen->id,
            'classe_id' => $examen->classe_id,
            'matiere_id' => $examen->matiere_id,
            'by' => $by?->id,
        ]);

        return true;
    }

    /**
     * Génère un numéro de convocation séquentiel thread-safe.
     * Format : CONV-{TENANT_CODE}-{ANNEE_LIBELLE}-{SEQ_4DIGITS}
     */
    public function genererNumeroConvocation(ESBTPExamenPlanifie $examen): string
    {
        $tenant = strtoupper((string) (config('app.tenant_code') ?? env('TENANT_CODE', 'PRES')));
        $annee = $examen->relationLoaded('anneeUniversitaire')
            ? $examen->anneeUniversitaire
            : $examen->anneeUniversitaire()->first();
        $anneeStr = $annee?->libelle ?? (string) $examen->annee_universitaire_id;
        $anneeStr = preg_replace('/[^A-Za-z0-9]/', '', $anneeStr);

        return DB::transaction(function () use ($examen, $tenant, $anneeStr) {
            $last = ESBTPExamenPlanifie::where('annee_universitaire_id', $examen->annee_universitaire_id)
                ->whereNotNull('numero_convocation')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('numero_convocation');

            $seq = 1;
            if ($last && preg_match('/-(\d{4})$/', $last, $m)) {
                $seq = ((int) $m[1]) + 1;
            }

            return sprintf('CONV-%s-%s-%04d', $tenant, $anneeStr, $seq);
        });
    }

    /**
     * Récupère les matières du scope via MatiereTreeBuilder canonique
     * (rule globale klassci-classe-matieres).
     */
    private function getMatieresForScope(ESBTPClasse $classe, int $semestre): Collection
    {
        $matiereIds = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('semestre', $semestre)
            ->whereNotNull('matiere_id')
            ->pluck('matiere_id')
            ->unique()
            ->values();

        if ($matiereIds->isEmpty()) {
            return collect();
        }

        return \App\Models\ESBTPMatiere::whereIn('id', $matiereIds)
            ->orderBy('name')
            ->get();
    }

    private function buildTitre(string $matiereName, string $type, int $semestre): string
    {
        $typeLabel = match ($type) {
            'EXAMEN' => 'Examen',
            'PARTIEL' => 'Partiel',
            'RATTRAPAGE' => 'Rattrapage',
            'SOUTENANCE' => 'Soutenance',
            default => 'Épreuve',
        };

        return sprintf('%s - %s - S%d', $typeLabel, $matiereName, $semestre);
    }

    /**
     * Construit un index étudiant -> [examens] sur base des inscriptions actives.
     *
     * @return array<int, array<int, ESBTPExamenPlanifie>>
     */
    private function indexExamensByEtudiant(Collection $examens): array
    {
        $classeIds = $examens->pluck('classe_id')->unique()->values()->all();
        if (empty($classeIds)) {
            return [];
        }

        $inscriptions = ESBTPInscription::query()
            ->whereIn('classe_id', $classeIds)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get(['etudiant_id', 'classe_id']);

        $byClasse = $inscriptions->groupBy('classe_id');
        $byEtudiant = [];

        foreach ($examens as $examen) {
            $inscritsCe = $byClasse->get($examen->classe_id) ?? collect();
            foreach ($inscritsCe as $insc) {
                $byEtudiant[$insc->etudiant_id][] = $examen;
            }
        }

        return $byEtudiant;
    }

    private function examsOverlap(ESBTPExamenPlanifie $a, ESBTPExamenPlanifie $b): bool
    {
        return $a->date_debut < $b->date_fin && $b->date_debut < $a->date_fin;
    }

    /* ═════════════════════════════════════════════════════════════════════
       CHANTIER MULTI-CLASSES UEMOA (refonte 22 mai 2026)
       Un examen LMD est rattaché à un ECUE (esbtp_matieres.unite_enseignement_id
       non null) et cible TOUTES les classes du scope :
         - classe   : 1 classe précise (mode legacy / TP individuel)
         - parcours : toutes les classes du parcours (+ extras inter-parcours)
         - mention  : toutes les classes LMD de la mention (filiere_id en LMD)
         - domaine  : toutes les classes des mentions du domaine
       ═════════════════════════════════════════════════════════════════════ */

    /**
     * Résout la liste des classes ciblées par un scope donné.
     *
     * @param  string  $scopeType  classe | parcours | mention | domaine
     * @param  ?int    $scopeId    ID de l'entité scope
     * @param  array   $extraParcoursIds  Parcours additionnels en inter-parcours (scope=parcours uniquement)
     * @return Collection<int, ESBTPClasse>
     */
    public function resolveScopedClasses(
        string $scopeType,
        ?int $scopeId,
        array $extraParcoursIds = []
    ): Collection {
        $query = ESBTPClasse::query();

        // Optionnellement filtrer par is_active si la colonne existe
        if (\Schema::hasColumn('esbtp_classes', 'is_active')) {
            $query->where('is_active', true);
        }

        switch ($scopeType) {
            case 'classe':
                if ($scopeId) {
                    $query->where('id', $scopeId);
                } else {
                    return collect();
                }
                break;

            case 'parcours':
                $parcoursIds = array_values(array_unique(array_filter(
                    array_merge([$scopeId], $extraParcoursIds)
                )));
                if (empty($parcoursIds)) return collect();
                $query->whereIn('parcours_id', $parcoursIds);
                break;

            case 'mention':
                // En LMD, filiere_id sert sémantiquement de mention_id
                // (voir rule classe-lmd-filiere-as-mention.md)
                if (! $scopeId) return collect();
                $query->where('filiere_id', $scopeId)
                    ->where('systeme_academique', 'LMD');
                break;

            case 'domaine':
                if (! $scopeId) return collect();
                $mentionIds = ESBTPLMDMention::where('domaine_id', $scopeId)
                    ->pluck('id')->all();
                if (empty($mentionIds)) return collect();
                $query->whereIn('filiere_id', $mentionIds)
                    ->where('systeme_academique', 'LMD');
                break;

            default:
                return collect();
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Détermine automatiquement le scope par défaut suggéré pour un examen
     * d'après le parcours et la classe principale :
     *   - L1 (niveau ordre = 1)          → 'mention' (tronc commun)
     *   - L2+ / M1 / M2 / Doctorat       → 'parcours'
     *   - Pas de LMD                     → 'classe'
     *
     * @return array{scope_type:string, scope_id:?int}
     */
    public function autoDetectScope(?ESBTPClasse $classe, ?ESBTPLMDParcours $parcours): array
    {
        if (! $classe) {
            return ['scope_type' => 'classe', 'scope_id' => null];
        }

        $systeme = $classe->systeme_academique ?? 'BTS';
        if ($systeme !== 'LMD') {
            return ['scope_type' => 'classe', 'scope_id' => $classe->id];
        }

        // LMD : décider mention vs parcours selon le niveau d'études
        $niveau = $classe->niveau ?? $classe->niveauEtude ?? null;
        $ordre = $niveau?->ordre ?? null;

        if ($ordre === 1) {
            // L1 → mention (filiere_id en LMD)
            return ['scope_type' => 'mention', 'scope_id' => $classe->filiere_id];
        }

        // L2+ / M1 / M2 → parcours (si présent)
        if ($classe->parcours_id) {
            return ['scope_type' => 'parcours', 'scope_id' => $classe->parcours_id];
        }

        // Pas de parcours assigné → fallback mention
        return ['scope_type' => 'mention', 'scope_id' => $classe->filiere_id];
    }

    /**
     * Synchronise la pivot examen ↔ classes en respectant les choix manuels
     * (excluded = true conservé pour audit, deletes soft).
     *
     * @param  array<int, int>  $classeIds  IDs des classes finalement sélectionnées
     * @return int Nombre de classes attachées (non excluded)
     */
    public function syncExamenClasses(ESBTPExamenPlanifie $examen, array $classeIds): int
    {
        $classeIds = array_values(array_unique(array_filter(array_map('intval', $classeIds))));

        // Récupère pivots existants pour conserver les soft-deletes / excluded historiques
        $existing = DB::table('esbtp_examen_classes')
            ->where('examen_id', $examen->id)
            ->get()
            ->keyBy('classe_id');

        $now = now();
        $kept = 0;
        foreach ($classeIds as $classeId) {
            if ($existing->has($classeId)) {
                // Réactive si soft-deleted ou excluded
                DB::table('esbtp_examen_classes')
                    ->where('examen_id', $examen->id)
                    ->where('classe_id', $classeId)
                    ->update([
                        'excluded' => false,
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('esbtp_examen_classes')->insert([
                    'examen_id' => $examen->id,
                    'classe_id' => $classeId,
                    'excluded' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $kept++;
        }

        // Marque comme excluded toutes les anciennes classes plus dans la nouvelle liste
        $newSet = array_flip($classeIds);
        foreach ($existing as $classeId => $row) {
            if (! isset($newSet[$classeId])) {
                DB::table('esbtp_examen_classes')
                    ->where('examen_id', $examen->id)
                    ->where('classe_id', $classeId)
                    ->update(['excluded' => true, 'updated_at' => $now]);
            }
        }

        // Met à jour classe_id "principale" (legacy / convocation per-class) à la 1ère classe
        if (! empty($classeIds) && (int) $examen->classe_id !== (int) $classeIds[0]) {
            $examen->update(['classe_id' => $classeIds[0]]);
        }

        return $kept;
    }

    /**
     * Retourne les UE d'un parcours (LMD) avec leurs ECUE.
     * Utilisé par le modal pour grouper les ECUE par UE dans le dropdown.
     *
     * @return Collection<int, array{ue: \App\Models\ESBTPUniteEnseignement, ecues: Collection}>
     */
    public function getEcuesGroupedByUe(?int $parcoursId, ?int $niveauEtudeId = null): Collection
    {
        if (! $parcoursId) {
            return collect();
        }

        $ueQuery = \App\Models\ESBTPUniteEnseignement::query()
            ->where('parcours_id', $parcoursId);

        if ($niveauEtudeId) {
            $ueQuery->where(function ($q) use ($niveauEtudeId) {
                $q->where('niveau_id', $niveauEtudeId)->orWhereNull('niveau_id');
            });
        }

        $ues = $ueQuery->orderBy('name')->get();

        // Charge tous les ECUE (Matières) de ces UEs en une seule query
        $ueIds = $ues->pluck('id')->all();
        $ecues = ESBTPMatiere::whereIn('unite_enseignement_id', $ueIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unite_enseignement_id'])
            ->groupBy('unite_enseignement_id');

        return $ues->map(fn ($ue) => [
            'ue' => $ue,
            'ecues' => $ecues->get($ue->id, collect()),
        ]);
    }

    /**
     * Détecte les parcours qui partagent un ECUE donné (toggle inter-parcours).
     * Retourne les parcours qui ont au moins une UE contenant cette matière.
     *
     * @return Collection<int, ESBTPLMDParcours>
     */
    public function detectSharedParcours(int $ecueMatiereId, ?int $excludeParcoursId = null): Collection
    {
        $ecue = ESBTPMatiere::find($ecueMatiereId);
        if (! $ecue || ! $ecue->unite_enseignement_id) {
            return collect();
        }

        // Trouve toutes les UEs qui partagent cet ECUE (souvent juste celle de l'ECUE)
        // puis tous les parcours de ces UEs.
        $ueIds = \App\Models\ESBTPUniteEnseignement::query()
            ->where(function ($q) use ($ecue) {
                // Même UE
                $q->where('id', $ecue->unite_enseignement_id)
                  // Même code (cas où une UE est dupliquée entre parcours avec même code)
                  ->orWhere('code', \App\Models\ESBTPUniteEnseignement::find($ecue->unite_enseignement_id)?->code);
            })
            ->pluck('id')->all();

        $parcoursIds = \App\Models\ESBTPUniteEnseignement::whereIn('id', $ueIds)
            ->whereNotNull('parcours_id')
            ->pluck('parcours_id')->unique()->values()->all();

        $query = ESBTPLMDParcours::whereIn('id', $parcoursIds);
        if ($excludeParcoursId) {
            $query->where('id', '!=', $excludeParcoursId);
        }

        return $query->orderBy('name')->get(['id', 'name', 'code']);
    }
}
