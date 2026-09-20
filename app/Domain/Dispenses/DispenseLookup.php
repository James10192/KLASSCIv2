<?php

declare(strict_types=1);

namespace App\Domain\Dispenses;

use App\Domain\Dispenses\Models\ESBTPDispense;

/**
 * Les dispenses actives d'un ou plusieurs etudiants, en une requete.
 *
 * Chaque etudiant demande declenche une requete, puis lit en memoire. Un
 * appelant qui connait sa cohorte peut la precharger d'un coup avec
 * precharger() et ramener la generation groupee d'une classe a une seule
 * requete ; AUCUN appelant ne le fait aujourd'hui, la generation reste donc a
 * une requete par etudiant. C'est dit ici pour que personne ne croie
 * l'optimisation acquise.
 *
 * Le memo garde TOUTES les lignes d'une matiere, pas une seule. Une matiere
 * peut porter deux dispenses actives — une par semestre — et n'en retenir
 * qu'une rendrait la question « cette matiere est-elle dispensee sur l'annee ? »
 * impossible a trancher.
 */
final class DispenseLookup
{
    /** @var array<string, array<int, array<int, list<ESBTPDispense>>>> annee => etudiant => matiere => lignes */
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
                $this->memo[(string) $anneeId][(int) $dispense->etudiant_id][(int) $dispense->matiere_id][] = $dispense;
            });
    }

    /**
     * Dispenses actives de cet etudiant, une par matiere.
     *
     * Quand une matiere en porte deux (une par semestre), c'est la premiere
     * lue qui represente la matiere : cette forme sert l'affichage, pas la
     * decision. Les decisions passent par pourEtudiantEtSemestre().
     *
     * @return array<int, ESBTPDispense>
     */
    public function pourEtudiant(int $etudiantId, int $anneeId): array
    {
        return array_map(
            fn (array $lignes) => $lignes[0],
            array_filter($this->lignesDe($etudiantId, $anneeId), fn (array $l) => $l !== [])
        );
    }

    /**
     * Dispenses de cet etudiant qui retirent la matiere a cette periode.
     *
     * `$semestre` nul designe l'ANNEE. Une dispense de semestre n'y suffit pas :
     * un eleve dispense au premier semestre et note au second a bien travaille
     * la matiere sur l'annee. Deux dispenses de semestre qui couvrent les deux
     * moities, en revanche, la retirent — et cela ne se voit qu'en regardant
     * l'ensemble des lignes, ce que fait cette methode.
     *
     * @return array<int, ESBTPDispense>
     */
    public function pourEtudiantEtSemestre(int $etudiantId, int $anneeId, ?int $semestre): array
    {
        $retenues = [];

        foreach ($this->lignesDe($etudiantId, $anneeId) as $matiereId => $lignes) {
            if ($lignes === []) {
                continue;
            }

            if ($semestre !== null) {
                foreach ($lignes as $ligne) {
                    if ($ligne->couvreLeSemestre($semestre)) {
                        $retenues[$matiereId] = $ligne;
                        break;
                    }
                }

                continue;
            }

            $annuelle = null;
            $periodes = [];
            foreach ($lignes as $ligne) {
                if ($ligne->periode === null) {
                    $annuelle = $ligne;
                    break;
                }
                $periodes[$ligne->periode] = $ligne;
            }

            if ($annuelle !== null) {
                $retenues[$matiereId] = $annuelle;
            } elseif (isset($periodes['semestre1'], $periodes['semestre2'])) {
                $retenues[$matiereId] = $periodes['semestre1'];
            }
        }

        return $retenues;
    }

    /**
     * @return array<int, list<ESBTPDispense>>
     */
    private function lignesDe(int $etudiantId, int $anneeId): array
    {
        if (! isset($this->memo[(string) $anneeId][$etudiantId])) {
            $this->precharger([$etudiantId], $anneeId);
        }

        return $this->memo[(string) $anneeId][$etudiantId] ?? [];
    }

    /** Apres avoir accorde ou revoque : la memoire de la requete est perimee. */
    public function oublier(): void
    {
        $this->memo = [];
    }
}
