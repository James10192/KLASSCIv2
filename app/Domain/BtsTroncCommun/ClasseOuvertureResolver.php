<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPClasseOrientationTarget;

/**
 * A quel semestre une classe de specialite s'ouvre-t-elle ?
 *
 * Un etudiant de BTS suit le tronc commun, puis rejoint une classe de
 * specialite au semestre porte par `semestre_activation`. Avant ce semestre,
 * la classe n'accueille personne : aucune evaluation ni aucune note ne peut
 * legitimement s'y rattacher.
 *
 * Cette question etait posee a trois endroits — un modele, un service et un
 * controleur — chacun avec sa propre requete brute sur la table. Elle vit
 * desormais ici, aupres des autres resolveurs du parcours BTS.
 *
 * Une classe absente de la table n'est contrainte par rien : elle sert tous
 * les semestres. C'est le cas de la grande majorite des classes.
 */
final class ClasseOuvertureResolver
{
    /**
     * Semestre d'ouverture par classe cible. Portee requete : la generation en
     * masse et la saisie de notes interrogent ce resolveur en boucle.
     *
     * @var array<int, int>|null
     */
    private ?array $ouvertures = null;

    /**
     * Semestre a partir duquel la classe accueille des etudiants, ou null si
     * elle n'est la cible d'aucune orientation active.
     */
    public function semestreDOuverture(int $classeId): ?int
    {
        return $this->ouverturesParClasse()[$classeId] ?? null;
    }

    /**
     * La classe accueille-t-elle deja des etudiants a ce semestre ?
     *
     * Vrai par defaut : une classe qui n'est la cible d'aucune orientation
     * n'est contrainte par rien.
     */
    public function estOuverteAu(int $classeId, int $semestre): bool
    {
        $ouverture = $this->semestreDOuverture($classeId);

        return $ouverture === null || $semestre >= $ouverture;
    }

    /**
     * Classes qui ne sont pas encore ouvertes a ce semestre.
     *
     * @return list<int>
     */
    public function classesNonOuvertesAu(int $semestre): array
    {
        $ids = [];

        foreach ($this->ouverturesParClasse() as $classeId => $ouverture) {
            if ($semestre < $ouverture) {
                $ids[] = $classeId;
            }
        }

        return $ids;
    }

    /**
     * Semestre d'ouverture le plus precoce, par classe cible.
     *
     * Une classe peut etre la cible de plusieurs troncs communs : on retient
     * le semestre le plus tot, celui a partir duquel elle peut recevoir un
     * etudiant.
     *
     * @return array<int, int>
     */
    public function ouverturesParClasse(): array
    {
        if ($this->ouvertures !== null) {
            return $this->ouvertures;
        }

        $ouvertures = [];

        ESBTPClasseOrientationTarget::query()
            ->active()
            ->get(['target_classe_id', 'semestre_activation'])
            ->each(function (ESBTPClasseOrientationTarget $cible) use (&$ouvertures): void {
                $classeId = (int) $cible->target_classe_id;
                $semestre = (int) $cible->semestre_activation;

                $ouvertures[$classeId] = isset($ouvertures[$classeId])
                    ? min($ouvertures[$classeId], $semestre)
                    : $semestre;
            });

        return $this->ouvertures = $ouvertures;
    }

    /**
     * Vide la memoire. Utile aux tests, qui creent des cibles apres la
     * premiere resolution.
     */
    public function oublier(): void
    {
        $this->ouvertures = null;
    }
}
