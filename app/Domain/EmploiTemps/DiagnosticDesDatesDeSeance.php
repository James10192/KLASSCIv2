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
     * La population du défaut : les séances qui n'ont pas de date.
     *
     * Sa portée n'est pas « tout ce qui a `date_seance` nulle », mais
     * **exactement ce que la paie compterait si la date était là**. C'est ce
     * qui rend le chiffre du relevé utilisable pour décider d'un rattrapage :
     * un total plus large serait une alerte qu'on ne peut pas recouper.
     *
     * D'où les deux bornes, copiées de `TeacherHoursService::seancesDeLaPeriode()`,
     * la requête qui alimente le bulletin de paie :
     *
     *  - **récréations et pauses déjeuner exclues** (`type` `break` / `lunch`).
     *    Elles n'ont jamais compté d'heures d'enseignement ; les faire figurer
     *    au relevé gonflerait le total d'heures « perdues » avec des heures que
     *    personne n'a jamais dû payer.
     *  - **séances supprimées exclues**, par la portée globale de `SoftDeletes`
     *    que porte le modèle. Rien à écrire : c'est le comportement par défaut
     *    de `query()`, et il est juste ici.
     *
     * Ce qui n'est PAS filtré, et pourquoi : `is_active`. La paie ne le filtre
     * pas non plus — une séance désactivée après avoir été faite reste due.
     * Filtrer ici et pas là ferait diverger les deux comptes.
     */
    private function requete(?int $emploiTempsId = null): \Illuminate\Database\Eloquent\Builder
    {
        return ESBTPSeanceCours::query()
            ->whereNull('date_seance')
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->when($emploiTempsId !== null, fn ($q) => $q->where('emploi_temps_id', $emploiTempsId));
    }

    /**
     * Ce que l'on perd, sans rien modifier.
     *
     * @param  int  $limite  nombre de lignes détaillées ; les totaux, eux, portent sur tout
     * @param  int|null  $emploiTempsId  restreint le relevé au même périmètre que le rattrapage.
     *                                   Sans lui, la confirmation d'écriture annonçait des totaux
     *                                   globaux au moment où l'opérateur décide d'écrire sur un
     *                                   seul emploi du temps — un chiffre juste, sur le mauvais
     *                                   périmètre, à l'instant précis où il engage.
     * @return array<string, mixed>
     */
    public function rapport(int $limite = 200, ?int $emploiTempsId = null): array
    {
        $total = $this->requete($emploiTempsId)->count();

        $parEnseignant = [];
        $rattrapables = 0;
        $irrattrapables = [];
        $detail = [];

        $this->requete($emploiTempsId)
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

        $requete = $this->requete($emploiTempsId)->with('emploiTemps');

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
        // Les valeurs BRUTES, et non `$seance->heure_debut` : ces colonnes sont
        // castées en `datetime`, donc l'attribut rend un Carbon daté d'AUJOURD'HUI
        // et toute découpe de chaîne y lit la date. C'est ce qui rendait zéro
        // heure pour toutes les séances.
        //
        // Un second effet existe — une heure nulle rend l'instant présent plutôt
        // que `null` — mais il ne concerne QUE les objets en mémoire : la
        // migration d'origine déclare `$table->time('heure_debut')` et
        // `$table->time('heure_fin')` sans `nullable()`, et aucune migration
        // ultérieure ne les relâche. Une séance aux horaires vides ne peut donc
        // pas exister en base. Le garde ci-dessous le couvre quand même, parce
        // qu'il ne coûte rien ; ce n'est pas une population à aller corriger.
        //
        // Le premier effet, lui, est bien général et vivant AILLEURS — une
        // dizaine de lectures en contexte chaîne (`substr($seance->heure_debut,
        // 0, 5)` rend « 2026- »), dont l'avis d'absence envoyé au parent et
        // l'export CSV des présences. Antérieur à ce chantier, hors de son
        // périmètre, et suivi à part : ne pas le corriger ici sans le mesurer.
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
