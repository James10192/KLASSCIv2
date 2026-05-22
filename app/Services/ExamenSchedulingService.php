<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPExamenSurveillant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;
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
}
