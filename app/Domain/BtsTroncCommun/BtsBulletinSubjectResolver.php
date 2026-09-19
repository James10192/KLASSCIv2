<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use Illuminate\Support\Collection;

/**
 * Résout la liste des matières d'une classe BTS pour la génération/prévisualisation
 * de bulletin, en tenant compte du tronc commun.
 *
 * Une classe de spécialité (filière fille d'un tronc commun) doit aussi voir les
 * matières définies au niveau de la filière mère (le tronc commun) afin que les
 * notes saisies pendant la phase tronc commun apparaissent au bulletin (C10).
 *
 * Source canonique : `esbtp_matiere_filiere_niveau` via `matiereIdsForCombo`, en
 * union sur `troncCommunUnionFiliereIds()`. Fallback sur le pivot legacy
 * `esbtp_classe_matiere` quand aucune matière n'est définie au niveau (compat BTS
 * historique).
 *
 * BTS uniquement — LMD intouché. Stateless, sans dépendance à BulletinService.
 *
 * **Les ECUE LMD sont écartées, et ce filtre n'est pas décoratif.** Les deux
 * écrans qui gèrent les matières BTS l'appliquent déjà — `prepareMatieresListing()`
 * par un `whereNull('unite_enseignement_id')`, `lignesDuCombo()` par un `filter()`
 * sur le même attribut. Ce résolveur, lui, ne filtrait que `is_active` : une ECUE
 * qui obtient une ligne dans le pivot canonique BTS sortait donc **au bulletin**
 * tout en restant **introuvable** depuis les écrans censés la gérer. Le cas mesuré
 * sur esbtp-abidjan : « Alimentation en eau et QTE » (TPOH243), importée avec les
 * maquettes Génie Civil, portait une ligne `(TRAVAUX_PUBLICS, 2A)` et s'imprimait
 * sur les bulletins de Travaux Publics 2ᵉ année — sans qu'on puisse l'en retirer.
 *
 * Le filtre est sûr ici PAR CONSTRUCTION, et non par énumération de ses
 * appelants : une classe LMD ne porte aucune ligne dans le pivot canonique BTS
 * ni dans `esbtp_classe_matiere`, donc ce résolveur rend vide pour elle — ce qui
 * est le bon résultat sur un écran BTS. (Une version antérieure de ce commentaire
 * citait deux appelants ; le chantier en a ajouté trois, et une preuve par
 * énumération cesse d'en être une dès qu'elle est incomplète.) Ne recopie PAS
 * `btsOnly()` sur un lecteur au contexte mixte — les présences, par exemple,
 * concernent légitimement des ECUE (cf. le garde-fou de la rule ci-dessous).
 *
 * @see .claude/rules/klassci-classe-matieres.md
 * @see .claude/rules/lmd-bts-bulletin-separation.md
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 */
class BtsBulletinSubjectResolver
{
    /**
     * Matières (modèles complets, actives) servant de base au bulletin d'une classe.
     *
     * @return Collection<int, ESBTPMatiere>
     */
    public function subjectsForClasse(ESBTPClasse $classe): Collection
    {
        if ($classe->filiere_id && $classe->niveau_etude_id) {
            // Tronc commun (C10) : union [filière classe, filière TC parente].
            $unionFiliereIds = $classe->filiere
                ? $classe->filiere->troncCommunUnionFiliereIds()
                : [$classe->filiere_id];

            // Statut TC de chaque filière de l'union (pour filtrer les matières
            // classées « specialite » uniquement sur les combos de tronc commun).
            $filieresById = ESBTPFiliere::whereIn('id', $unionFiliereIds)
                ->get(['id', 'is_tronc_commun', 'parent_id'])
                ->keyBy('id');

            $matiereIds = collect($unionFiliereIds)
                ->flatMap(function ($filiereId) use ($classe, $filieresById) {
                    $query = ESBTPMatiereFilierNiveau::forCombo($filiereId, $classe->niveau_etude_id);

                    // Sur un combo de tronc commun, une matière classée « specialite »
                    // (rattachée par erreur au combo TC) ne doit pas remonter au bulletin.
                    // Les matières non classées (null) restent incluses => non-régressif.
                    // Sur un combo de spécialité, on garde tout (les matières de spé sont légitimes).
                    if (optional($filieresById->get($filiereId))->isTroncCommun()) {
                        $query->notSpecialite();
                    }

                    return $query->pluck('matiere_id');
                })
                ->unique()
                ->values();

            $matieres = ESBTPMatiere::whereIn('id', $matiereIds)
                ->btsOnly()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            if ($matieres->isNotEmpty()) {
                return $matieres;
            }
        }

        // Fallback classes BTS historiques attachées directement via le pivot.
        // `btsOnly()` ferait la meme chose, mais sans qualifier la colonne : dans
        // cette jointure on les nomme toutes, comme `is_active` juste en dessous,
        // qui existe des deux cotes du pivot et serait ambigue sans son prefixe.
        return $classe->matieres()
            ->whereNull('esbtp_matieres.unite_enseignement_id')
            ->where('esbtp_matieres.is_active', true)
            ->orderBy('esbtp_matieres.name')
            ->get();
    }
}
