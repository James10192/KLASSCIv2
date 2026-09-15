<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPSeanceCours;
use Illuminate\Support\Facades\DB;

/**
 * Les séances sans date, ce qu'elles coûtent, et comment les rattraper.
 *
 * ## Le défaut, et pourquoi il ne se voit pas
 *
 * `esbtp_seance_cours.date_seance` est nullable, et le formulaire d'ajout de
 * l'écran emploi du temps ne l'écrivait pas — de mai 2025 à septembre 2026.
 * Aucun observateur ne la remplit ensuite.
 *
 * Or c'est cette colonne, et non `jour`, que lit tout ce qui raisonne par date :
 * `TeacherHoursService` la filtre en `whereNotNull`, donc la séance ne compte ni
 * dans le récapitulatif des heures ni dans le bulletin de paie ; l'émargement se
 * rattache par date, donc ne peut pas s'y accrocher ; le contrôle de conflit la
 * compare à une date. La séance s'affiche normalement à l'écran et n'existe pour
 * personne d'autre : il n'y a ni erreur, ni trace, ni case vide — d'où ce relevé,
 * qui est le seul moyen de savoir combien il y en a.
 *
 * ## Ce que le rattrapage peut, et ce qu'il ne peut pas
 *
 * La date se recalcule par `ESBTPEmploiTemps::dateDuJour()`, la formule unique
 * du domaine. Elle échoue — et rend `null` — dans deux cas qu'aucun rattrapage
 * ne peut trancher : un `jour` illisible, et un emploi du temps sans date de
 * début. Ces séances sont comptées à part et laissées telles quelles ; poser une
 * date approchée les ferait entrer dans la paie avec une valeur inventée, ce qui
 * est pire que l'absence.
 */
class DiagnosticDesDatesDeSeance
{
    /**
     * Ce que l'on perd, sans rien modifier.
     *
     * @param  int  $limite  nombre de lignes détaillées ; les totaux, eux, portent sur tout
     * @return array<string, mixed>
     */
    public function rapport(int $limite = 200): array
    {
        $total = $this->requete()->count();

        $parEnseignant = [];
        $rattrapables = 0;
        $irrattrapables = [];
        $detail = [];

        $this->requete()
            ->with(['emploiTemps', 'teacher.user', 'matiere', 'classe'])
            ->orderBy('id')
            ->chunkById(500, function ($seances) use (&$parEnseignant, &$rattrapables, &$irrattrapables, &$detail, $limite) {
                foreach ($seances as $seance) {
                    $heures = self::dureeEnHeures($seance);
                    $cle = $seance->teacher_id ?: 0;

                    $parEnseignant[$cle] ??= [
                        'teacher_id' => $seance->teacher_id,
                        'enseignant' => $seance->teacher?->user?->name ?? '(aucun enseignant affecté)',
                        'seances' => 0,
                        'heures' => 0.0,
                    ];
                    $parEnseignant[$cle]['seances']++;
                    $parEnseignant[$cle]['heures'] += $heures;

                    $date = $seance->emploiTemps?->dateDuJour($seance->jour);

                    if ($date === null) {
                        $raison = self::raisonDeLEchec($seance->emploiTemps, $seance->jour);
                        $irrattrapables[$raison] = ($irrattrapables[$raison] ?? 0) + 1;
                    } else {
                        $rattrapables++;
                    }

                    if (count($detail) < $limite) {
                        $detail[] = [
                            'seance_id' => $seance->id,
                            'emploi_temps_id' => $seance->emploi_temps_id,
                            'classe' => $seance->classe?->name,
                            'matiere' => $seance->matiere?->name,
                            'enseignant' => $seance->teacher?->user?->name,
                            'jour' => $seance->jour,
                            // Brutes, pour la même raison que la durée : l'attribut
                            // casté rendrait « 2026-09-15 08:00:00 » — une date
                            // du jour collée devant l'heure, dans un rapport qui
                            // sert justement à traquer des dates manquantes.
                            'heure_debut' => $seance->getAttributes()['heure_debut'] ?? null,
                            'heure_fin' => $seance->getAttributes()['heure_fin'] ?? null,
                            'date_calculable' => $date?->toDateString(),
                        ];
                    }
                }
            });

        usort($parEnseignant, fn ($a, $b) => $b['heures'] <=> $a['heures']);

        foreach ($parEnseignant as &$ligne) {
            $ligne['heures'] = round($ligne['heures'], 2);
        }
        unset($ligne);

        return [
            'total_sans_date' => $total,
            'rattrapables' => $rattrapables,
            'irrattrapables' => $irrattrapables,
            'heures_perdues' => round(array_sum(array_column($parEnseignant, 'heures')), 2),
            'par_enseignant' => array_values($parEnseignant),
            'detail' => $detail,
            'limite_detail' => $limite,
        ];
    }

    /**
     * Pose la date manquante là où elle se calcule.
     *
     * Simule par défaut : `$appliquer` doit être demandé explicitement. Un
     * rattrapage qui écrit sans qu'on l'ait demandé est exactement le geste
     * qu'on ne veut pas offrir sur huit instances en service.
     *
     * N'écrit QUE `date_seance`, et seulement là où elle est nulle : la
     * commande est donc rejouable, et ne peut pas déplacer une séance qui a
     * déjà une date.
     *
     * @return array<string, mixed>
     */
    public function rattraper(bool $appliquer = false, ?int $emploiTempsId = null): array
    {
        $posees = 0;
        $laissees = [];

        $requete = $this->requete()->with('emploiTemps');

        if ($emploiTempsId !== null) {
            $requete->where('emploi_temps_id', $emploiTempsId);
        }

        DB::transaction(function () use ($requete, $appliquer, &$posees, &$laissees) {
            $requete->orderBy('id')->chunkById(500, function ($seances) use ($appliquer, &$posees, &$laissees) {
                foreach ($seances as $seance) {
                    $date = $seance->emploiTemps?->dateDuJour($seance->jour);

                    if ($date === null) {
                        $raison = self::raisonDeLEchec($seance->emploiTemps, $seance->jour);
                        $laissees[$raison] = ($laissees[$raison] ?? 0) + 1;

                        continue;
                    }

                    if ($appliquer) {
                        // `saveQuietly` : la séance n'a pas changé de contenu,
                        // seulement recouvré une donnée qui aurait dû être là.
                        // Réveiller les observateurs rejouerait des effets de
                        // création sur des lignes anciennes.
                        $seance->date_seance = $date;
                        $seance->saveQuietly();
                    }

                    $posees++;
                }
            });

            if (! $appliquer) {
                // Rien n'a été écrit, mais on annule quand même : si un jour
                // une branche de ce parcours écrit sans passer par `$appliquer`,
                // elle sera annulée au lieu d'être découverte en production.
                DB::rollBack();
            }
        });

        return [
            'applique' => $appliquer,
            'dates_posees' => $posees,
            'laissees_sans_date' => $laissees,
        ];
    }

    /**
     * Pourquoi la date n'a pas pu être recalculée.
     *
     * Publique et statique parce que c'est la seule DÉCISION de cette classe
     * qui ne dépende pas de la base : le reste est une requête. La nommer ici
     * évite aussi qu'elle soit recopiée entre le relevé et le rattrapage —
     * deux libellés divergents feraient deux rapports qui ne se recoupent pas.
     */
    public static function raisonDeLEchec(?ESBTPEmploiTemps $emploiTemps, mixed $jour): string
    {
        if ($emploiTemps === null) {
            return 'emploi du temps introuvable';
        }

        if (! $emploiTemps->date_debut) {
            return 'emploi du temps sans date de début';
        }

        // La période existe et le jour n'a pas donné de date : il ne désigne
        // aucun jour de la semaine ouvrée.
        return 'jour illisible';
    }

    /**
     * La durée d'une séance, en heures décimales.
     *
     * Publique et statique pour la même raison : c'est de l'arithmétique, et
     * c'est elle qui chiffre ce que la paie ne voit pas. Rend zéro plutôt
     * qu'un négatif ou un faux total quand les bornes manquent ou s'inversent.
     */
    public static function dureeEnHeures(ESBTPSeanceCours $seance): float
    {
        // Les valeurs BRUTES, et non `$seance->heure_debut`. Ces colonnes sont
        // castées en `datetime`, et sous ce cast une heure NULLE ne se lit pas
        // `null` : `asDateTime(null)` rend l'instant présent. Lire l'attribut
        // casté donnerait donc une durée calculée sur l'heure qu'il est.
        $brutes = $seance->getAttributes();

        $debut = self::secondeDeLaJournee($brutes['heure_debut'] ?? null);
        $fin = self::secondeDeLaJournee($brutes['heure_fin'] ?? null);

        if ($debut === null || $fin === null || $fin <= $debut) {
            return 0.0;
        }

        return ($fin - $debut) / 3600;
    }

    /**
     * L'heure de la journée, en secondes depuis minuit.
     *
     * `heure_debut` et `heure_fin` sont des colonnes `time`, mais le modèle les
     * caste en `datetime` : les lire rend un `Carbon` daté d'AUJOURD'HUI, pas
     * une chaîne « 08:00 ». Une découpe de chaîne y lisait donc la date et
     * rendait zéro heure pour toutes les séances — ce que le test a attrapé.
     *
     * On ne soustrait pas deux `Carbon` non plus : ils portent la même date ici,
     * mais rien ne le garantit si le cast change. Seule l'heure du jour compte.
     */
    private static function secondeDeLaJournee(mixed $heure): ?int
    {
        if ($heure instanceof \DateTimeInterface) {
            return (int) $heure->format('H') * 3600
                + (int) $heure->format('i') * 60
                + (int) $heure->format('s');
        }

        if (! is_string($heure) || ! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', trim($heure), $m)) {
            return null;
        }

        return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) ($m[3] ?? 0);
    }
}
