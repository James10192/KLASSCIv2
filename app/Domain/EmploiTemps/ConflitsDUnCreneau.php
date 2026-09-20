<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use Closure;

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
 * Et l'erreur qui reste possible — appeler `pourUneNouvelleSeance()` sur une
 * modification — échoue FERMÉ : la séance se voit elle-même et refuse.
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

        $creneau = $this->creneauConcurrent($jour, $dateSeance->toDateString(), $heureDebut, $heureFin, $seanceIgnoree);

        $conflits = [];

        if ($teacherId && $this->occupe($creneau, fn ($q) => $q->where('teacher_id', $teacherId))) {
            $nom = ESBTPTeacher::with('user')->find($teacherId)?->user?->name;
            $conflits[] = $nom
                ? "L'enseignant {$nom} a déjà un cours à cet horaire sur un emploi du temps actif."
                : 'Cet enseignant a déjà un cours à cet horaire sur un emploi du temps actif.';
        }

        if ($salle !== null && $salle !== '' && $this->occupe($creneau, fn ($q) => $q->where('salle', $salle))) {
            $conflits[] = "La salle {$salle} est déjà occupée à cet horaire.";
        }

        if ($this->occupe($creneau, fn ($q) => $q->where('classe_id', $this->emploiTemps->classe_id))) {
            $conflits[] = 'La classe a déjà une séance programmée à cet horaire.';
        }

        return $conflits;
    }

    /**
     * Existe-t-il une séance de cette portée sur le créneau ?
     *
     * Les trois recherches ne différaient que par une clause ; elles étaient
     * recopiées à l'identique pour le reste, et une correction sur une seule
     * des trois aurait laissé deux angles morts.
     */
    private function occupe(Closure $creneau, Closure $portee): bool
    {
        return ESBTPSeanceCours::query()->where($portee)->where($creneau)->exists();
    }

    /**
     * Ce qui, dans la base, occupe déjà ce créneau.
     *
     * ## Les deux façons dont une séance dit sa date, et pourquoi les deux comptent
     *
     * `date_seance` est la colonne de référence, mais elle est **nullable** et
     * `storeSession()` ne l'écrivait pas avant septembre 2026 : toute séance
     * saisie depuis l'écran emploi du temps depuis mai 2025 la porte à `null`.
     * Aucune migration ne les rattrape — le rattrapage est une commande, et tant
     * qu'elle n'a pas tourné sur une instance, ces lignes existent.
     *
     * Une égalité stricte sur `date_seance` ne les voit donc pas, et le garde
     * rendrait « aucun conflit » sur le chemin de saisie principal : le silence
     * qui ressemble à une autorisation, celui-là même que refuse le contrôle de
     * date illisible vingt lignes plus haut. D'où la seconde branche, qui
     * rattrape ces lignes par leur `jour` — les deux écritures comprises.
     *
     * La période est déjà bornée par `whereHas('emploiTemps', …)` : un `jour`
     * sans date ne peut donc pas ramener une séance d'un emploi du temps qui ne
     * couvre pas la date visée.
     */
    private function creneauConcurrent(
        mixed $jour,
        string $jourCompare,
        string $heureDebut,
        string $heureFin,
        ?int $seanceIgnoree,
    ): Closure {
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

        return function ($q) use ($jour, $jourCompare, $heureDebut, $heureFin, $seanceIgnoree, $emploiDuTempsEnVigueur) {
            $q->where('is_active', true)
                ->where('heure_debut', '<', $heureFin)
                ->where('heure_fin', '>', $heureDebut)
                ->whereHas('emploiTemps', $emploiDuTempsEnVigueur)
                ->where(function ($datee) use ($jour, $jourCompare) {
                    // Comparée en chaîne de date, et non en `Carbon` : la colonne
                    // est un `date`, et lier un `Carbon` y envoyait
                    // « Y-m-d H:i:s ». Le résultat était juste — il tenait
                    // seulement à ce que l'heure soit minuit.
                    $datee->where('date_seance', $jourCompare)
                        ->orWhere(fn ($heritee) => $heritee->whereNull('date_seance')
                            ->whereIn('jour', JourDeLaSemaine::ecrituresDe($jour)));
                });

            if ($seanceIgnoree !== null) {
                $q->where('id', '!=', $seanceIgnoree);
            }
        };
    }
}
