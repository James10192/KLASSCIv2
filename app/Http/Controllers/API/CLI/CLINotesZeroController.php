<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Poser une note aux eleves qu'une evaluation a oublies.
 *
 * Le bulletin construit sa liste de matieres a partir des notes de l'eleve.
 * Celui qui n'a aucune note dans une matiere n'y a donc pas de ligne du tout :
 * la matiere disparait de son bulletin alors qu'elle figure sur celui de son
 * voisin de classe. Le trou ne se voit nulle part — ni comme un zero, ni comme
 * une absence, ni comme un oubli de saisie.
 *
 * Poser la note manquante remet les deux bulletins face a face.
 *
 * Par defaut, on ne touche QU'AUX evaluations deja notees : une evaluation ou
 * personne n'a de note n'est pas une evaluation ratee, c'est une evaluation
 * que l'enseignant n'a pas encore saisie. Y poser des zeros donnerait un zero
 * a toute la classe dans une matiere qui n'a pas encore ete corrigee.
 */
class CLINotesZeroController extends BaseApiController
{
    /**
     * POST /api/cli/evaluations/noter-les-non-notes
     */
    public function noter(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $valide = $request->validate([
            'evaluation_ids' => 'nullable|array|max:500',
            'evaluation_ids.*' => 'integer|min:1',
            'annee_id' => 'nullable|integer',
            'classe_id' => 'nullable|integer',
            'periode' => 'nullable|string|in:semestre1,semestre2',
            'systeme' => 'nullable|string|in:BTS,LMD',
            'niveau_year' => 'nullable|integer|min:1|max:10',
            'note' => 'nullable|numeric|min:0',
            'seulement_evaluations_deja_notees' => 'nullable|boolean',
            'dry_run' => 'nullable|boolean',
        ]);

        $annee = isset($valide['annee_id'])
            ? ESBTPAnneeUniversitaire::find($valide['annee_id'])
            : $this->getAnneeCouraante();

        if (! $annee) {
            return $this->errorResponse(
                'Aucune annee universitaire courante configuree.',
                ['code' => 'NO_ACADEMIC_YEAR'],
                422
            );
        }

        $simulation = $request->boolean('dry_run', true);
        $seulementNotees = $request->boolean('seulement_evaluations_deja_notees', true);
        $valeur = isset($valide['note']) ? (float) $valide['note'] : 0.0;

        $evaluations = $this->evaluationsDuPerimetre($valide, (int) $annee->id);

        if ($evaluations->isEmpty()) {
            return $this->successResponse([
                'dry_run' => $simulation,
                'evaluations' => [],
                'total_notes' => 0,
            ], 'Aucune evaluation dans ce perimetre.');
        }

        $inscrits = $this->inscritsParClasse($evaluations->pluck('classe_id')->unique(), (int) $annee->id);

        $notesExistantes = ESBTPNote::whereIn('evaluation_id', $evaluations->pluck('id'))
            ->get(['evaluation_id', 'etudiant_id'])
            ->groupBy('evaluation_id')
            ->map(fn ($lignes) => $lignes->pluck('etudiant_id')->map('intval')->all());

        $lignes = [];
        $aCreer = [];

        foreach ($evaluations as $evaluation) {
            $dejaNotes = $notesExistantes->get($evaluation->id, []);
            $classeInscrits = $inscrits->get((int) $evaluation->classe_id, collect())->all();
            $manquants = array_values(array_diff($classeInscrits, $dejaNotes));

            $ignoree = $seulementNotees && $dejaNotes === [];

            $lignes[] = [
                'evaluation_id' => (int) $evaluation->id,
                'titre' => $evaluation->titre,
                'classe' => $evaluation->classe->name ?? null,
                'matiere' => $evaluation->matiere->name ?? null,
                'periode' => $evaluation->periode,
                'date' => optional($evaluation->date_evaluation)->toDateString(),
                'inscrits' => count($classeInscrits),
                'deja_notes' => count($dejaNotes),
                'a_noter' => $ignoree ? 0 : count($manquants),
                'ignoree' => $ignoree,
                'motif_ignoree' => $ignoree ? 'aucune note saisie : evaluation non encore corrigee' : null,
            ];

            if (! $ignoree && $manquants !== []) {
                $aCreer[(int) $evaluation->id] = ['evaluation' => $evaluation, 'etudiants' => $manquants];
            }
        }

        $total = array_sum(array_column($lignes, 'a_noter'));

        if ($simulation) {
            return $this->successResponse([
                'dry_run' => true,
                'annee' => ['id' => $annee->id, 'name' => $annee->name ?? $annee->libelle],
                'note_posee' => $valeur,
                'seulement_evaluations_deja_notees' => $seulementNotees,
                'evaluations' => $lignes,
                'total_notes' => $total,
            ], 'Simulation : rien n a ete ecrit. Relancer avec dry_run=false pour appliquer.');
        }

        $creees = 0;
        $auteur = ((int) ($request->user()->id ?? 0)) ?: null;

        DB::transaction(function () use ($aCreer, $valeur, $auteur, $annee, &$creees) {
            foreach ($aCreer as $lot) {
                /** @var ESBTPEvaluation $evaluation */
                $evaluation = $lot['evaluation'];

                foreach ($lot['etudiants'] as $etudiantId) {
                    ESBTPNote::create([
                        'evaluation_id' => (int) $evaluation->id,
                        'etudiant_id' => (int) $etudiantId,
                        'matiere_id' => $evaluation->matiere_id,
                        // Denormalisees : elles suivent l'evaluation, jamais la
                        // classe courante de l'eleve. Une note appartient au
                        // cours ou elle a ete prise.
                        'classe_id' => $evaluation->classe_id,
                        'semestre' => $evaluation->periode,
                        'type_evaluation' => $evaluation->type,
                        'annee_universitaire' => $annee->name ?? $annee->libelle,
                        'note' => $valeur,
                        // Un zero pose ici est une note, pas une absence : rien
                        // ne dit que l'eleve n'etait pas la, seulement que sa
                        // note n'a jamais ete saisie.
                        'is_absent' => false,
                        'created_by' => $auteur,
                    ]);
                    $creees++;
                }
            }
        });

        Log::warning('CLI: notes posees sur les eleves non notes', [
            'annee_id' => $annee->id,
            'note' => $valeur,
            'notes_creees' => $creees,
            'evaluations' => array_keys($aCreer),
        ]);

        return $this->successResponse([
            'dry_run' => false,
            'annee' => ['id' => $annee->id, 'name' => $annee->name ?? $annee->libelle],
            'note_posee' => $valeur,
            'evaluations' => $lignes,
            'total_notes' => $creees,
        ], $creees.' note(s) creee(s) a '.$valeur.'.');
    }

    /**
     * @param  array<string, mixed>  $valide
     * @return Collection<int, ESBTPEvaluation>
     */
    private function evaluationsDuPerimetre(array $valide, int $anneeId): Collection
    {
        return ESBTPEvaluation::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
            })
            ->when(! empty($valide['evaluation_ids']), fn ($q) => $q->whereIn('id', $valide['evaluation_ids']))
            ->when(! empty($valide['classe_id']), fn ($q) => $q->where('classe_id', (int) $valide['classe_id']))
            ->when(! empty($valide['periode']), fn ($q) => $q->whereIn('periode', ESBTPEvaluation::aliasDePeriode($valide['periode'])))
            ->when(! empty($valide['systeme']) || ! empty($valide['niveau_year']), function ($q) use ($valide) {
                $q->whereHas('classe', function ($c) use ($valide) {
                    if (! empty($valide['systeme'])) {
                        $c->where('systeme_academique', $valide['systeme']);
                    }
                    if (! empty($valide['niveau_year'])) {
                        $c->whereHas('niveau', fn ($n) => $n->where('year', (int) $valide['niveau_year']));
                    }
                });
            })
            ->with(['classe:id,name', 'matiere:id,name'])
            ->orderBy('classe_id')
            ->orderBy('matiere_id')
            ->get();
    }

    /**
     * Les eleves reellement presents dans chaque classe pour cette annee.
     *
     * Memes criteres que partout ailleurs : inscription active et dossier mene
     * a son terme. Un dossier en cours ne doit pas recevoir de note.
     *
     * @param  Collection<int, int>  $classeIds
     * @return Collection<int, Collection<int, int>>
     */
    private function inscritsParClasse(Collection $classeIds, int $anneeId): Collection
    {
        return ESBTPInscription::query()
            ->whereIn('classe_id', $classeIds)
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get(['classe_id', 'etudiant_id'])
            ->groupBy('classe_id')
            ->map(fn ($lignes) => $lignes->pluck('etudiant_id')->map('intval')->unique()->values());
    }
}
