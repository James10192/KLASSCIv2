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
     * L'option `--emploi-temps` des deux commandes, validée en un seul endroit.
     *
     * Les deux commandes portent cette option sous le même nom et la même
     * sémantique, et ce constat était écrit dans leur docbloc sans rien en
     * tirer : la garde n'existait que sur celle qui LIT. Celle qui écrit en
     * masse gardait un `(int)` nu, et `(int) '12O'` vaut `0` — la requête
     * cherchait alors l'emploi du temps 0, n'en trouvait aucun, et la commande
     * annonçait « 0 séance à écrire » comme une bonne nouvelle. C'est le repli
     * muet que cette classe existe pour interdire, sur son outil le plus
     * dangereux.
     *
     * Rendre `null` signifie « aucune restriction », jamais « identifiant
     * illisible » : ce dernier cas lève.
     *
     * @throws \InvalidArgumentException si la valeur n'est pas un entier, ou ne
     *                                   désigne aucun emploi du temps
     */
    public static function perimetre(mixed $option): ?int
    {
        // L'option ABSENTE (null) vaut « toute l'instance ». Une option PRÉSENTE
        // mais vide (`--emploi-temps=`) est une frappe incomplète, pas cette
        // intention-là, et le silence lui coûterait cher : sur la commande qui
        // écrit en masse, la lire comme null ferait porter le rattrapage sur
        // toute l'instance alors que l'ancienne conversion muette (`(int) ''`)
        // n'écrivait nulle part. On ne retourne pas une conduite par accident.
        if ($option === null) {
            return null;
        }

        if ($option === '') {
            throw new \InvalidArgumentException(
                '--emploi-temps attend un identifiant numérique, reçu une valeur vide. '
                .'Retirez l\'option pour porter sur toute l\'instance.'
            );
        }

        if (! ctype_digit(ltrim((string) $option, '+'))) {
            throw new \InvalidArgumentException(
                sprintf('--emploi-temps attend un identifiant numérique, reçu « %s ».', $option)
            );
        }

        $id = (int) $option;

        if (! ESBTPEmploiTemps::whereKey($id)->exists()) {
            throw new \InvalidArgumentException(sprintf('Emploi du temps %d introuvable.', $id));
        }

        return $id;
    }

    /**
     * La population du défaut : les séances qui n'ont pas de date.
     *
     * Sa portée est celle de la paie **moins le filtre enseignant**, et c'est
     * délibéré. Les deux bornes reprises de
     * `TeacherHoursService::seancesDeLaPeriode()` :
     *
     *  - **récréations et pauses déjeuner exclues** (`type` `break` / `lunch`).
     *    Elles n'ont jamais compté d'heures d'enseignement ; les faire figurer
     *    au relevé gonflerait le total d'heures « perdues » avec des heures que
     *    personne n'a jamais dû payer.
     *  - **séances supprimées exclues**, par la portée globale de `SoftDeletes`
     *    que porte le modèle. Rien à écrire : c'est le comportement par défaut
     *    de `query()`, et il est juste ici.
     *
     * Ce qui n'est PAS filtré, et pourquoi :
     *
     *  - `is_active` — la paie ne le filtre pas non plus. Une séance désactivée
     *    après avoir été faite reste due ; filtrer ici et pas là ferait diverger
     *    les deux comptes.
     *  - **`teacher_id`**, que la paie exige pourtant (`->where('teacher_id', …)`
     *    et `->whereNotNull('teacher_id')` selon la méthode). Le relevé le laisse
     *    passer parce qu'une séance sans enseignant mérite d'être vue : c'est
     *    une population réelle et non marginale, `ESBTPSeanceCoursController`
     *    posant `teacher_id = null` sur tout `type = 'homework'`, c'est-à-dire
     *    sur TOUTES les évaluations LMD (examen, partiel, rattrapage, soutenance).
     *
     *  - **la période**, que la paie borne aussi (`whereDate` entre deux dates).
     *    Elle est dégénérée ici — il n'y a pas de date à borner, c'est le sujet
     *    du relevé — mais sa conséquence ne l'est pas : `heures_recoupables_paie`
     *    porte sur toute la vie de l'instance, et une fois datées, ces heures
     *    tomberont dans la période de LEUR date, qui peut être une paie déjà
     *    close ou une année antérieure. « Recoupable » dit donc que la paie
     *    saurait quoi en faire, pas qu'elle les paiera ce mois-ci.
     *
     * **Conséquence directe sur la lecture du rapport, à ne pas perdre de vue :**
     * son total d'heures est donc PLUS LARGE que ce que la paie recouperait. Le
     * rapport ne publie pas ce total seul — il le scinde en
     * `heures_recoupables_paie` et `heures_sans_enseignant`, et la commande
     * affiche les deux. Annoncer la somme sous un libellé « heures dues » ferait
     * décider d'une écriture de masse sur un chiffre que personne ne peut
     * rapprocher d'un bulletin.
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
                    $this->releverUneSeance($seance, $limite, $parEnseignant, $rattrapables, $irrattrapables, $detail);
                }
            });

        usort($parEnseignant, fn ($a, $b) => $b['heures'] <=> $a['heures']);

        foreach ($parEnseignant as &$ligne) {
            $ligne['heures'] = round($ligne['heures'], 2);
        }
        unset($ligne);

        [$recoupables, $sansEnseignant] = self::partageDesHeures($parEnseignant);

        return [
            'total_sans_date' => $total,
            'rattrapables' => $rattrapables,
            'irrattrapables' => $irrattrapables,
            'heures_perdues' => round($recoupables + $sansEnseignant, 2),
            'heures_recoupables_paie' => round($recoupables, 2),
            'heures_sans_enseignant' => round($sansEnseignant, 2),
            'par_enseignant' => array_values($parEnseignant),
            'detail' => $detail,
            'limite_detail' => $limite,
        ];
    }

    /**
     * Ce qu'une séance ajoute au relevé : ses heures, sa réparabilité, sa ligne.
     *
     * Extraite de la fermeture de `chunkById` : celle-ci portait quarante lignes
     * et faisait passer `rapport()` au-dessus du seuil de méthode. Les quatre
     * accumulateurs restent passés par référence — ils traversent les lots.
     *
     * @param  array<int|string, array<string, mixed>>  $parEnseignant
     * @param  array<string, int>  $irrattrapables
     * @param  list<array<string, mixed>>  $detail
     */
    private function releverUneSeance(
        ESBTPSeanceCours $seance,
        int $limite,
        array &$parEnseignant,
        int &$rattrapables,
        array &$irrattrapables,
        array &$detail,
    ): void {
        // `?? 0` et non `?: 0` : `?:` confondrait l'identifiant 0 avec l'absence
        // d'enseignant, et ferait fusionner les deux sous la même ligne — le
        // partage des heures dépendrait alors de l'ordre de lecture.
        $cle = $seance->teacher_id ?? 0;

        $parEnseignant[$cle] ??= [
            'teacher_id' => $seance->teacher_id,
            'enseignant' => $seance->teacher?->user?->name ?? '(aucun enseignant affecté)',
            'seances' => 0,
            'heures' => 0.0,
        ];
        $parEnseignant[$cle]['seances']++;
        $parEnseignant[$cle]['heures'] += self::dureeEnHeures($seance);

        $date = $seance->emploiTemps?->dateDuJour($seance->jour);

        if ($date === null) {
            $raison = self::raisonDeLEchec($seance->emploiTemps, $seance->jour);
            $irrattrapables[$raison] = ($irrattrapables[$raison] ?? 0) + 1;
        } else {
            $rattrapables++;
        }

        if (count($detail) >= $limite) {
            return;
        }

        $detail[] = [
            'seance_id' => $seance->id,
            'emploi_temps_id' => $seance->emploi_temps_id,
            'classe' => $seance->classe?->name,
            'matiere' => $seance->matiere?->name,
            'enseignant' => $seance->teacher?->user?->name,
            'jour' => $seance->jour,
            // Brutes, pour la même raison que la durée : l'accesseur du modèle
            // rendrait « 2026-09-15 08:00:00 » — une date du jour collée devant
            // l'heure, dans un rapport qui traque justement des dates manquantes.
            'heure_debut' => $seance->getAttributes()['heure_debut'] ?? null,
            'heure_fin' => $seance->getAttributes()['heure_fin'] ?? null,
            'date_calculable' => $date?->toDateString(),
        ];
    }

    /**
     * Les heures qu'un bulletin pourra recouper, et celles qu'il ne pourra pas.
     *
     * La paie exige un `teacher_id` ; les séances qui n'en ont pas — toutes les
     * évaluations LMD en font partie — sont un vrai défaut à voir, mais leurs
     * heures ne se rapprocheront d'aucun bulletin. Les additionner sous un
     * libellé unique ferait engager une écriture de masse sur un chiffre
     * irrécupérable de moitié.
     *
     * @param  array<int|string, array<string, mixed>>  $parEnseignant
     * @return array{0: float, 1: float}
     */
    private static function partageDesHeures(array $parEnseignant): array
    {
        $recoupables = 0.0;
        $sansEnseignant = 0.0;

        foreach ($parEnseignant as $ligne) {
            if ($ligne['teacher_id'] === null) {
                $sansEnseignant += $ligne['heures'];
            } else {
                $recoupables += $ligne['heures'];
            }
        }

        return [$recoupables, $sansEnseignant];
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
        // Les valeurs BRUTES, et non `$seance->heure_debut` : le modèle déclare
        // un ACCESSEUR `getHeureDebutAttribute()` qui fait `Carbon::parse()`,
        // donc l'attribut rend un Carbon daté d'AUJOURD'HUI et toute découpe de
        // chaîne y lit la date. C'est ce qui rendait zéro heure partout.
        //
        // La cause est bien l'accesseur, PAS le cast `'datetime'` que porte
        // aussi le modèle : un accesseur passe avant le cast, qui est donc
        // inerte ici. Y toucher ne changerait rien — voir le piège #14 de
        // `klassci-debugging-discipline.md`.
        //
        // Un second effet existe — une heure nulle rend l'instant présent plutôt
        // que `null`, par ce même `Carbon::parse(null)` — mais il ne concerne
        // QUE les objets en mémoire : la
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
     * `heure_debut` et `heure_fin` sont des colonnes `time`, mais le modèle
     * déclare un accesseur `getHeureDebutAttribute()` qui fait `Carbon::parse()` :
     * les lire rend un `Carbon` daté d'AUJOURD'HUI, pas une chaîne « 08:00 ».
     * Une découpe de chaîne y lisait donc la date et rendait zéro heure pour
     * toutes les séances — ce que le test a attrapé.
     *
     * Le cast `'datetime'` homonyme que porte aussi le modèle n'y est pour rien,
     * et n'y peut rien : un accesseur passe avant lui, il est inerte. Le
     * débrancher ne changerait pas cette lecture d'un caractère.
     *
     * On ne soustrait pas deux `Carbon` non plus : ils portent la même date ici,
     * mais rien ne le garantit si l'accesseur change. Seule l'heure du jour compte.
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
