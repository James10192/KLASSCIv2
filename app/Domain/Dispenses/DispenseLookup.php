<?php

declare(strict_types=1);

namespace App\Domain\Dispenses;

use App\Domain\Dispenses\Models\ESBTPDispense;

/**
 * Les dispenses actives d'un ou plusieurs etudiants, en une requete.
 *
 * La generation groupee d'une classe appelle le bulletin une fois par etudiant.
 * Une lecture par etudiant ferait quarante requetes la ou une seule suffit :
 * l'appelant precharge la cohorte, et chaque etudiant lit ensuite en memoire.
 * Un etudiant demande seul reste correct — il declenche sa propre requete.
 */
final class DispenseLookup
{
    /** @var array<string, array<int, array<int, ESBTPDispense>>> annee => etudiant => matiere => dispense */
    private array $memo = [];

    /**
     * Charge la cohorte entiere en une requete.
     *
     * @param  list<int>  $etudiantIds
     */
    public function precharger(array $etudiantIds, int $anneeId): void
    {
        $manquants = array_values(array_filter(
            array_unique(array_map('intval', $etudiantIds)),
            fn (int $id) => ! isset($this->memo[(string) $anneeId][$id])
        ));

        if ($manquants === []) {
            return;
        }

        // Les identifiants demandes sont marques presents meme sans dispense :
        // sans cela, un etudiant qui n'en a aucune serait re-interroge a chaque
        // lecture, et le prechargement ne servirait a rien.
        foreach ($manquants as $id) {
            $this->memo[(string) $anneeId][$id] = [];
        }

        ESBTPDispense::query()
            ->active()
            ->whereIn('etudiant_id', $manquants)
            ->where('annee_universitaire_id', $anneeId)
            ->get()
            ->each(function (ESBTPDispense $dispense) use ($anneeId) {
                $this->memo[(string) $anneeId][(int) $dispense->etudiant_id][(int) $dispense->matiere_id] = $dispense;
            });
    }

    /**
     * Dispenses actives de cet etudiant, indexees par matiere.
     *
     * @return array<int, ESBTPDispense>
     */
    public function pourEtudiant(int $etudiantId, int $anneeId): array
    {
        if (! isset($this->memo[(string) $anneeId][$etudiantId])) {
            $this->precharger([$etudiantId], $anneeId);
        }

        return $this->memo[(string) $anneeId][$etudiantId] ?? [];
    }

    /**
     * Dispenses de cet etudiant qui couvrent ce semestre, indexees par matiere.
     *
     * `$semestre` nul = l'annee entiere : toute dispense active compte, qu'elle
     * porte sur un semestre ou sur l'annee.
     *
     * @return array<int, ESBTPDispense>
     */
    public function pourEtudiantEtSemestre(int $etudiantId, int $anneeId, ?int $semestre): array
    {
        return array_filter(
            $this->pourEtudiant($etudiantId, $anneeId),
            fn (ESBTPDispense $dispense) => $dispense->couvreLeSemestre($semestre)
        );
    }

    /** Apres avoir accorde ou revoque : la memoire de la requete est perimee. */
    public function oublier(): void
    {
        $this->memo = [];
    }
}
