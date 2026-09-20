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
 * UNE exception compte, ET ELLE EST OUVERTE : le repli de
 * `BulletinInlineConfigurationService::matieresPourConfiguration()` lit le
 * produit des deux pivots plats quand le couple n'a AUCUNE ligne canonique.
 * Vider entierement la maquette d'un couple declenche donc ce repli, et les
 * matieres qu'on vient d'en retirer REAPPARAISSENT sur l'ecran qui decide du
 * contenu du bulletin.
 *
 * CE PARAGRAPHE A ANNONCE LE CONTRAIRE, et c'est la raison de sa majuscule.
 * Une version de `retirer()` detachait du pivot plat ce qu'aucun couple
 * canonique ne reclamait plus ; elle a ete RETIREE, parce qu'elle decidait sur
 * le canonique pendant que le repli lit le plat, et que rien n'ecrit le
 * canonique depuis l'ecran des matieres. Son collateral etait mesurablement
 * pire que le defaut vise. Le docblock de `retirer()` porte le detail, le
 * declencheur, et la correction qui vaudrait (un `deleted_at` sur le pivot
 * canonique). Une phrase d'en-tete qui absout ferme l'enquete suivante :
 * celle-ci a survecu a la marche arriere pendant une passe entiere.
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
     * NE TOUCHE PAS AUX PIVOTS PLATS, ET C'EST UNE MARCHE ARRIERE ASSUMEE.
     *
     * Une version de cette methode les detachait quand plus aucune ligne
     * canonique ne les reclamait. L'intention etait juste — voir le defaut
     * decrit plus bas — mais la condition regardait le MAUVAIS pivot : elle
     * comptait sur le canonique, alors que le seul consommateur qu'elle
     * protegeait lit le PRODUIT DES DEUX LISTES PLATES.
     *
     * Le collateral, mesure sur l'etat normal des donnees : une matiere en
     * filieres {A, B} et niveaux {1} dont la maquette ne porte que (A,1). La
     * classe (B,1) n'a aucune ligne canonique, donc elle lit le repli, donc
     * elle voit cette matiere AUJOURD'HUI. Retirer la matiere de (A,1)
     * detachait le niveau 1 — plus aucun couple canonique ne le reclamait — et
     * la matiere DISPARAISSAIT du bulletin de (B,1), que personne n'avait
     * touchee. Cet etat n'est pas un cas de bord : `ESBTPMatiereController`
     * ecrit les deux listes plates et refuse deliberement d'ecrire la
     * maquette, donc « plat plus large que canonique » est la regle.
     *
     * S'y ajoutait une perte seche : `esbtp_matiere_niveau` porte
     * `coefficient` et `heures_cours`, que `poser()` ne reecrit pas. Un cycle
     * retirer puis reposer les ramenait a leur defaut.
     *
     * LE DEFAUT QUI RESTE, ET SON DECLENCHEUR.
     * `BulletinInlineConfigurationService::matieresPourConfiguration()` retombe
     * sur le produit des deux listes plates des qu'un couple n'a PLUS AUCUNE
     * ligne canonique. Vider entierement une maquette par la croix fait donc
     * reapparaitre les matieres retirees sur l'ecran qui decide du contenu du
     * bulletin. Apres un retrait explicite, « vide » est une DECISION, pas une
     * absence de configuration — c'est le zero confondu avec l'absence de
     * `rien-en-dur.md`.
     *
     * POURQUOI CE N'EST PAS CORRIGE ICI. Distinguer « vide » de « jamais
     * renseigne » demande un etat que le depot n'a pas : cette methode
     * supprime en dur la ligne canonique ET sa place par semestre, et
     * `esbtp_matiere_filiere_niveau` ne porte pas de suppression en douceur.
     * La correction juste est de poser cet etat (un `deleted_at` sur le pivot
     * canonique, que le repli interrogerait par `withTrashed()`), et c'est un
     * changement de schema sur une table partagee par huit instances : il
     * demande sa propre mesure, pas d'etre glisse en fin de branche. Le
     * declencheur est la premiere ecole qui vide une maquette et voit revenir
     * ce qu'elle a retire.
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
