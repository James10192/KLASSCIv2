<?php

namespace App\Services;

use App\Models\ESBTPResultatMatiere;

class BulletinSectionSummary
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  iterable<int, object>  $resultatsGeneraux
     * @param  iterable<int, object>  $resultatsTechniques
     * @param  array<int|string, array{total_heures?: float|int}>  $absencesParMatiere
     * @param  array{etudiant_id: int, classe_id: int, annee_id: int, periode: string}|null  $rankContext
     * @return array{general: ?array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}, technical: ?array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}}
     */
    public static function forView(
        array $settings,
        iterable $resultatsGeneraux,
        iterable $resultatsTechniques,
        array $absencesParMatiere,
        ?float $moyenneGenerale,
        ?float $moyenneTechnique,
        ?array $rankContext
    ): array {
        if (($settings['bulletin_show_section_averages'] ?? '1') != '1') {
            return ['general' => null, 'technical' => null];
        }

        $summary = new self;

        return [
            'general' => $summary->forSection($resultatsGeneraux, $absencesParMatiere, $moyenneGenerale, $rankContext),
            'technical' => $summary->forSection($resultatsTechniques, $absencesParMatiere, $moyenneTechnique, $rankContext),
        ];
    }

    /**
     * @param  iterable<int, object>  $resultats
     * @param  array<int|string, array{total_heures?: float|int}>  $absencesParMatiere
     * @param  array{etudiant_id: int, classe_id: int, annee_id: int, periode: string}|null  $rankContext
     * @return array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}
     */
    public function forSection(
        iterable $resultats,
        array $absencesParMatiere,
        ?float $officialAverage,
        ?array $rankContext = null
    ): array {
        $coefficient = 0.0;
        $weighted = 0.0;
        $absences = 0.0;
        $matiereCoefficients = [];
        $subjectRank = null;

        foreach ($resultats as $resultat) {
            $coef = (float) ($resultat->coefficient ?? 0);
            $matiereId = (int) ($resultat->matiere_id ?? 0);

            // Une matiere dispensee ou non notee reste AFFICHEE sur le
            // bulletin — c'est tout l'objet du lot — mais elle ne doit entrer
            // dans aucun total. Sans ce filtre, son coefficient s'ajoutait au
            // denominateur pendant que sa moyenne nulle valait zero point : la
            // ligne de synthese imprimait « Moyenne 13,50 · Coef 8 · M×C 54,00 »,
            // et 54/8 = 6,75. Le document se contredisait sur une seule ligne,
            // sans erreur, devant une famille.
            //
            // Meme raison pour le jeu de matieres du rang : classer un eleve sur
            // une matiere dont il est dispense n'a pas de sens.
            if (! ESBTPResultatMatiere::ligneNotee($resultat)) {
                // Les heures d'absence, elles, restent comptees : ce sont des
                // heures reellement manquees, la ligne les affiche, et les
                // retirer du total de section rendrait la colonne fausse.
                if ($matiereId > 0) {
                    $absences += (float) (
                        $absencesParMatiere[$matiereId]['total_heures']
                        ?? $absencesParMatiere[(string) $matiereId]['total_heures']
                        ?? 0
                    );
                }

                continue;
            }

            $moyenne = (float) ($resultat->moyenne ?? 0);
            $coefficient += $coef;
            $weighted += $moyenne * $coef;
            if ($matiereId <= 0) {
                continue;
            }
            $matiereCoefficients[$matiereId] = $coef;
            if (is_numeric($resultat->rang ?? null)) {
                $subjectRank = (int) $resultat->rang;
            }
            $absences += (float) (
                $absencesParMatiere[$matiereId]['total_heures']
                ?? $absencesParMatiere[(string) $matiereId]['total_heures']
                ?? 0
            );
        }

        $average = $officialAverage ?? ($coefficient > 0 ? $weighted / $coefficient : null);
        $rang = null;
        if (count($matiereCoefficients) === 1 && $subjectRank !== null) {
            $rang = $subjectRank;
        } elseif ($rankContext !== null && $average !== null && $matiereCoefficients !== []) {
            $rang = app(BulletinService::class)->rankAmongMatiereSet(
                $matiereCoefficients,
                (int) $rankContext['etudiant_id'],
                (int) $rankContext['classe_id'],
                (int) $rankContext['annee_id'],
                (string) $rankContext['periode'],
                $average
            );
        }

        return [
            'moyenne' => $average,
            'coefficient' => $coefficient,
            'weighted' => $weighted,
            'absences' => $absences,
            'rang' => $rang,
        ];
    }

    /**
     * @param  array<int, float>  $averages
     */
    public static function rankAmong(array $averages, float $target): int
    {
        $rank = 1;
        foreach ($averages as $average) {
            if ($average > $target) {
                $rank++;
            }
        }

        return $rank;
    }
}
