<?php

declare(strict_types=1);

namespace App\Domain\Examens;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPExamenAnonymat;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Relier un examen planifié à la saisie de ses notes.
 *
 * Avant : l'examen n'avait aucun lien vers une évaluation, son verrou
 * (`notes_locked`) n'était lu par personne, et « anonyme » se limitait à une
 * case cochée — l'écran le disait lui-même : « Sans numéro d'anonymat : la
 * saisie des notes affiche les noms ».
 *
 * Maintenant :
 *  - `ouvrir()` crée (une fois) l'évaluation de l'examen et la lie ;
 *  - le verrou est lu par `GradeSheetNoteMutationGuard`, que toute écriture de
 *    note traverse ;
 *  - un examen anonyme reçoit un numéro par copie, dans un ordre tiré au sort
 *    (un ordre alphabétique rendrait le numéro devinable), et la saisie affiche
 *    les numéros tant que l'anonymat n'est pas levé — levée datée et nominative.
 */
final class FeuilleDeNotesDeLExamen
{
    public function ouvrir(ESBTPExamenPlanifie $examen, User $par): ESBTPEvaluation
    {
        if ($examen->evaluation_id && ($evaluation = ESBTPEvaluation::find($examen->evaluation_id))) {
            return $evaluation;
        }

        if (! $examen->classe_id || ! $examen->matiere_id) {
            throw ValidationException::withMessages([
                'examen' => 'L’examen doit viser une classe et un élément constitutif (ECUE) pour ouvrir sa feuille de notes.',
            ]);
        }

        return DB::transaction(function () use ($examen, $par): ESBTPEvaluation {
            $locked = ESBTPExamenPlanifie::query()->lockForUpdate()->findOrFail($examen->id);
            if ($locked->evaluation_id && ($existante = ESBTPEvaluation::find($locked->evaluation_id))) {
                return $existante;
            }

            $evaluation = new ESBTPEvaluation;
            $evaluation->titre = $locked->titre ?: 'Examen';
            $evaluation->type = ESBTPEvaluation::TYPE_EXAMEN;
            $evaluation->date_evaluation = $locked->date_debut ?? now();
            $evaluation->duree_minutes = (int) ($locked->duree_minutes ?: 120);
            $evaluation->coefficient = (float) ($locked->coefficient ?: 1);
            $evaluation->bareme = (float) ($locked->bareme ?: 20);
            $evaluation->classe_id = $locked->classe_id;
            $evaluation->matiere_id = $locked->matiere_id;
            $evaluation->annee_universitaire_id = $locked->annee_universitaire_id;
            $evaluation->periode = $locked->semestre ? 'semestre'.$locked->semestre : 'semestre1';
            $evaluation->created_by = $par->id;
            $evaluation->is_published = 1;
            $evaluation->status = $evaluation->determineAutomaticStatus(null, false);
            $evaluation->save();

            $locked->forceFill(['evaluation_id' => $evaluation->id, 'updated_by' => $par->id])->save();

            if ($locked->is_anonymous) {
                $this->attribuerNumeros($locked);
            }

            return $evaluation;
        });
    }

    /**
     * Un numéro par étudiant de la cohorte. Idempotent : un étudiant déjà
     * numéroté garde son numéro, un inscrit tardif reçoit le suivant.
     *
     * Toujours sous le verrou de l'examen : deux correcteurs qui ouvrent la
     * feuille en même temps ne doivent pas tirer le même numéro (unicité
     * `examen_planifie_id` + `numero`). Le suivant part du plus grand numéro
     * déjà tiré, pas du nombre de lignes.
     *
     * @return int le nombre de numéros créés
     */
    public function attribuerNumeros(ESBTPExamenPlanifie $examen): int
    {
        return DB::transaction(function () use ($examen): int {
            ESBTPExamenPlanifie::query()->lockForUpdate()->findOrFail($examen->id);

            $cohorte = ESBTPInscription::query()
                ->where('classe_id', $examen->classe_id)
                ->where('annee_universitaire_id', $examen->annee_universitaire_id)
                ->where('status', 'active')
                ->pluck('etudiant_id')
                ->unique();

            $existants = ESBTPExamenAnonymat::where('examen_planifie_id', $examen->id)->get(['etudiant_id', 'numero']);
            $aNumeroter = $cohorte->diff($existants->pluck('etudiant_id'))->shuffle()->values();
            $suivant = (int) $existants->map(fn ($a) => (int) substr((string) strrchr((string) $a->numero, '-'), 1))->max() + 1;

            foreach ($aNumeroter as $i => $etudiantId) {
                ESBTPExamenAnonymat::create([
                    'examen_planifie_id' => $examen->id,
                    'etudiant_id' => $etudiantId,
                    'numero' => sprintf('%s-%03d', $this->prefixe($examen), $suivant + $i),
                ]);
            }

            return $aNumeroter->count();
        });
    }

    /**
     * Les évaluations, parmi celles données, dont les copies sont encore
     * anonymes : examen anonyme dont l'anonymat n'est pas levé. Aucun écran
     * ne doit y montrer un nom à côté d'une note.
     *
     * @param  iterable<int>  $evaluationIds
     * @return Collection<int, int>
     */
    public function evaluationsSousAnonymat(iterable $evaluationIds): Collection
    {
        return ESBTPExamenPlanifie::query()
            ->whereIn('evaluation_id', collect($evaluationIds)->all())
            ->where('is_anonymous', true)
            ->whereNull('anonymat_leve_at')
            ->pluck('evaluation_id')
            ->map(fn ($id) => (int) $id);
    }

    /**
     * Les numéros à afficher à la place des noms, ou null si la saisie peut
     * montrer les noms (examen non anonyme, ou anonymat levé).
     *
     * @return Collection<int, string>|null etudiant_id => numéro
     */
    public function numerosPourLaSaisie(ESBTPEvaluation $evaluation): ?Collection
    {
        $examen = ESBTPExamenPlanifie::where('evaluation_id', $evaluation->id)->first();
        if (! $examen || ! $examen->is_anonymous || $examen->anonymat_leve_at) {
            return null;
        }

        $numeros = ESBTPExamenAnonymat::where('examen_planifie_id', $examen->id)->pluck('numero', 'etudiant_id');
        $cohorte = ESBTPInscription::query()
            ->where('classe_id', $examen->classe_id)
            ->where('annee_universitaire_id', $examen->annee_universitaire_id)
            ->where('status', 'active')
            ->pluck('etudiant_id');

        // Un inscrit arrivé après l'ouverture de la feuille reçoit son numéro,
        // sous le verrou de l'examen ; sinon la lecture n'écrit rien.
        if ($cohorte->diff($numeros->keys())->isNotEmpty()) {
            $this->attribuerNumeros($examen);
            $numeros = ESBTPExamenAnonymat::where('examen_planifie_id', $examen->id)->pluck('numero', 'etudiant_id');
        }

        return $numeros;
    }

    public function leverAnonymat(ESBTPExamenPlanifie $examen, User $par): void
    {
        if (! $examen->is_anonymous || $examen->anonymat_leve_at) {
            throw ValidationException::withMessages(['examen' => 'L’anonymat de cet examen n’est pas à lever.']);
        }

        $examen->forceFill(['anonymat_leve_at' => now(), 'anonymat_leve_par' => $par->id])->save();

        // En `warning` : un acte rare, que la production ne filtre pas.
        Log::warning('Anonymat levé sur un examen.', ['examen_id' => $examen->id, 'par' => $par->id]);
    }

    private function prefixe(ESBTPExamenPlanifie $examen): string
    {
        return 'E'.$examen->id;
    }
}
