<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Importe un relevé de notes déjà vérifié dans des évaluations existantes.
 *
 * L'import est atomique : une inscription, une évaluation ou une note ambiguë
 * invalide le lot entier. Les évaluations ne sont jamais créées/modifiées et une
 * même paire étudiant-évaluation n'est jamais dupliquée.
 */
class CLINotesImportController extends BaseApiController
{
    private const MAX_LIGNES = 1500;

    public function importer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'motif' => 'required|string|min:10|max:500',
            'entries' => 'required|array|min:1|max:'.self::MAX_LIGNES,
            'entries.*.evaluation_id' => 'required|integer|distinct',
            'entries.*.etudiant_id' => 'required|integer|exists:esbtp_etudiants,id',
            'entries.*.note' => 'required|numeric|min:0|max:100',
            'dry_run' => 'nullable|boolean',
            'confirmation' => 'nullable|string',
        ]);

        $dryRun = (bool) ($v['dry_run'] ?? true);
        if (! $dryRun && ($v['confirmation'] ?? null) !== 'IMPORTER_LES_NOTES') {
            return $this->errorResponse(
                'Confirmation invalide. Envoyez confirmation=IMPORTER_LES_NOTES pour exécuter.',
                [],
                422
            );
        }

        $annee = ESBTPAnneeUniversitaire::findOrFail((int) $v['annee_universitaire_id']);
        $evaluationIds = collect($v['entries'])->pluck('evaluation_id')->map(fn ($id) => (int) $id)->unique()->values();
        $studentIds = collect($v['entries'])->pluck('etudiant_id')->map(fn ($id) => (int) $id)->unique()->values();

        $evaluations = ESBTPEvaluation::query()
            ->whereIn('id', $evaluationIds)
            ->where('annee_universitaire_id', $annee->id)
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED))
            ->get()
            ->keyBy('id');

        $inscriptions = ESBTPInscription::query()
            ->whereIn('etudiant_id', $studentIds)
            ->where('annee_universitaire_id', $annee->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereNull('deleted_at')
            ->get(['etudiant_id', 'classe_id'])
            ->mapWithKeys(fn (ESBTPInscription $i) => [(int) $i->etudiant_id.':'.(int) $i->classe_id => true]);

        $notes = ESBTPNote::withTrashed()
            ->whereIn('evaluation_id', $evaluationIds)
            ->whereIn('etudiant_id', $studentIds)
            ->get()
            ->groupBy(fn (ESBTPNote $n) => (int) $n->evaluation_id.':'.(int) $n->etudiant_id);

        $errors = [];
        $plan = [];
        $seen = [];

        foreach ($v['entries'] as $entry) {
            $evaluationId = (int) $entry['evaluation_id'];
            $studentId = (int) $entry['etudiant_id'];
            $key = $evaluationId.':'.$studentId;

            if (isset($seen[$key])) {
                $errors[] = ['evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'erreur' => 'La même note apparaît plusieurs fois dans le fichier.'];
                continue;
            }
            $seen[$key] = true;

            $evaluation = $evaluations->get($evaluationId);
            if (! $evaluation) {
                $errors[] = ['evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'erreur' => 'Évaluation absente, hors année cible ou annulée.'];
                continue;
            }
            if (! $inscriptions->has($studentId.':'.(int) $evaluation->classe_id)) {
                $errors[] = ['evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'erreur' => 'Étudiant non inscrit activement dans la classe de cette évaluation pour cette année.'];
                continue;
            }

            $note = (float) $entry['note'];
            $bareme = (float) ($evaluation->bareme ?: 20);
            if ($note > $bareme) {
                $errors[] = ['evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'erreur' => 'Note supérieure au barème de l’évaluation ('.$bareme.').'];
                continue;
            }

            $existantes = $notes->get($key, collect());
            if ($existantes->count() > 1) {
                $errors[] = ['evaluation_id' => $evaluationId, 'etudiant_id' => $studentId, 'erreur' => 'Plusieurs notes existent déjà : correction manuelle requise.'];
                continue;
            }

            /** @var ESBTPNote|null $existante */
            $existante = $existantes->first();
            $identique = $existante
                && ! $existante->trashed()
                && ! (bool) $existante->is_absent
                && (float) $existante->note === $note;

            $plan[] = [
                'evaluation_id' => $evaluationId,
                'etudiant_id' => $studentId,
                'note' => $note,
                'action' => $identique ? 'inchangée' : ($existante ? 'mise_a_jour' : 'création'),
            ];
        }

        if ($errors !== []) {
            return $this->errorResponse(
                'Import refusé : corrigez les lignes signalées. Aucune note n’a été modifiée.',
                ['errors' => $errors],
                422
            );
        }

        $resume = [
            'dry_run' => $dryRun,
            'annee' => ['id' => (int) $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'lignes' => count($plan),
            'creations' => count(array_filter($plan, fn ($p) => $p['action'] === 'création')),
            'mises_a_jour' => count(array_filter($plan, fn ($p) => $p['action'] === 'mise_a_jour')),
            'inchangees' => count(array_filter($plan, fn ($p) => $p['action'] === 'inchangée')),
            'evaluations' => $evaluationIds->count(),
            'etudiants' => $studentIds->count(),
        ];

        if ($dryRun) {
            return $this->successResponse($resume + ['plan' => $plan], 'Simulation : aucune écriture. Relancer avec dry_run=false et la confirmation pour appliquer.');
        }

        $auteur = (int) $request->user()->id;
        DB::transaction(function () use ($plan, $notes, $evaluations, $annee, $auteur): void {
            foreach ($plan as $ligne) {
                if ($ligne['action'] === 'inchangée') {
                    continue;
                }

                /** @var ESBTPEvaluation $evaluation */
                $evaluation = $evaluations->get($ligne['evaluation_id']);
                $key = $ligne['evaluation_id'].':'.$ligne['etudiant_id'];
                /** @var ESBTPNote|null $note */
                $note = $notes->get($key, collect())->first();

                if ($note) {
                    if ($note->trashed()) {
                        $note->restore();
                    }
                    $note->note = $ligne['note'];
                    $note->is_absent = false;
                    $note->matiere_id = $evaluation->matiere_id;
                    $note->classe_id = $evaluation->classe_id;
                    $note->semestre = $evaluation->periode;
                    $note->type_evaluation = $evaluation->type;
                    $note->annee_universitaire = $annee->name ?? $annee->libelle;
                    $note->updated_by = $auteur;
                    $note->save();

                    continue;
                }

                ESBTPNote::create([
                    'evaluation_id' => $evaluation->id,
                    'etudiant_id' => $ligne['etudiant_id'],
                    'matiere_id' => $evaluation->matiere_id,
                    'classe_id' => $evaluation->classe_id,
                    'semestre' => $evaluation->periode,
                    'type_evaluation' => $evaluation->type,
                    'annee_universitaire' => $annee->name ?? $annee->libelle,
                    'note' => $ligne['note'],
                    'is_absent' => false,
                    'created_by' => $auteur,
                ]);
            }
        });

        Log::warning('CLI: import autoritatif de notes exécuté', [
            'annee_universitaire_id' => $annee->id,
            'lignes' => $resume['lignes'],
            'creations' => $resume['creations'],
            'mises_a_jour' => $resume['mises_a_jour'],
            'evaluations' => $evaluationIds->all(),
            'caller_user_id' => $auteur,
            'motif' => $v['motif'],
        ]);

        return $this->successResponse($resume, 'Import terminé. Régénérez les bulletins concernés pour refléter ces notes.');
    }
}
