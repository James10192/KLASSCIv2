<?php

namespace App\Domain\EmploiTemps;

use Illuminate\Support\Collection;

/**
 * Les conflits d'horaire du bandeau de `/esbtp/seances-cours`.
 *
 * Sortie du contrôleur pour deux raisons, dans cet ordre : elle y était le seul
 * morceau de raisonnement d'un fichier qui en compte peu, et elle n'y était pas
 * vérifiable — privée, dans une classe de plus de mille lignes, donc jamais
 * prouvée autrement que par un commentaire. Ici, elle ne touche ni la base ni le
 * conteneur, et se rejoue en trois assertions.
 *
 * ## Ce que ce détecteur sait, et ce qu'il ignore
 *
 * Il apparie sur le JOUR DE LA SEMAINE et le chevauchement horaire. Il ne
 * regarde aucune date : ni `date_seance`, ni la période de l'emploi du temps.
 * C'est ce qui le distingue des deux autres contrôles du même domaine —
 * `checkSchedulingConflicts()`, qui compare à la date de la séance, et
 * `addExistingSessionsToAvailability()`, qui compare des périodes.
 *
 * D'où le regroupement par année universitaire : sans lui, un permanent qui
 * tient le lundi 8h-10h en 2024-2025 et de nouveau en 2026-2027 est en conflit
 * avec lui-même, et sur une instance qui porte plusieurs années en base c'est
 * une catégorie entière de faux conflits.
 *
 * **C'est une hypothèse, et elle n'est pas garantie.** On tient deux années
 * universitaires distinctes pour disjointes dans le temps ; `esbtp_annee_universitaires`
 * ne l'impose pas — ses `start_date` / `end_date` sont libres, et plusieurs
 * années peuvent porter `is_active`. Une session de rattrapage de l'année N qui
 * tombe dans le calendrier de l'année N+1 occupe une salle bien réelle, et ce
 * conflit-là n'est plus signalé. Le compromis est assumé : le bruit supprimé est
 * massif, l'angle mort est étroit. Le corriger vraiment demande de comparer des
 * dates, c'est-à-dire de faire de ce détecteur ce que les deux autres sont déjà.
 *
 * Le regroupement est posé à la source plutôt qu'en garde dans la boucle : il n'y
 * a alors rien à tester par paire, et la double boucle passe de l'histoire
 * entière de la base à chaque année prise à part.
 */
final class DetectionDesConflits
{
    /**
     * @param  iterable<object>  $seances  séances actives, avec `emploiTemps.classe` et `teacher.user` chargés
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: mixed, heure_fin: mixed, seance_id: mixed}>
     */
    public function depuis(iterable $seances): array
    {
        $conflits = [];

        foreach (Collection::make($seances)->groupBy('annee_universitaire_id') as $seancesDeLAnnee) {
            foreach ($seancesDeLAnnee as $seance) {
                foreach ($seancesDeLAnnee as $autre) {
                    // Chaque paire une seule fois, ancrée sur la séance de plus
                    // petit identifiant. La double boucle la voyait deux fois,
                    // et la déduplication ne rattrapait que le cas où les deux
                    // séances ont EXACTEMENT les mêmes horaires — sa clé les
                    // contient. Deux séances qui se chevauchent sans coïncider
                    // (8h-10h et 9h-11h) rendaient donc deux lignes de bandeau
                    // pour un seul conflit.
                    if ((int) $autre->id <= (int) $seance->id) {
                        continue;
                    }

                    if (! $this->seChevauchent($seance, $autre)) {
                        continue;
                    }

                    foreach ($this->conflitsDeLaPaire($seance, $autre) as $conflit) {
                        $conflits[] = $conflit;
                    }
                }
            }
        }

        return $this->dedupliquer($conflits);
    }

    private function seChevauchent(object $seance, object $autre): bool
    {
        return $seance->jour == $autre->jour
            && $seance->heure_debut < $autre->heure_fin
            && $seance->heure_fin > $autre->heure_debut;
    }

    /**
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: mixed, heure_fin: mixed, seance_id: mixed}>
     */
    private function conflitsDeLaPaire(object $seance, object $autre): array
    {
        $trouves = [];

        // Enseignant — lu sur `teacher_id`, la colonne vivante.
        //
        // Il se lisait sur la colonne texte `enseignant`, qui est MORTE : absente
        // de `$fillable`, écrite par aucun code du dépôt, donc nulle sur toute
        // séance créée par l'application. Comme `null == null` est vrai, la
        // branche se déclenchait sur n'importe quelle paire qui se chevauche.
        // S'en garder par `trim() !== ''` retirait le bruit mais rendait la
        // détection définitivement inerte : un faux positif systématique échangé
        // contre un faux négatif systématique n'est pas un progrès.
        if ($seance->teacher_id && (int) $seance->teacher_id === (int) $autre->teacher_id) {
            $trouves[] = $this->conflit('Enseignant', $this->nomDeLEnseignant($seance), $seance);
        }

        // Salle — celle-là est bien peuplée, et la garde de nullité y était le
        // vrai besoin : deux séances sans salle se déclaraient en conflit.
        //
        // La casse et les abréviations restent distinctes : « Amphi A », « amphi A »
        // et « A » sont trois salles. Les rapprocher élargirait la détection sur
        // des données existantes, ce qui est un autre geste.
        if (trim((string) $seance->salle) !== ''
            && trim((string) $seance->salle) === trim((string) $autre->salle)) {
            $trouves[] = $this->conflit('Salle', (string) $seance->salle, $seance);
        }

        // Classe — par l'emploi du temps, qui la porte.
        if ($seance->emploiTemps && $autre->emploiTemps
            && $seance->emploiTemps->classe_id == $autre->emploiTemps->classe_id) {
            $trouves[] = $this->conflit('Classe', (string) ($seance->emploiTemps->classe->name ?? ''), $seance);
        }

        return $trouves;
    }

    private function nomDeLEnseignant(object $seance): string
    {
        return $seance->teacher?->user?->name
            ?? $seance->teacher?->name
            ?? ('Enseignant #' . $seance->teacher_id);
    }

    /** @return array{type: string, nom: string, jour: mixed, heure_debut: mixed, heure_fin: mixed, seance_id: mixed} */
    private function conflit(string $type, string $nom, object $seance): array
    {
        return [
            'type' => $type,
            'nom' => $nom,
            'jour' => $seance->jour,
            'heure_debut' => $seance->heure_debut,
            'heure_fin' => $seance->heure_fin,
            'seance_id' => $seance->id,
        ];
    }

    /**
     * Une ligne de bandeau par (type, nom, jour, horaire).
     *
     * Ce qu'elle replie, maintenant que chaque paire n'est visitée qu'une fois :
     * un même enseignant en conflit avec DEUX autres séances sur le même
     * créneau. Trois séances qui se chevauchent rendent alors une ligne, et non
     * trois. Le `seance_id` conservé est celui de la dernière paire vue.
     *
     * La clé n'inclut pas l'année : deux conflits réels de deux années
     * différentes, pour le même enseignant au même créneau, se replieraient sur
     * une seule ligne. Le regroupement par année en amont rend le cas
     * inatteignable pour les conflits d'enseignant et de classe ; il reste
     * théoriquement possible pour une salle portant le même libellé.
     *
     * @param  list<array{type: string, nom: string, jour: mixed, heure_debut: mixed, heure_fin: mixed, seance_id: mixed}>  $conflits
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: mixed, heure_fin: mixed, seance_id: mixed}>
     */
    private function dedupliquer(array $conflits): array
    {
        $uniques = [];

        foreach ($conflits as $conflit) {
            $cle = $conflit['type'].'-'.$conflit['nom'].'-'.$conflit['jour'].'-'.$conflit['heure_debut'].'-'.$conflit['heure_fin'];
            $uniques[$cle] = $conflit;
        }

        return array_values($uniques);
    }
}
