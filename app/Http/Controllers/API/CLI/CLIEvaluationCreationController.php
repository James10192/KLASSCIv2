<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Crée des évaluations (examens, devoirs…) depuis le CLI.
 *
 * Simulation par défaut. Chaque ligne reprend les règles de l'écran de création
 * (barème, coefficient, types, périodes propres à la classe, heures). Un lot
 * est atomique : une ligne invalide ou un doublon annule tout. Une évaluation
 * déjà existante pour le même (année, classe, matière, type, période, titre) est
 * refusée : on ne crée jamais deux fois la même évaluation.
 */
class CLIEvaluationCreationController extends BaseApiController
{
    private const MAX_LIGNES = 500;

    private const CONFIRMATION = 'CREER_LES_EVALUATIONS';

    public function creer(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $v = $request->validate([
            'annee_universitaire_id' => 'required|integer|exists:esbtp_annee_universitaires,id',
            'motif' => 'required|string|min:10|max:500',
            'entries' => 'required|array|min:1|max:'.self::MAX_LIGNES,
            'entries.*' => 'required|array',
            'dry_run' => 'nullable|boolean',
            'confirmation' => 'nullable|string',
        ]);

        $dryRun = (bool) ($v['dry_run'] ?? true);
        if (! $dryRun && ($v['confirmation'] ?? null) !== self::CONFIRMATION) {
            return $this->errorResponse(
                'Confirmation invalide. Envoyez confirmation='.self::CONFIRMATION.' pour exécuter.',
                [],
                422
            );
        }

        $annee = ESBTPAnneeUniversitaire::findOrFail((int) $v['annee_universitaire_id']);

        [$plan, $errors] = $this->preparer($v['entries'], $annee);

        if ($errors !== []) {
            return $this->errorResponse(
                'Création refusée : corrigez les lignes signalées. Aucune évaluation n’a été créée.',
                ['errors' => $errors],
                422
            );
        }

        $resume = [
            'dry_run' => $dryRun,
            'annee' => ['id' => (int) $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'lignes' => count($plan),
            'publiees' => count(array_filter($plan, fn ($p) => $p['is_published'])),
        ];

        if ($dryRun) {
            return $this->successResponse(
                $resume + ['plan' => $plan],
                'Simulation : aucune écriture. Relancer avec dry_run=false et la confirmation pour créer.'
            );
        }

        $auteur = (int) $request->user()->id;
        $creees = [];
        DB::transaction(function () use ($plan, $annee, $auteur, &$creees): void {
            foreach ($plan as $ligne) {
                $evaluation = new ESBTPEvaluation;
                $evaluation->titre = $ligne['titre'];
                $evaluation->description = $ligne['description'];
                $evaluation->type = $ligne['type'];
                $evaluation->date_evaluation = Carbon::createFromFormat('Y-m-d H:i', $ligne['debut']);
                $evaluation->coefficient = $ligne['coefficient'];
                $evaluation->bareme = $ligne['bareme'];
                $evaluation->duree_minutes = $ligne['duree_minutes'];
                $evaluation->classe_id = $ligne['classe_id'];
                $evaluation->matiere_id = $ligne['matiere_id'];
                $evaluation->periode = $ligne['periode'];
                $evaluation->annee_universitaire_id = $annee->id;
                $evaluation->created_by = $auteur;
                $evaluation->is_published = $ligne['is_published'];
                $evaluation->status = $ligne['is_published']
                    ? $evaluation->determineAutomaticStatus(null, false)
                    : ESBTPEvaluation::STATUS_DRAFT;
                $evaluation->save();
                $creees[] = (int) $evaluation->id;
            }
        });

        Log::warning('CLI: création d’évaluations exécutée', [
            'annee_universitaire_id' => $annee->id,
            'evaluations' => $creees,
            'caller_user_id' => $auteur,
            'motif' => $v['motif'],
        ]);

        return $this->successResponse($resume + ['evaluations_creees' => $creees], 'Évaluations créées.');
    }

    /**
     * Valide chaque ligne et calcule le plan. Retourne [plan, erreurs].
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function preparer(array $entries, ESBTPAnneeUniversitaire $annee): array
    {
        $plan = [];
        $errors = [];
        $vues = [];

        foreach (array_values($entries) as $index => $entry) {
            $classe = ESBTPClasse::find($entry['classe_id'] ?? null);

            $validator = Validator::make($entry, [
                'classe_id' => 'required|integer|exists:esbtp_classes,id',
                'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
                'titre' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'type' => ['required', 'string', Rule::in(ESBTPEvaluation::typesSaisissables())],
                'periode' => ['required', 'string', Rule::in(array_keys(ESBTPEvaluation::periodesPourClasse($classe)))],
                'date_evaluation' => 'required|date_format:Y-m-d',
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'bareme' => 'required|numeric|min:'.ESBTPEvaluation::BAREME_MIN.'|max:'.ESBTPEvaluation::BAREME_MAX,
                'coefficient' => 'required|numeric|min:'.ESBTPEvaluation::COEFFICIENT_MIN.'|max:'.ESBTPEvaluation::COEFFICIENT_MAX,
                'is_published' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                $errors[] = ['ligne' => $index, 'erreur' => $validator->errors()->all()];

                continue;
            }

            $debut = Carbon::createFromFormat('Y-m-d H:i', $entry['date_evaluation'].' '.$entry['heure_debut']);
            $fin = Carbon::createFromFormat('Y-m-d H:i', $entry['date_evaluation'].' '.$entry['heure_fin']);
            $cle = implode('|', [$entry['classe_id'], $entry['matiere_id'], $entry['type'], $entry['periode'], $entry['titre']]);

            if (isset($vues[$cle])) {
                $errors[] = ['ligne' => $index, 'erreur' => 'Doublon dans le lot (ligne '.$vues[$cle].').'];

                continue;
            }
            $vues[$cle] = $index;

            $existante = ESBTPEvaluation::query()
                ->where('annee_universitaire_id', $annee->id)
                ->where('classe_id', $entry['classe_id'])
                ->where('matiere_id', $entry['matiere_id'])
                ->where('type', $entry['type'])
                ->where('periode', $entry['periode'])
                ->where('titre', $entry['titre'])
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED))
                ->first();

            if ($existante) {
                $errors[] = ['ligne' => $index, 'erreur' => 'Évaluation déjà existante (#'.$existante->id.') : rien n’est recréé.'];

                continue;
            }

            $plan[] = [
                'ligne' => $index,
                'classe_id' => (int) $entry['classe_id'],
                'matiere_id' => (int) $entry['matiere_id'],
                'titre' => $entry['titre'],
                'description' => $entry['description'] ?? null,
                'type' => $entry['type'],
                'periode' => $entry['periode'],
                'debut' => $debut,
                'duree_minutes' => (int) $debut->diffInMinutes($fin),
                'bareme' => (float) $entry['bareme'],
                'coefficient' => (float) $entry['coefficient'],
                'is_published' => (bool) ($entry['is_published'] ?? false),
            ];
        }

        // Un plan ne sert qu'à voir la simulation : on le rend sérialisable.
        $plan = array_map(fn (array $p) => $p + ['debut' => $p['debut']->format('Y-m-d H:i')], $plan);

        return [$plan, $errors];
    }
}
