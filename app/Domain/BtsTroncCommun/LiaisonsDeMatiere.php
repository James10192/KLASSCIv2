<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Facades\DB;

/**
 * Le rattachement d'une matiere a un couple (filiere, niveau), en un seul
 * endroit.
 *
 * TROIS TABLES, DEUX VERITES. Le rattachement vit dans
 * `esbtp_matiere_filiere_niveau` (le pivot canonique, au grain filiere x
 * niveau) et dans les deux pivots plats `esbtp_matiere_filiere` et
 * `esbtp_matiere_niveau`, qui sont deux listes independantes. Le produit des
 * deux listes n'est pas le pivot canonique : une matiere en filieres [A, B] et
 * en niveaux [1, 2] parait rattachee a quatre couples alors que le pivot n'en
 * porte peut-etre qu'un. C'est ce que `CLIMatiereController::diagnoseLiaisons`
 * appelle des combinaisons fantomes.
 *
 * ON NE REPARE PAS CET ECART PAR EFFET DE BORD. Une premiere version de cette
 * classe recalculait les pivots plats depuis le canonique apres chaque
 * ecriture, par un `sync()`. La revue l'a demontee, et elle avait raison :
 * `sync()` detache tout ce qui n'est pas dans la liste, et la liste ne
 * connaissait que le couple qu'on venait de toucher. Ajouter une matiere a
 * (Batiment, 1re annee) l'aurait detachee de Travaux Publics et de la 2e
 * annee — en emportant `esbtp_matiere_niveau.coefficient` et
 * `heures_cours`, qui ne se retrouvent nulle part ailleurs.
 *
 * Ce service n'ECRIT donc les pivots plats qu'en AJOUT (`syncWithoutDetaching`,
 * le geste qu'`addToCombination` faisait deja), et n'en retire jamais rien. La
 * coherence des LECTURES se gagne ailleurs, et c'est la qu'elle se gagne
 * vraiment : les ecrans lisent le pivot canonique par
 * `BtsBulletinSubjectResolver`, pas le produit des deux listes. Reconcilier
 * l'existant reste un geste separe, explicite et simule d'abord.
 *
 * BTS uniquement. Le LMD tient ses matieres par parcours -> UE -> ECUE et
 * n'utilise aucun de ces trois pivots.
 *
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
// Non `final` a dessein : `RetraitDeMaquette::appliquer()` est le chemin qui
// EFFACE des lignes de maquette, et le seul moyen de l'eprouver sans base est
// de lui passer une doublure de ce service. Aucune sous-classe n'existe en
// production, et il n'y a pas de contrat d'heritage a tenir ici.
class LiaisonsDeMatiere
{
    /**
     * Rattache une matiere a un couple, sans toucher a ses autres couples.
     *
     * Rend la ligne canonique, creee ou deja la.
     */
    public function poser(int $matiereId, int $filiereId, int $niveauId): ESBTPMatiereFilierNiveau
    {
        return DB::transaction(function () use ($matiereId, $filiereId, $niveauId) {
            $ligne = ESBTPMatiereFilierNiveau::firstOrCreate([
                'matiere_id' => $matiereId,
                'filiere_id' => $filiereId,
                'niveau_etude_id' => $niveauId,
            ]);

            // En AJOUT seulement : les pivots plats portent d'autres couples
            // que celui-ci, et leur charge utile (coefficient, heures) ne se
            // retrouve nulle part ailleurs.
            $matiere = ESBTPMatiere::find($matiereId);
            if ($matiere) {
                $matiere->filieres()->syncWithoutDetaching([$filiereId]);
                $matiere->niveaux()->syncWithoutDetaching([$niveauId]);
            }

            return $ligne;
        });
    }

    /**
     * Detache une matiere d'un couple : la ligne canonique et ses places par
     * semestre.
     *
     * NE TOUCHE PAS aux pivots plats. Ils ne savent pas de quel couple vient
     * une filiere : retirer « Batiment » parce qu'on quitte (Batiment, 2e
     * annee) retirerait aussi la matiere de (Batiment, 1re annee), que
     * personne n'a nomme.
     *
     * @return array{canonique: int, places_semestre: int}
     */
    public function retirer(int $matiereId, int $filiereId, int $niveauId): array
    {
        return DB::transaction(function () use ($matiereId, $filiereId, $niveauId) {
            $canonique = ESBTPMatiereFilierNiveau::query()
                ->where('matiere_id', $matiereId)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->delete();

            if ($canonique === 0) {
                // Rien n'etait rattache : rien a nettoyer, et surtout rien a
                // ecrire avant de repondre « rien n'a ete fait ».
                return ['canonique' => 0, 'places_semestre' => 0];
            }

            // La place au bulletin d'un couple qui n'existe plus n'a plus de
            // sens, et sa ligne resterait a jamais : rien ne la relit.
            $places = ESBTPMaquettePlaceSemestre::query()
                ->where('matiere_id', $matiereId)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->delete();

            return ['canonique' => (int) $canonique, 'places_semestre' => (int) $places];
        });
    }
}
