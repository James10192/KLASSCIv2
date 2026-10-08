<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Duplique les notes d'une évaluation vers l'ECUE associé, sans jamais
 * modifier l'évaluation source ni les inscriptions.
 *
 * Chaque source reçoit une cible de même classe, année et période. La cible est
 * réutilisée lorsqu'elle est unique ; sinon elle est créée en clonant le cadre
 * pédagogique de la source. Toute ambiguïté annule le lot entier.
 */
class CLINotesDuplicationEcueController extends BaseApiController
{
    public function dupliquer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'source_evaluation_ids' => 'required|array|min:1|max:50',
            'source_evaluation_ids.*' => 'required|integer|distinct',
            'matiere_cible_id' => 'required|integer|exists:esbtp_matieres,id',
            'motif' => 'required|string|min:10|max:500',
            'dry_run' => 'nullable|boolean',
            'confirmation' => 'nullable|string',
        ]);

        $dryRun = (bool) ($v['dry_run'] ?? true);
        if (! $dryRun && ($v['confirmation'] ?? null) !== 'DUPLIQUER_LES_NOTES') {
            return $this->errorResponse(
                'Confirmation invalide. Envoyez confirmation=DUPLIQUER_LES_NOTES pour exécuter.',
                [],
                422
            );
        }

        $annee = ESBTPAnneeUniversitaire::findOrFail((int) $v['annee_universitaire_id']);
        $cibleMatiere = ESBTPMatiere::findOrFail((int) $v['matiere_cible_id']);
        $sourceIds = collect($v['source_evaluation_ids'])->map(fn ($id) => (int) $id)->values();

        $sources = ESBTPEvaluation::query()
            ->whereIn('id', $sourceIds)
            ->where('annee_universitaire_id', $annee->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED))
            ->with('classe:id,name')
            ->get()
            ->keyBy('id');

        $erreurs = [];
        foreach ($sourceIds as $sourceId) {
            $source = $sources->get($sourceId);
            if (! $source) {
                $erreurs[] = ['evaluation_id' => $sourceId, 'erreur' => 'Évaluation absente, hors année cible ou annulée.'];
                continue;
            }
            if ((int) $source->matiere_id === (int) $cibleMatiere->id) {
                $erreurs[] = ['evaluation_id' => $sourceId, 'erreur' => 'La matière cible est déjà celle de la source.'];
            }
        }

        if ($erreurs !== []) {
            return $this->errorResponse('Duplication refusée. Aucune donnée n’a été modifiée.', ['errors' => $erreurs], 422);
        }

        $ciblesExistantes = ESBTPEvaluation::query()
            ->where('matiere_id', $cibleMatiere->id)
            ->where('annee_universitaire_id', $annee->id)
            ->whereIn('classe_id', $sources->pluck('classe_id')->unique())
            ->get()
            ->groupBy(fn (ESBTPEvaluation $e) => $e->classe_id.'|'.$e->periode);

        $notesSources = ESBTPNote::withTrashed()
            ->whereIn('evaluation_id', $sourceIds)
            ->get()
            ->groupBy('evaluation_id');

        $plans = [];
        foreach ($sourceIds as $sourceId) {
            /** @var ESBTPEvaluation $source */
            $source = $sources->get($sourceId);
            $cleCible = $source->classe_id.'|'.$source->periode;
            $candidates = $ciblesExistantes->get($cleCible, collect());

            if ($candidates->count() > 1) {
                $erreurs[] = [
                    'evaluation_id' => $sourceId,
                    'erreur' => 'Plusieurs évaluations cibles existent pour cette classe et cette période : sélection manuelle requise.',
                    'evaluations_cibles' => $candidates->pluck('id')->values()->all(),
                ];
                continue;
            }

            $notesSource = $notesSources->get($sourceId, collect());
            if ($notesSource->isEmpty()) {
                $erreurs[] = ['evaluation_id' => $sourceId, 'erreur' => 'La source ne contient aucune note à dupliquer.'];
                continue;
            }

            if ($notesSource->contains(fn (ESBTPNote $note) => $note->trashed())) {
                $erreurs[] = ['evaluation_id' => $sourceId, 'erreur' => 'La source contient une note supprimée : restauration ou correction manuelle requise.'];
                continue;
            }

            $doublonsSource = $notesSource->groupBy('etudiant_id')->filter(fn ($notes) => $notes->count() !== 1);
            if ($doublonsSource->isNotEmpty()) {
                $erreurs[] = [
                    'evaluation_id' => $sourceId,
                    'erreur' => 'La source contient des notes multiples ou supprimées pour un même étudiant.',
                    'etudiants' => $doublonsSource->keys()->values()->all(),
                ];
                continue;
            }

            $etudiantIds = $notesSource->pluck('etudiant_id')->map(fn ($id) => (int) $id)->values();
            $inscrits = ESBTPInscription::query()
                ->whereIn('etudiant_id', $etudiantIds)
                ->where('classe_id', $source->classe_id)
                ->where('annee_universitaire_id', $annee->id)
                ->where('status', 'active')
                ->where('workflow_step', 'etudiant_cree')
                ->whereNull('deleted_at')
                ->pluck('etudiant_id')
                ->map(fn ($id) => (int) $id)
                ->flip();

            $nonInscrits = $etudiantIds->filter(fn (int $id) => ! $inscrits->has($id))->values();
            if ($nonInscrits->isNotEmpty()) {
                $erreurs[] = [
                    'evaluation_id' => $sourceId,
                    'erreur' => 'Une ou plusieurs notes source ne correspondent plus à une inscription active de la classe.',
                    'etudiants' => $nonInscrits->all(),
                ];
                continue;
            }

            $cible = $candidates->first();
            $notesCible = $cible
                ? ESBTPNote::withTrashed()->where('evaluation_id', $cible->id)->get()->groupBy('etudiant_id')
                : collect();

            if ($cible && $notesCible->filter(fn ($notes) => $notes->count() !== 1)->isNotEmpty()) {
                $erreurs[] = ['evaluation_id' => $sourceId, 'erreur' => 'La cible contient des notes multiples ou supprimées ambiguës.'];
                continue;
            }

            $etrangeres = $cible
                ? $notesCible->keys()->map(fn ($id) => (int) $id)->diff($etudiantIds)->values()
                : collect();
            if ($etrangeres->isNotEmpty()) {
                $erreurs[] = [
                    'evaluation_id' => $sourceId,
                    'evaluation_cible_id' => $cible->id,
                    'erreur' => 'La cible contient déjà des notes d’étudiants absents de la source.',
                    'etudiants' => $etrangeres->all(),
                ];
                continue;
            }

            $actions = $notesSource->map(function (ESBTPNote $note) use ($notesCible) {
                $existante = $notesCible->get((int) $note->etudiant_id)?->first();
                $identique = $existante
                    && ! $existante->trashed()
                    && (bool) $existante->is_absent === (bool) $note->is_absent
                    && (float) $existante->note === (float) $note->note;

                return [
                    'etudiant_id' => (int) $note->etudiant_id,
                    'note' => $note->note === null ? null : (float) $note->note,
                    'is_absent' => (bool) $note->is_absent,
                    'action' => $identique ? 'inchangée' : ($existante ? 'mise_a_jour' : 'création'),
                ];
            })->values();

            $plans[] = [
                'source' => $source,
                'cible_existante' => $cible,
                'actions' => $actions,
            ];
        }

        if ($erreurs !== []) {
            return $this->errorResponse('Duplication refusée. Aucune donnée n’a été modifiée.', ['errors' => $erreurs], 422);
        }

        $resume = [
            'dry_run' => $dryRun,
            'annee' => ['id' => (int) $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'matiere_cible' => ['id' => (int) $cibleMatiere->id, 'name' => $cibleMatiere->name],
            'evaluations_creees' => count(array_filter($plans, fn ($p) => ! $p['cible_existante'])),
            'notes_creees' => $this->compter($plans, 'création'),
            'notes_mises_a_jour' => $this->compter($plans, 'mise_a_jour'),
            'notes_inchangees' => $this->compter($plans, 'inchangée'),
            'evaluations' => collect($plans)->map(fn ($p) => [
                'source_id' => (int) $p['source']->id,
                'classe' => $p['source']->classe->name ?? null,
                'cible_id' => $p['cible_existante']?->id,
                'creation_cible' => ! $p['cible_existante'],
                'notes' => $p['actions']->count(),
            ])->values(),
        ];

        if ($dryRun) {
            return $this->successResponse($resume, 'Simulation : aucune écriture. Relancer avec dry_run=false et la confirmation pour appliquer.');
        }

        $auteur = (int) $request->user()->id;
        DB::transaction(function () use ($plans, $cibleMatiere, $annee, $auteur): void {
            foreach ($plans as $plan) {
                /** @var ESBTPEvaluation $source */
                $source = $plan['source'];
                /** @var ESBTPEvaluation|null $cible */
                $cible = $plan['cible_existante'];

                if (! $cible) {
                    $cible = ESBTPEvaluation::create([
                        'titre' => $source->titre,
                        'description' => $source->description,
                        'matiere_id' => $cibleMatiere->id,
                        'classe_id' => $source->classe_id,
                        'type' => $source->type,
                        'date_evaluation' => $source->date_evaluation,
                        'coefficient' => $source->coefficient,
                        'bareme' => $source->bareme,
                        'duree_minutes' => $source->duree_minutes,
                        'periode' => $source->periode,
                        'annee_universitaire_id' => $annee->id,
                        'status' => $source->status,
                        'is_published' => $source->is_published,
                        'notes_published' => $source->notes_published,
                        'created_by' => $auteur,
                        'updated_by' => $auteur,
                    ]);
                }

                $notesExistantes = ESBTPNote::withTrashed()
                    ->where('evaluation_id', $cible->id)
                    ->get()
                    ->keyBy('etudiant_id');

                foreach ($plan['actions'] as $action) {
                    $note = $notesExistantes->get($action['etudiant_id']);
                    if ($note) {
                        if ($note->trashed()) {
                            $note->restore();
                        }
                        $note->note = $action['note'];
                        $note->is_absent = $action['is_absent'];
                        $note->matiere_id = $cible->matiere_id;
                        $note->classe_id = $cible->classe_id;
                        $note->semestre = $cible->periode;
                        $note->type_evaluation = $cible->type;
                        $note->annee_universitaire = $annee->name ?? $annee->libelle;
                        $note->updated_by = $auteur;
                        $note->save();
                        continue;
                    }

                    ESBTPNote::create([
                        'evaluation_id' => $cible->id,
                        'etudiant_id' => $action['etudiant_id'],
                        'matiere_id' => $cible->matiere_id,
                        'classe_id' => $cible->classe_id,
                        'semestre' => $cible->periode,
                        'type_evaluation' => $cible->type,
                        'annee_universitaire' => $annee->name ?? $annee->libelle,
                        'note' => $action['note'],
                        'is_absent' => $action['is_absent'],
                        'created_by' => $auteur,
                    ]);
                }
            }
        });

        Log::warning('CLI: duplication de notes ECUE exécutée', [
            'annee_universitaire_id' => $annee->id,
            'matiere_cible_id' => $cibleMatiere->id,
            'sources' => collect($plans)->pluck('source.id')->all(),
            'caller_user_id' => $auteur,
            'motif' => $v['motif'],
        ]);

        return $this->successResponse($resume, 'Duplication terminée. Régénérez les bulletins concernés pour refléter ces notes.');
    }

    /** @param array<int, array<string, mixed>> $plans */
    private function compter(array $plans, string $action): int
    {
        return collect($plans)->sum(fn ($p) => $p['actions']->where('action', $action)->count());
    }
}
