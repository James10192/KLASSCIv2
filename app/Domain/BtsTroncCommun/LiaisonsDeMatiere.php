<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
 * RIEN N'ECRIT PLUS CE PRODUIT DANS LE PIVOT CANONIQUE. Les deux ecrans des
 * matieres l'ont fait tour a tour, et les deux sont revenus au meme defaut :
 * deux listes ne decrivent pas un ensemble de couples qui n'est pas un
 * rectangle plein, donc un tel formulaire ne peut qu'AJOUTER, jamais retirer.
 * C'etait une roue a cliquet sur la table que lit le bulletin. La maquette
 * s'edite desormais la ou elle se voit, couple par couple.
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
 * `BtsBulletinSubjectResolver`, pas le produit des deux listes.
 *
 * UNE exception comptait, et elle est fermee : le repli de
 * `BulletinInlineConfigurationService::matieresPourConfiguration()` lit le
 * produit des deux pivots plats quand le couple n'a AUCUNE ligne canonique.
 * Vider entierement la maquette d'un couple declenchait donc ce repli, et les
 * matieres qu'on venait d'en retirer REAPPARAISSAIENT sur l'ecran qui decide
 * du contenu du bulletin. `retirer()` detache desormais du pivot plat ce
 * qu'aucun couple canonique ne reclame plus — voir son docblock pour la
 * condition exacte, qui est etroite a dessein.
 *
 * Reconcilier l'existant reste un geste separe, explicite et simule d'abord.
 *
 * BTS uniquement, et `poser()` le FAIT RESPECTER. Le LMD tient ses matieres par
 * parcours -> UE -> ECUE et n'ecrit aucun de ces trois pivots : une ECUE qui y
 * obtient une ligne est toujours une erreur, jamais un cas legitime.
 *
 * C'est le filet, et il est place ici a dessein. Le chantier qui a decouvert la
 * fuite a d'abord filtre chez les LECTEURS — douze filtres dans neuf fichiers,
 * trouves en trois passes de revue, et il en restait. Filtrer en lecture
 * demande a chaque futur ecran de s'en souvenir ; refuser a l'ECRITURE ne le
 * demande qu'une fois. Les cinq appelants d'aujourd'hui refusent deja en amont,
 * avec un message pour l'utilisateur : ce garde-ci ne leur sert pas, il sert au
 * sixieme.
 *
 * Il LEVE au lieu de retourner silencieusement : une ligne posee par erreur
 * fait sortir une matiere sur un bulletin deja imprime, ce dont personne ne
 * s'apercoit. Une erreur 500 se voit le jour meme.
 *
 * `retirer()` n'a PAS ce garde, et c'est l'inverse d'un oubli : le retrait est
 * le geste correcteur. Le refuser des deux cotes a rendu `TPOH243` x
 * (TRAVAUX_PUBLICS, 2A) visible par le CLI et retirable par rien.
 *
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
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
            $matiere = ESBTPMatiere::find($matiereId);

            if ($matiere && $matiere->unite_enseignement_id !== null) {
                Log::warning('Ecriture refusee : une ECUE LMD ne se rattache pas a une maquette BTS.', [
                    'matiere_id' => $matiereId,
                    'filiere_id' => $filiereId,
                    'niveau_etude_id' => $niveauId,
                    'unite_enseignement_id' => $matiere->unite_enseignement_id,
                    'user_id' => optional(auth()->user())->id,
                ]);

                throw new \InvalidArgumentException(
                    "La matiere #{$matiereId} est un element constitutif LMD : elle ne peut pas "
                    .'etre rattachee a une maquette BTS. Elle se gere dans /esbtp/lmd/ue.'
                );
            }

            $ligne = ESBTPMatiereFilierNiveau::firstOrCreate([
                'matiere_id' => $matiereId,
                'filiere_id' => $filiereId,
                'niveau_etude_id' => $niveauId,
            ]);

            // En AJOUT seulement : les pivots plats portent d'autres couples
            // que celui-ci, et leur charge utile (coefficient, heures) ne se
            // retrouve nulle part ailleurs.
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
     * NE TOUCHE AUX PIVOTS PLATS QUE QUAND PLUS RIEN NE S'EN SERT. Ils ne
     * savent pas de quel couple vient une filiere : detacher « Batiment »
     * parce qu'on quitte (Batiment, 2e annee) retirerait aussi la matiere de
     * (Batiment, 1re annee), que personne n'a nomme. On ne detache donc une
     * filiere que s'il ne reste AUCUNE ligne canonique (matiere, filiere), et
     * un niveau que s'il n'en reste aucune (matiere, niveau).
     *
     * Ne rien detacher du tout etait un trou, et c'est ce chantier qui l'a
     * creuse en donnant un geste de retrait a portee de clic.
     * `BulletinInlineConfigurationService::matieresPourConfiguration()` retombe
     * sur le PRODUIT des deux pivots plats des qu'un couple n'a plus aucune
     * ligne canonique : vider entierement une maquette par la croix faisait
     * donc REAPPARAITRE les matieres retirees sur l'ecran qui decide du
     * contenu du bulletin. Apres un retrait explicite, « vide » est une
     * DECISION, pas une absence de configuration — le repli n'a de sens que
     * pour un couple que personne n'a jamais renseigne.
     *
     * CE QUI EST PERDU EST JOURNALISE. Les pivots plats portent une charge
     * utile (`coefficient`, `heures_cours`) qui ne se retrouve nulle part
     * ailleurs. La detacher en silence serait le piege #12 : on ne saurait
     * meme pas qu'il faut chercher. Chaque detachement part donc au journal
     * avec ce qu'il emporte.
     *
     * @return array{canonique: int, places_semestre: int, pivots_plats: array{filiere: bool, niveau: bool}}
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
                return [
                    'canonique' => 0,
                    'places_semestre' => 0,
                    'pivots_plats' => ['filiere' => false, 'niveau' => false],
                ];
            }

            // La place au bulletin d'un couple qui n'existe plus n'a plus de
            // sens, et sa ligne resterait a jamais : rien ne la relit.
            $places = ESBTPMaquettePlaceSemestre::query()
                ->where('matiere_id', $matiereId)
                ->where('filiere_id', $filiereId)
                ->where('niveau_etude_id', $niveauId)
                ->delete();

            $platsDetaches = $this->detacherLesPivotsPlatsDevenusInutiles(
                $matiereId,
                $filiereId,
                $niveauId,
            );

            return [
                'canonique' => (int) $canonique,
                'places_semestre' => (int) $places,
                'pivots_plats' => $platsDetaches,
            ];
        });
    }

    /**
     * Detache des pivots plats ce qu'aucun couple canonique ne reclame plus.
     *
     * Appelee APRES la suppression de la ligne canonique : ce qui reste en base
     * a cet instant est exactement ce qui doit decider.
     *
     * @return array{filiere: bool, niveau: bool}
     */
    private function detacherLesPivotsPlatsDevenusInutiles(
        int $matiereId,
        int $filiereId,
        int $niveauId
    ): array {
        $matiere = ESBTPMatiere::find($matiereId);

        if (! $matiere) {
            return ['filiere' => false, 'niveau' => false];
        }

        $resteCetteFiliere = ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiereId)
            ->where('filiere_id', $filiereId)
            ->exists();

        $resteCeNiveau = ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiereId)
            ->where('niveau_etude_id', $niveauId)
            ->exists();

        $detaches = ['filiere' => false, 'niveau' => false];

        if (! $resteCetteFiliere) {
            $this->journaliserLaChargeUtile($matiere, 'filieres', $filiereId, $matiereId);
            $matiere->filieres()->detach($filiereId);
            $detaches['filiere'] = true;
        }

        if (! $resteCeNiveau) {
            $this->journaliserLaChargeUtile($matiere, 'niveaux', $niveauId, $matiereId);
            $matiere->niveaux()->detach($niveauId);
            $detaches['niveau'] = true;
        }

        return $detaches;
    }

    /**
     * Dit au journal ce que le detachement emporte.
     *
     * `coefficient` et `heures_cours` vivent sur le pivot plat et nulle part
     * ailleurs : les perdre sans trace rendrait la reconstitution impossible.
     */
    private function journaliserLaChargeUtile(
        ESBTPMatiere $matiere,
        string $relation,
        int $cibleId,
        int $matiereId
    ): void {
        // Colonne QUALIFIEE : `id` existe des deux cotes de la jointure de
        // pivot, et MySQL refuse l'ambiguite (1052). Le meme piege est note
        // dans `BtsBulletinSubjectResolver`, sur le meme genre de requete.
        $lien = $matiere->{$relation}();
        $ligne = $lien->where($lien->getRelated()->getTable() . '.id', $cibleId)->first();

        Log::warning('Pivot plat detache : plus aucun couple canonique ne le reclame.', [
            'matiere_id' => $matiereId,
            'matiere' => $matiere->name,
            'relation' => $relation,
            'cible_id' => $cibleId,
            'charge_utile' => $ligne ? $ligne->pivot->toArray() : null,
        ]);
    }
}
