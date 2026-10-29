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
 * conteneur, et elle EST prouvée :
 * `tests/Unit/Domain/EmploiTemps/DetectionDesConflitsTest.php`.
 *
 * Cette phrase a porté deux mensonges successifs, et le second est instructif :
 * elle a d'abord annoncé « se rejoue en trois assertions » quand la suite
 * n'existait pas encore ; puis, une fois la suite écrite **dans le même commit
 * que cette classe**, une correction ultérieure a déclaré qu'« aucun test ne
 * l'accompagnait » et en a créé une seconde, rivale, sous un autre chemin —
 * sans voir que la première existait, ni qu'elle venait de la mettre au rouge.
 * Avant d'écrire qu'une classe n'a pas de test, cherchez-le.
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
 * a alors plus d'année à tester par paire, et la double boucle passe de
 * l'histoire entière de la base à chaque année prise à part.
 *
 * Il ne dispense PAS de dater la déduplication, et c'est le piège : regrouper
 * empêche d'apparier deux années, pas deux conflits nés séparément dans deux
 * années de se replier l'un sur l'autre à la fin. Voir `dedupliquer()`.
 */
final class DetectionDesConflits
{
    /**
     * @param  iterable<object>  $seances  séances actives, avec `emploiTemps.classe` et `teacher.user` chargés
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: ?string, heure_fin: ?string, seance_id: mixed, annee_universitaire_id: mixed}>
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

    /**
     * Le jour passe par `JourDeLaSemaine` et non par `==`.
     *
     * `esbtp_seance_cours.jour` porte deux écritures selon l'écran de saisie :
     * l'entier `1` depuis la liste des séances, le libellé « Lundi » depuis
     * l'emploi du temps. En PHP 8, `1 == 'Lundi'` est faux — c'est l'entier qui
     * est converti en chaîne. Deux séances du même lundi issues des deux
     * chemins ne se voyaient donc jamais.
     *
     * Le regroupement par jour à la source, qui supprimerait ce test comme le
     * regroupement par année a supprimé le sien, n'est PAS fait : un jour
     * illisible formerait son propre groupe, et toutes les données abîmées se
     * déclareraient en conflit entre elles. `memeJour()` refuse ce cas, un
     * `groupBy` ne le saurait pas.
     */
    private function seChevauchent(object $seance, object $autre): bool
    {
        return JourDeLaSemaine::memeJour($seance->jour, $autre->jour)
            && $seance->heure_debut < $autre->heure_fin
            && $seance->heure_fin > $autre->heure_debut;
    }

    /**
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: ?string, heure_fin: ?string, seance_id: mixed, annee_universitaire_id: mixed}>
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

    /**
     * Une ligne de conflit, prête à être affichée.
     *
     * Les heures sont mises en forme ICI, et non à l'affichage. Sur
     * `ESBTPSeanceCours`, `heure_debut` rend un `Carbon` daté du jour (piège #14
     * de `klassci-debugging-discipline.md`) : transporter l'objet jusqu'au
     * bandeau faisait afficher « 2026-09-17 08:00:00 à 2026-09-17 10:00:00 » à
     * la place de « 08:00 à 10:00 ». Le tamis de la rule ne pouvait pas le voir,
     * son motif cherchant `->heure_debut` quand la vue lit `$conflit['heure_debut']`.
     *
     * Formater à la source plutôt qu'à l'affichage rend aussi la clé de
     * `dedupliquer()` explicite : elle reposait jusqu'ici sur le `__toString()`
     * d'un `Carbon`.
     *
     * Par `HeureDeSeance::hi()` et NON par `optional(…)->format('H:i')`, qui a
     * d'abord été posé ici : ce dernier ne couvre que l'objet et rend `null` sur
     * une chaîne, sans un mot. Les `null` entraient dans la clé de
     * `dedupliquer()` et repliaient deux conflits distincts en une seule ligne —
     * un double-emploi d'enseignant disparaissait de l'écran. Deux tests de
     * `DetectionDesConflitsTest` l'ont dit tout de suite ; encore fallait-il
     * savoir qu'ils existaient.
     *
     * @return array{type: string, nom: string, jour: mixed, heure_debut: ?string, heure_fin: ?string, seance_id: mixed, annee_universitaire_id: mixed}
     */
    private function conflit(string $type, string $nom, object $seance): array
    {
        return [
            'type' => $type,
            'nom' => $nom,
            'jour' => $seance->jour,
            'heure_debut' => HeureDeSeance::hi($seance->heure_debut),
            'heure_fin' => HeureDeSeance::hi($seance->heure_fin),
            'seance_id' => $seance->id,
            // Portée pour la déduplication ci-dessous, pas pour l'affichage.
            'annee_universitaire_id' => $seance->annee_universitaire_id,
        ];
    }

    /**
     * Une ligne de bandeau par (année, type, nom, jour, horaire).
     *
     * Ce qu'elle replie, maintenant que chaque paire n'est visitée qu'une fois :
     * un même enseignant en conflit avec DEUX autres séances au même créneau et
     * dans la même année. Le `seance_id` conservé est celui de la dernière paire
     * vue.
     *
     * **L'année est dans la clé, et ce n'est pas décoratif.** Le regroupement en
     * amont empêche d'APPARIER deux années ; il ne fait rien contre deux
     * conflits produits indépendamment dans deux années et qui se télescopent
     * ici, puisque cette méthode tourne une seule fois, après tous les groupes.
     * Sans l'année, un enseignant doublement réservé le même lundi en 2024-2025
     * ET en 2026-2027 ne rendait qu'une ligne : un double-emploi bien réel
     * disparaissait du bandeau. Les trois types y étaient également exposés — ce
     * commentaire a d'abord affirmé le contraire, et c'était faux.
     *
     * @param  list<array{type: string, nom: string, jour: mixed, heure_debut: ?string, heure_fin: ?string, seance_id: mixed, annee_universitaire_id: mixed}>  $conflits
     * @return list<array{type: string, nom: string, jour: mixed, heure_debut: ?string, heure_fin: ?string, seance_id: mixed, annee_universitaire_id: mixed}>
     */
    private function dedupliquer(array $conflits): array
    {
        $uniques = [];

        foreach ($conflits as $conflit) {
            $cle = implode('-', [
                $conflit['annee_universitaire_id'],
                $conflit['type'],
                $conflit['nom'],
                // Le RANG, pas l'écriture : sinon trois séances du même lundi
                // écrites `1`, « Lundi » et « Lundi » rendent deux lignes de
                // bandeau pour un seul conflit — le défaut même que cette
                // déduplication est censée supprimer. Repli sur l'écriture
                // brute pour ne pas replier tous les jours illisibles ensemble.
                JourDeLaSemaine::rang($conflit['jour']) ?? $conflit['jour'],
                $conflit['heure_debut'],
                $conflit['heure_fin'],
            ]);
            $uniques[$cle] = $conflit;
        }

        return array_values($uniques);
    }
}
