<?php

namespace App\Services\Reinscription;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use Illuminate\Support\Collection;

/**
 * Les notes d'une promotion entiere, telles que la decision de reinscription
 * les lit — sans construire un modele Eloquent par note.
 *
 * La liste de reinscription et ses compteurs analysent TOUTE la promotion a
 * chaque ouverture : sur esbtp-abidjan, 2000 etudiants et pres de 50 000
 * notes. Hydrater 50 000 `ESBTPNote` et leurs relations coutait a lui seul
 * pres de deux secondes ; lire les memes lignes brutes en coute cinquante
 * millisecondes.
 *
 * Chaque note rendue est un objet leger qui repond aux SEULES questions que
 * pose la decision, avec les memes valeurs que le modele :
 *
 * - `id`, `etudiant_id`, `matiere_id`, `evaluation_id`, `note` : la colonne
 *   brute, telle que le modele la rend (aucune n'est castee) ;
 * - `matiere` : la matiere de la note, effacee en douceur comprise, ou `null` ;
 * - `evaluation` : `null` si l'evaluation est absente ou effacee, sinon un
 *   objet dont `matiere` est la matiere de l'evaluation.
 *
 * Les lignes viennent du constructeur de requetes du modele (`toBase()`) :
 * archivage et suppression douce s'appliquent comme pour `ESBTPNote::where()`.
 */
final class NotesDeLaPromotion
{
    /**
     * @param  array<int|string>  $etudiantIds
     * @return Collection<int, Collection<int, object>> notes par etudiant, dans l'ordre des identifiants
     */
    public function pour(array $etudiantIds, ?string $anneeUniversitaire): Collection
    {
        $lignes = ESBTPNote::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->where('annee_universitaire', $anneeUniversitaire)
            ->orderBy('id')
            ->toBase()
            ->get(['id', 'etudiant_id', 'matiere_id', 'evaluation_id', 'note']);

        $matiereDesEvaluations = ESBTPEvaluation::query()
            ->whereIn('id', $lignes->pluck('evaluation_id')->filter()->unique()->values())
            ->toBase()
            ->pluck('matiere_id', 'id');

        // `withTrashed()`, comme les relations de l'analyse : une matiere effacee
        // en douceur doit rester visible du filtre BTS / LMD.
        $matieres = ESBTPMatiere::withTrashed()
            ->whereIn('id', $lignes->pluck('matiere_id')->merge($matiereDesEvaluations->values())->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        return $lignes
            ->map(fn (object $ligne) => $this->note($ligne, $matiereDesEvaluations, $matieres))
            ->groupBy('etudiant_id');
    }

    private function note(object $ligne, Collection $matiereDesEvaluations, Collection $matieres): object
    {
        $evaluation = null;
        if ($ligne->evaluation_id !== null && $matiereDesEvaluations->has($ligne->evaluation_id)) {
            $matiereId = $matiereDesEvaluations->get($ligne->evaluation_id);
            $evaluation = (object) ['matiere' => $matiereId !== null ? $matieres->get($matiereId) : null];
        }

        $ligne->matiere = $ligne->matiere_id !== null ? $matieres->get($ligne->matiere_id) : null;
        $ligne->evaluation = $evaluation;

        return $ligne;
    }
}
