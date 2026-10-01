<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPFiliere;

/**
 * Configurer le tronc commun BTS : marquer une filière, ouvrir une sortie
 * (classe de spécialité vers laquelle une classe de tronc commun oriente).
 *
 * Un seul chemin pour les écrans (fiche de la classe, fiche de la filière,
 * administration des sorties), la CLI et Nanan. La CLI et la fiche filière
 * acceptaient une sortie qu'un autre écran refusait (source hors tronc commun,
 * vers elle-même, autre niveau).
 *
 * Écrivent encore ESBTPClasseOrientationTarget sans passer par ici, à dessein
 * pour l'instant : BtsOrientationTargetController::bulkCopy() et la commande
 * SeedOrientationTargets RECOPIENT ou dérivent des sorties déjà validées sur
 * une autre classe (même filière, même niveau), et
 * BtsOrientationPolicySupport::validateTarget() crée la sortie par hiérarchie
 * de filières au moment d'orienter. Les migrer est la prochaine étape.
 */
class ConfigurationTroncCommun
{
    /**
     * Ce qu'il faut dire avant de marquer (ou démarquer) une OPTION comme tronc
     * commun. Ce n'est PAS un refus : c'est une décision enregistrée (Marcel,
     * juin 2026, commentaire de esbtp/filieres/edit.blade.php « tronc commun
     * secondaire ») qu'une filière rattachée à un parent puisse être un tronc
     * commun secondaire, et des écoles en ont en production (ESBTP Yakro, GBAT,
     * cité dans BtsOrientationTargetController::index).
     *
     * Pas « sans effet » non plus : isTroncCommun() exige une filière
     * principale, mais plusieurs lecteurs lisent la colonne brute
     * is_tronc_commun — ESBTPClasse (classe tronc commun du parent),
     * TroncCommunService (étudiants à orienter), BtsOrientationService
     * (resynchronisation des phases) et la CLI des matières.
     *
     * @return string[]
     */
    public function avertissementsMarquage(ESBTPFiliere $filiere, bool $troncCommun): array
    {
        if ($filiere->parent_id === null || $troncCommun === (bool) $filiere->is_tronc_commun) {
            return [];
        }
        $parent = $filiere->parent()->value('name') ?? ('#'.$filiere->parent_id);
        if (! $troncCommun) {
            return ["{$filiere->name} est un tronc commun secondaire rattaché à {$parent} : en retirant la marque, ses étudiants ne seront plus listés parmi ceux à orienter, ses classes ne seront plus vues comme classes de tronc commun du parent, et la CLI des matières la refusera comme tronc commun."];
        }

        return [
            "{$filiere->name} deviendra un tronc commun secondaire rattaché à {$parent}.",
            "Ce qui change : ses étudiants apparaîtront parmi ceux à orienter, ses classes pourront être reprises comme classes de tronc commun du parent, la resynchronisation des phases et la CLI des matières la traiteront comme tronc commun. L'écran d'orientation, lui, ne la propose pas comme tronc commun principal.",
        ];
    }

    public function marquerFiliere(ESBTPFiliere $filiere, bool $troncCommun, ?int $semestres = null): ESBTPFiliere
    {
        $filiere->update([
            'is_tronc_commun' => $troncCommun,
            'semestres_tronc_commun' => $semestres ?? ($filiere->semestres_tronc_commun ?: 1),
        ]);

        return $filiere;
    }

    /** Pourquoi cette sortie est refusée, ou null. Mêmes règles que l'écran de la classe. */
    public function refusSortie(ESBTPClasse $source, ESBTPClasse $cible): ?string
    {
        if (! $source->isTroncCommun()) {
            return "{$source->name} n'appartient pas à un tronc commun : elle n'a pas de sortie.";
        }
        if ((int) $source->id === (int) $cible->id) {
            return 'Une classe ne peut pas être sa propre sortie.';
        }
        // Les classes sont universelles (classes-universelles-pas-annee.md) :
        // seul le niveau d'études compte, jamais l'année.
        if ((int) $source->niveau_etude_id !== (int) $cible->niveau_etude_id) {
            return "{$cible->name} n'est pas au même niveau que {$source->name}.";
        }

        return null;
    }

    public function ajouterSortie(
        ESBTPClasse $source,
        ESBTPClasse $cible,
        ?int $semestreActivation = null,
        ?string $notes = null,
        ?int $ordre = null,
        bool $active = true,
    ): ESBTPClasseOrientationTarget {
        $refus = $this->refusSortie($source, $cible);
        if ($refus !== null) {
            throw new \InvalidArgumentException($refus);
        }

        // Rouvrir une sortie existante ne doit rien effacer : les notes, le rang
        // et le semestre déjà posés restent tant qu'on n'en donne pas d'autres.
        $sortie = ESBTPClasseOrientationTarget::firstOrNew(['source_classe_id' => $source->id, 'target_classe_id' => $cible->id]);
        $existe = $sortie->exists;
        $sortie->fill([
            'semestre_activation' => $semestreActivation ?? ($existe ? $sortie->semestre_activation : 2),
            'is_active' => $active,
            'sort_order' => $ordre ?? ($existe ? $sortie->sort_order : ESBTPClasseOrientationTarget::where('source_classe_id', $source->id)->count()),
            'notes' => $notes ?? ($existe ? $sortie->notes : null),
        ])->save();

        return $sortie;
    }
}
