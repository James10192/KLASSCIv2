<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun\Diagnostics;

/**
 * Pourquoi une matiere de specialite atteint un bulletin de tronc commun, et
 * ce que l'ecole peut faire pour l'en retirer.
 *
 * Les actions sont des PHRASES, jamais des operations : ce diagnostic ne
 * touche a rien. Classer une matiere, deplacer une note ou regenerer un
 * bulletin reste une decision de l'ecole.
 */
final class TcSpecialiteLeakCauses
{
    public const MATIERE_A_CLASSER = 'matiere_a_classer';

    public const EVALUATION_AUTRE_CLASSE = 'evaluation_autre_classe';

    public const ETUDIANT_REORIENTE = 'etudiant_reoriente';

    public const AUTRE = 'autre';

    public const TYPE_CLASSEE_SPECIALITE = 'classee_specialite';

    public const TYPE_NON_CLASSEE = 'non_classee_hors_planification';

    /** @return list<string> */
    public static function toutes(): array
    {
        return [self::MATIERE_A_CLASSER, self::EVALUATION_AUTRE_CLASSE, self::ETUDIANT_REORIENTE, self::AUTRE];
    }

    /**
     * Cause d'une note : d'abord ou l'evaluation a ete posee, ensuite ce que
     * dit la classification de la matiere.
     *
     * @param  array<int, true>  $classesDeSpecialite  classes des phases de specialite de l'etudiant
     */
    public static function pourNote(int $classeEvaluation, int $classeTc, array $classesDeSpecialite, string $type): string
    {
        if ($classeEvaluation !== $classeTc) {
            return isset($classesDeSpecialite[$classeEvaluation])
                ? self::ETUDIANT_REORIENTE
                : self::EVALUATION_AUTRE_CLASSE;
        }

        return $type === self::TYPE_NON_CLASSEE ? self::MATIERE_A_CLASSER : self::AUTRE;
    }

    /**
     * Cause d'une moyenne enregistree sur la classe de tronc commun. Elle
     * herite de la cause des notes de l'etudiant quand il y en a : une moyenne
     * persistee a partir d'une note egaree reste sur le bulletin a chaque
     * generation, meme une fois la note ecartee du calcul.
     *
     * @param  list<string>  $causesDesNotes
     */
    public static function pourMoyenne(array $causesDesNotes, string $type): string
    {
        foreach ([self::ETUDIANT_REORIENTE, self::EVALUATION_AUTRE_CLASSE, self::MATIERE_A_CLASSER] as $cause) {
            if (in_array($cause, $causesDesNotes, true)) {
                return $cause;
            }
        }

        return $causesDesNotes === [] && $type === self::TYPE_NON_CLASSEE ? self::MATIERE_A_CLASSER : self::AUTRE;
    }

    /** @param array<string, mixed> $contexte */
    public static function action(string $cause, array $contexte): string
    {
        $matiere = '« '.($contexte['matiere'] ?? '?').' »';
        $couple = ($contexte['filiere'] ?? '?').' × '.($contexte['niveau'] ?? '?');
        $evaluation = isset($contexte['evaluation_id']) ? 'l\'evaluation #'.$contexte['evaluation_id'] : 'l\'evaluation';
        $classeEval = $contexte['evaluation_classe'] ?? 'une autre classe';

        return match ($cause) {
            self::MATIERE_A_CLASSER => "Classer {$matiere} sur le couple {$couple} depuis /esbtp/matieres/classification (specialite si elle n'est pas enseignee en tronc commun), ou la planifier pour le tronc commun si elle l'est.",
            self::EVALUATION_AUTRE_CLASSE => "Verifier {$evaluation} posee sur {$classeEval} : l'etudiant n'y a pas de phase. Deplacer ou retirer la note si elle ne le concerne pas, puis regenerer le bulletin.",
            self::ETUDIANT_REORIENTE => "Note prise en specialite ({$classeEval}) : verifier la periode de {$evaluation}, elle ne doit pas figurer au bulletin de tronc commun. Regenerer le bulletin apres correction.",
            default => ($contexte['moyenne_sans_note'] ?? false)
                ? "Moyenne enregistree sur la classe de tronc commun sans note correspondante pour {$matiere} : a arbitrer par l'ecole, puis regenerer le bulletin."
                : "Evaluation de {$matiere}, classee specialite, posee sur la classe de tronc commun : verifier la matiere de {$evaluation}, ou revoir la classification si la matiere est reellement enseignee en tronc commun.",
        };
    }
}
