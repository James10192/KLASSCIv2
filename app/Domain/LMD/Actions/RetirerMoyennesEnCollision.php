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
 * Il ne s'applique qu'aux lignes que la fusion a elle-même rendues en
 * collision : `$autorises` est la liste que `MergeDuplicateEcue` a renvoyée,
 * gardée côté serveur par le contrôleur — jamais celle que le navigateur
 * envoie. Sans ce lien, le seul contrôle possible (« l'élément est une ECUE
 * mise de côté, et la canonique a une ligne sur la même coordonnée ») serait
 * vrai de N'IMPORTE QUELLE ECUE du semestre en face de n'importe quelle ECUE
 * supprimée : le geste effacerait une moyenne qu'aucune fusion n'a produite, et
 * réécrirait une moyenne sans rapport. Deux gardes s'y ajoutent, par
 * précaution : l'élément absorbé ne porte plus aucune évaluation (la fusion
 * forcée les a toutes déplacées), et la canonique a bien une ligne en face.
 * C'est l'établissement qui déclenche le geste ; le code ne choisit pas seul.
 */
class RetirerMoyennesEnCollision
{
    /**
     * @param  list<int>  $resultatIds  lignes demandées
     * @param  list<int>  $autorises  lignes que la fusion vers `$canonicalId` a rendues en collision
     * @return array{retirees:int, recalculees:int, echecs:list<int>, refusees:list<array{id:int, raison:string}>}
     */
    public function execute(int $canonicalId, array $resultatIds, array $autorises, ?int $par = null): array
    {
        $autorises = array_map('intval', $autorises);
        $canonique = ESBTPMatiere::find($canonicalId);
        $refusees = [];
        $retirees = 0;
        $recalculees = 0;
        $echecs = [];

        foreach (array_values(array_unique(array_map('intval', $resultatIds))) as $id) {
            // Le lien avec la fusion d'abord : c'est lui qui rend le reste sûr.
            $raison = in_array($id, $autorises, true) ? null : 'hors_de_la_fusion';
            $raison ??= $canonique === null || $canonique->unite_enseignement_id === null
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

            // Un recalcul en échec laisse la conservée sans les notes absorbées,
            // alors que la ligne de l'absorbée est partie : le certificat compte
            // désormais trop peu. L'appelant doit le savoir, ligne par ligne.
            $issue = PerimetreDeRecalcul::recalculerUnCouple($couple, RecalculApresDeplacement::SOURCE, $par);
            if ($issue['statut'] === PerimetreDeRecalcul::ECHEC) {
                $echecs[] = $id;
            }
            $recalculees += $issue['statut'] === PerimetreDeRecalcul::RECALCULE ? 1 : 0;
        }

        Log::warning('[LMD reconciliation] Moyennes en collision retirees apres fusion d ECUE', [
            'canonical_id' => $canonicalId,
            'retirees' => $retirees,
            'recalculees' => $recalculees,
            'echecs' => $echecs,
            'refusees' => $refusees,
            'par' => $par,
        ]);

        return ['retirees' => $retirees, 'recalculees' => $recalculees, 'echecs' => $echecs, 'refusees' => $refusees];
    }

    /** Pourquoi cette ligne n'est pas une collision de fusion, ou `null` si elle en est une. */
    private function refus(ESBTPResultat $ligne, int $canonicalId): ?string
    {
        $absorbee = ESBTPMatiere::withTrashed()->find($ligne->matiere_id);

        if ($absorbee === null || ! $absorbee->trashed() || $absorbee->unite_enseignement_id === null) {
            return 'pas_un_element_absorbe';
        }

        if (DB::table('esbtp_evaluations')->where('matiere_id', $absorbee->id)->exists()) {
            return 'element_encore_evalue';
        }

        if (! MergeDuplicateEcue::ligneDeLaCanonique($canonicalId, $ligne)->exists()) {
            return 'pas_de_collision';
        }

        return null;
    }
}
