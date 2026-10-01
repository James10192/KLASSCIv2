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
    /** Pourquoi une filière ne peut pas être marquée tronc commun, ou null. */
    public function refusMarquage(ESBTPFiliere $filiere, bool $troncCommun): ?string
    {
        // isTroncCommun() exige une filière principale : marquée sur une option,
        // la case serait cochée en base et sans aucun effet.
        if ($troncCommun && $filiere->parent_id !== null) {
            return "{$filiere->name} est une option d'une autre filière : seule une filière principale peut être un tronc commun.";
        }

        return null;
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
