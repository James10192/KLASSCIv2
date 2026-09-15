<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;

/**
 * Les conflits qu'un créneau rencontrerait s'il était enregistré.
 *
 * ## Ce que cette classe est, et ce qu'elle n'est pas
 *
 * Elle interroge la BASE pour UN créneau candidat, avant écriture. Sa voisine
 * `DetectionDesConflits` fait autre chose : elle balaie une collection DÉJÀ
 * chargée pour dresser le bandeau d'une page. L'une garde l'enregistrement,
 * l'autre rend compte de l'existant ; ne les confondez pas.
 *
 * ## Pourquoi elle existe
 *
 * La recherche vivait en privé dans `ESBTPSeanceCoursController`, couplée à un
 * `Request` et à SES noms de champs (`type`, `teacher_id`). Elle n'était donc
 * appelable que depuis `store()`. Résultat mesuré sur la branche :
 *
 * - `ESBTPSeanceCoursController::update()` n'en avait aucune : déplacer une
 *   séance sur un créneau occupé passait sans un mot. Créer la même séance au
 *   même endroit était refusé ; la déplacer là, non.
 * - `ESBTPEmploiTempsController::storeSession()` non plus, alors que c'est le
 *   chemin de saisie de l'écran emploi du temps.
 *
 * ## Deux points d'entrée, et pas un drapeau
 *
 * Une séance qu'on modifie doit s'exclure elle-même de la recherche, sinon elle
 * entre en conflit avec sa propre ligne et rien ne peut plus être enregistré.
 * Un paramètre optionnel `$seanceIgnoree` s'oublie ; deux méthodes nommées, non.
 */
final class ConflitsDUnCreneau
{
    public function __construct(private readonly ESBTPEmploiTemps $emploiTemps) {}

    /**
     * Les conflits d'un créneau qui n'existe pas encore.
     *
     * @param  ?int  $teacherId  `esbtp_teachers.id`, ou null pour ne pas chercher
     *                           de conflit d'enseignant (pause, déjeuner).
     * @param  ?string  $salle  null ou vide pour ne pas chercher de conflit de salle.
     * @return string[] Phrases destinées à l'utilisateur ; vide = rien ne s'oppose.
     */
    public function pourUneNouvelleSeance(
        mixed $jour,
        ?string $heureDebut,
        ?string $heureFin,
        ?int $teacherId = null,
        ?string $salle = null,
    ): array {
        return $this->chercher($jour, $heureDebut, $heureFin, $teacherId, $salle, null);
    }

    /**
     * Les conflits d'un créneau qui remplacerait une séance existante.
     *
     * La séance passée est retirée de la recherche : c'est elle qu'on déplace.
     *
     * @return string[]
     */
    public function pourUneSeanceModifiee(
        ESBTPSeanceCours $seance,
        mixed $jour,
        ?string $heureDebut,
        ?string $heureFin,
        ?int $teacherId = null,
        ?string $salle = null,
    ): array {
        return $this->chercher($jour, $heureDebut, $heureFin, $teacherId, $salle, (int) $seance->id);
    }

    /** @return string[] */
    private function chercher(
        mixed $jour,
        ?string $heureDebut,
        ?string $heureFin,
        ?int $teacherId,
        ?string $salle,
        ?int $seanceIgnoree,
    ): array {
        if ($heureDebut === null || $heureFin === null) {
            return [];
        }

        // La MÊME date que celle que l'appelant écrira : les deux se calculent
        // par `dateDuJour()`. Si l'une changeait sans l'autre, la garde
        // comparerait une date et l'enregistrement en poserait une autre.
        $dateSeance = $this->emploiTemps->dateDuJour($jour);

        if ($dateSeance === null) {
            // Sans date, aucune des trois recherches ne veut dire quoi que ce
            // soit. Mieux vaut le dire que rendre « aucun conflit » : c'est un
            // silence qui ressemble à une autorisation.
            return ['Le jour de la séance est illisible : les conflits n\'ont pas pu être vérifiés.'];
        }

        // Comparée en chaîne de date, et non en `Carbon`. La colonne est un
        // `date` ; lier un `Carbon` y envoyait « Y-m-d H:i:s », que MySQL
        // ramenait bien au bon jour puisque l'heure est toujours minuit. Le
        // résultat était donc juste — il tenait seulement à cette heure-là.
        $jourCompare = $dateSeance->toDateString();

        // Ne retenir que les séances dont l'emploi du temps couvre la date de
        // CELLE qu'on enregistre, et non la date du jour : avec « aujourd'hui »,
        // un emploi du temps pas encore en vigueur était écarté de la recherche,
        // donc préparer le planning du semestre suivant ne déclenchait aucun
        // conflit — précisément au moment où il se corrige sans coût.
        $emploiDuTempsEnVigueur = function ($q) use ($jourCompare) {
            $q->where('is_active', true)
                ->whereDate('date_debut', '<=', $jourCompare)
                ->where(function ($subQuery) use ($jourCompare) {
                    $subQuery->whereNull('date_fin')
                        ->orWhereDate('date_fin', '>=', $jourCompare);
                });
        };

        $creneau = function ($q) use ($jourCompare, $heureDebut, $heureFin, $seanceIgnoree, $emploiDuTempsEnVigueur) {
            $q->where('date_seance', $jourCompare)
                ->where('is_active', true)
                ->where('heure_debut', '<', $heureFin)
                ->where('heure_fin', '>', $heureDebut)
                ->whereHas('emploiTemps', $emploiDuTempsEnVigueur);

            if ($seanceIgnoree !== null) {
                $q->where('id', '!=', $seanceIgnoree);
            }
        };

        $conflits = [];

        if ($teacherId) {
            $occupe = ESBTPSeanceCours::query()
                ->where('teacher_id', $teacherId)
                ->where($creneau)
                ->exists();

            if ($occupe) {
                $nom = ESBTPTeacher::with('user')->find($teacherId)?->user?->name;
                $conflits[] = $nom
                    ? "L'enseignant {$nom} a déjà un cours à cet horaire sur un emploi du temps actif."
                    : 'Cet enseignant a déjà un cours à cet horaire sur un emploi du temps actif.';
            }
        }

        if ($salle !== null && $salle !== '') {
            $occupee = ESBTPSeanceCours::query()
                ->where('salle', $salle)
                ->where($creneau)
                ->exists();

            if ($occupee) {
                $conflits[] = "La salle {$salle} est déjà occupée à cet horaire.";
            }
        }

        $classeOccupee = ESBTPSeanceCours::query()
            ->where('classe_id', $this->emploiTemps->classe_id)
            ->where($creneau)
            ->exists();

        if ($classeOccupee) {
            $conflits[] = 'La classe a déjà une séance programmée à cet horaire.';
        }

        return $conflits;
    }
}
