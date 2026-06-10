<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
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
 * @see .claude/rules/klassci-classe-matieres.md
 * @see .claude/rules/lmd-bts-bulletin-separation.md
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

            $matiereIds = collect($unionFiliereIds)
                ->flatMap(fn ($filiereId) => ESBTPMatiereFilierNiveau::matiereIdsForCombo(
                    $filiereId,
                    $classe->niveau_etude_id
                ))
                ->unique()
                ->values();

            $matieres = ESBTPMatiere::whereIn('id', $matiereIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            if ($matieres->isNotEmpty()) {
                return $matieres;
            }
        }

        // Fallback classes BTS historiques attachées directement via le pivot.
        return $classe->matieres()
            ->where('esbtp_matieres.is_active', true)
            ->orderBy('esbtp_matieres.name')
            ->get();
    }
}
