<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\Evaluations\ReglesCreationEvaluation;
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
use Illuminate\Validation\ValidationException;

/**
 * Crée des évaluations (examens, devoirs…) depuis le CLI.
 *
 * Simulation par défaut. Les règles de création (bornes, types, périodes propres
 * à la classe, remplissage, statut, durée) sont celles de l'écran : elles viennent
 * de App\Domain\Evaluations\ReglesCreationEvaluation. Un lot est atomique : une
 * ligne invalide, un doublon ou une garde du modèle annule tout. Une évaluation
 * déjà présente le même jour pour (année, classe, matière, type, période, titre)
 * est refusée.
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

        $auteur = (int) $request->user()->id;

        try {
            // Simulation et exécution passent par le même chemin d'écriture (gardes du
            // modèle comprises) ; la simulation annule la transaction à la fin.
            $creees = $this->ecrire($plan, $annee, $auteur, $dryRun);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Création refusée par les règles de l’évaluation : aucune évaluation n’a été créée.',
                ['errors' => $e->errors()],
                422
            );
        }

        $resume = [
            'dry_run' => $dryRun,
            'annee' => ['id' => (int) $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'lignes' => count($plan),
            'publiees' => count(array_filter($plan, fn ($p) => $p['is_published'])),
            'plan' => array_map(fn (array $p) => $this->versJson($p), $plan),
        ];

        if ($dryRun) {
            return $this->successResponse(
                $resume,
                'Simulation exacte : aucune écriture. Relancer avec dry_run=false et la confirmation pour créer.'
            );
        }

        Log::warning('CLI: création d’évaluations exécutée', [
            'annee_universitaire_id' => $annee->id,
            'evaluations' => $creees,
            'caller_user_id' => $auteur,
            'motif' => $v['motif'],
        ]);

        return $this->successResponse($resume + ['evaluations_creees' => $creees], 'Évaluations créées.');
    }

    /**
     * Valide chaque ligne et calcule le plan (objets Carbon). Retourne [plan, erreurs].
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function preparer(array $entries, ESBTPAnneeUniversitaire $annee): array
    {
        $plan = [];
        $errors = [];
        $vues = [];

        foreach (array_values($entries) as $index => $entry) {
            // Identifiant vérifié avant toute recherche : un tableau ou une chaîne
            // non numérique doit donner un 422, pas une erreur de type.
            $classeId = $entry['classe_id'] ?? null;
            $classe = is_int($classeId) || (is_string($classeId) && ctype_digit($classeId))
                ? ESBTPClasse::find((int) $classeId)
                : null;

            $entry['periode'] = ReglesCreationEvaluation::normaliserPeriode($entry['periode'] ?? null);

            $validator = Validator::make($entry, array_merge(
                ReglesCreationEvaluation::champs($classe),
                [
                    'classe_id' => 'required|integer|exists:esbtp_classes,id',
                    'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
                    'titre' => 'required|string|max:255',
                    'description' => 'nullable|string|max:1000',
                    'date_evaluation' => 'required|date_format:Y-m-d',
                    'heure_debut' => 'required|date_format:H:i',
                    'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                    'is_published' => 'nullable|boolean',
                ]
            ));

            if ($validator->fails()) {
                $errors[] = ['ligne' => $index, 'erreur' => $validator->errors()->all()];

                continue;
            }

            // Un titre « régularisation … » désactive le contrôle enseignant LMD de
            // l'observateur : le CLI ne crée pas ce type d'évaluation (même règle).
            $titreNormalise = mb_strtolower(trim((string) $entry['titre']), 'UTF-8');
            if (str_starts_with($titreNormalise, 'régularisation ') || str_starts_with($titreNormalise, 'regularisation ')) {
                $errors[] = ['ligne' => $index, 'erreur' => 'Titre réservé aux régularisations : création refusée par le CLI.'];

                continue;
            }

            $debut = Carbon::createFromFormat('Y-m-d H:i', $entry['date_evaluation'].' '.$entry['heure_debut']);
            $fin = Carbon::createFromFormat('Y-m-d H:i', $entry['date_evaluation'].' '.$entry['heure_fin']);
            $cle = implode('|', [
                $entry['classe_id'], $entry['matiere_id'], $entry['type'], $entry['periode'], $entry['titre'], $debut->toDateString(),
            ]);

            if (isset($vues[$cle])) {
                $errors[] = ['ligne' => $index, 'erreur' => 'Doublon dans le lot (ligne '.$vues[$cle].').'];

                continue;
            }
            $vues[$cle] = $index;

            $existante = $this->existante($entry, $debut, $annee);

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
                'fin' => $fin,
                'duree_saisie' => null,
                'bareme' => (float) $entry['bareme'],
                'coefficient' => (float) $entry['coefficient'],
                'is_published' => (bool) ($entry['is_published'] ?? false),
            ];
        }

        return [$plan, $errors];
    }

    /**
     * Écrit le plan dans une transaction. Pour chaque ligne : verrou sur la classe
     * (les créations concurrentes d'une même classe se succèdent), doublon revérifié
     * sous ce verrou, puis remplissage par la règle partagée. En simulation, la
     * transaction est annulée à la fin.
     *
     * @return array<int, int> identifiants créés (vide en simulation)
     */
    private function ecrire(array $plan, ESBTPAnneeUniversitaire $annee, int $auteur, bool $simulation): array
    {
        $creees = [];

        DB::beginTransaction();
        try {
            foreach ($plan as $ligne) {
                ESBTPClasse::query()->whereKey($ligne['classe_id'])->lockForUpdate()->first();

                $existante = $this->existante([
                    'classe_id' => $ligne['classe_id'],
                    'matiere_id' => $ligne['matiere_id'],
                    'type' => $ligne['type'],
                    'periode' => $ligne['periode'],
                    'titre' => $ligne['titre'],
                ], $ligne['debut'], $annee);

                if ($existante) {
                    throw ValidationException::withMessages([
                        'ligne' => ['Évaluation déjà existante (#'.$existante->id.') pour la ligne '.$ligne['ligne'].'.'],
                    ]);
                }

                $evaluation = new ESBTPEvaluation;
                ReglesCreationEvaluation::remplir($evaluation, $ligne, (int) $annee->id, $auteur);
                $evaluation->save();
                $creees[] = (int) $evaluation->id;
            }

            if ($simulation) {
                DB::rollBack();

                return [];
            }

            DB::commit();

            return $creees;
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Évaluation vivante déjà présente pour le même (année, classe, matière,
     * type, période, titre, JOUR). Deux devoirs de même titre à des dates
     * différentes restent légitimes.
     */
    private function existante(array $entry, Carbon $debut, ESBTPAnneeUniversitaire $annee): ?ESBTPEvaluation
    {
        return ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $annee->id)
            ->where('classe_id', $entry['classe_id'])
            ->where('matiere_id', $entry['matiere_id'])
            ->where('type', $entry['type'])
            ->where('periode', $entry['periode'])
            ->where('titre', $entry['titre'])
            ->whereDate('date_evaluation', $debut->toDateString())
            ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED))
            ->first();
    }

    /**
     * Forme JSON d'une ligne du plan (les dates sont rendues en texte).
     *
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function versJson(array $p): array
    {
        return [
            'ligne' => $p['ligne'],
            'classe_id' => $p['classe_id'],
            'matiere_id' => $p['matiere_id'],
            'titre' => $p['titre'],
            'type' => $p['type'],
            'periode' => $p['periode'],
            'debut' => $p['debut']->format('Y-m-d H:i'),
            'duree_minutes' => ReglesCreationEvaluation::dureeMinutes($p['debut'], $p['fin']),
            'bareme' => $p['bareme'],
            'coefficient' => $p['coefficient'],
            'is_published' => $p['is_published'],
        ];
    }
}
