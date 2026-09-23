<?php

namespace App\Domain\LMD\Actions;

use App\Domain\Notes\PerimetreDeRecalcul;
use App\Domain\Notes\RecalculApresDeplacement;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Règle une collision laissée par {@see MergeDuplicateEcue} : sur une même
 * coordonnée (élève, classe, année, période), l'élément absorbé et l'élément
 * conservé ont chacun leur moyenne enregistrée. Le certificat de scolarité les
 * compte toutes les deux.
 *
 * Ce que fait ce geste, et rien d'autre : mettre de côté la ligne de l'absorbé
 * (suppression douce, tracée par l'audit du modèle), puis recalculer celle du
 * conservé depuis les notes — qui, depuis la fusion, sont TOUTES sur le
 * conservé. C'est la moyenne que le premier enregistrement d'une note aurait
 * écrite de toute façon. S'il n'y a rien à moyenner, le recalcul laisse la
 * ligne et le dit ({@see PerimetreDeRecalcul::recalculerUnCouple()}).
 *
 * Il ne s'applique qu'à une ligne portée par un élément AFFECTÉ PAR UNE FUSION
 * (ECUE mise de côté) et en face d'une ligne vivante du conservé : en dehors de
 * ce cas, la ligne n'est pas une collision de fusion, et elle est refusée.
 * C'est l'établissement qui déclenche le geste ; le code ne choisit pas seul.
 */
class RetirerMoyennesEnCollision
{
    /**
     * @param  list<int>  $resultatIds  lignes de l'élément absorbé, telles que rendues dans `moyennes_enregistrees.conflits`
     * @return array{retirees:int, recalculees:int, refusees:list<array{id:int, raison:string}>}
     */
    public function execute(int $canonicalId, array $resultatIds, ?int $par = null): array
    {
        $canonique = ESBTPMatiere::find($canonicalId);
        $refusees = [];
        $retirees = 0;
        $recalculees = 0;

        foreach (array_values(array_unique(array_map('intval', $resultatIds))) as $id) {
            $raison = $canonique === null || $canonique->unite_enseignement_id === null
                ? 'element_conserve_introuvable'
                : null;

            $ligne = $raison === null ? ESBTPResultat::find($id) : null;
            $raison ??= $ligne === null ? 'ligne_introuvable' : $this->refus($ligne, $canonicalId);

            if ($raison !== null) {
                $refusees[] = ['id' => $id, 'raison' => $raison];

                continue;
            }

            $couple = [
                'etudiant_id' => (int) $ligne->etudiant_id,
                'classe_id' => (int) $ligne->classe_id,
                'matiere_id' => $canonicalId,
                'annee_universitaire_id' => (int) $ligne->annee_universitaire_id,
                'periode' => (string) $ligne->periode,
            ];

            // Modèle par modèle : le delete de masse contournerait l'audit.
            DB::transaction(fn () => $ligne->delete());
            $retirees++;

            $issue = PerimetreDeRecalcul::recalculerUnCouple($couple, RecalculApresDeplacement::SOURCE, $par);
            $recalculees += $issue['statut'] === PerimetreDeRecalcul::RECALCULE ? 1 : 0;
        }

        Log::warning('[LMD reconciliation] Moyennes en collision retirees apres fusion d ECUE', [
            'canonical_id' => $canonicalId,
            'retirees' => $retirees,
            'recalculees' => $recalculees,
            'refusees' => $refusees,
            'par' => $par,
        ]);

        return ['retirees' => $retirees, 'recalculees' => $recalculees, 'refusees' => $refusees];
    }

    /** Pourquoi cette ligne n'est pas une collision de fusion, ou `null` si elle en est une. */
    private function refus(ESBTPResultat $ligne, int $canonicalId): ?string
    {
        $absorbee = ESBTPMatiere::withTrashed()->find($ligne->matiere_id);

        if ($absorbee === null || ! $absorbee->trashed() || $absorbee->unite_enseignement_id === null) {
            return 'pas_un_element_absorbe';
        }

        if (! MergeDuplicateEcue::ligneDeLaCanonique($canonicalId, $ligne)->exists()) {
            return 'pas_de_collision';
        }

        return null;
    }
}
