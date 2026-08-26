<?php

namespace App\Services;

use App\Models\ESBTPResultat;

class BulletinSectionSummary
{
    /** @var array<string, array<int, float>> */
    private static array $classAverages = [];

    /**
     * @param  iterable<int, object>  $resultats
     * @param  array<int|string, array{total_heures?: float|int}>  $absencesParMatiere
     * @param  array{etudiant_id: int, classe_id: int, annee_id: int, periode: string}|null  $rankContext
     * @return array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}
     */
    /**
     * @param  iterable<int, object>  $resultatsGeneraux
     * @param  iterable<int, object>  $resultatsTechniques
     * @param  array<int|string, array{total_heures?: float|int}>  $absencesParMatiere
     * @param  array{etudiant_id: int, classe_id: int, annee_id: int, periode: string}|null  $rankContext
     * @return array{general: array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}, technical: array{moyenne: ?float, coefficient: float, weighted: float, absences: float, rang: ?int}}
     */
    public function pair(
        iterable $resultatsGeneraux,
        iterable $resultatsTechniques,
        array $absencesParMatiere,
        ?float $moyenneGenerale,
        ?float $moyenneTechnique,
        ?array $rankContext = null
    ): array {
        return [
            'general' => $this->forSection($resultatsGeneraux, $absencesParMatiere, $moyenneGenerale, $rankContext),
            'technical' => $this->forSection($resultatsTechniques, $absencesParMatiere, $moyenneTechnique, $rankContext),
        ];
    }

    public function forSection(
        iterable $resultats,
        array $absencesParMatiere,
        ?float $officialAverage,
        ?array $rankContext = null
    ): array {
        $coefficient = 0.0;
        $weighted = 0.0;
        $absences = 0.0;
        $matiereIds = [];

        foreach ($resultats as $resultat) {
            $coef = (float) ($resultat->coefficient ?? 0);
            $moyenne = (float) ($resultat->moyenne ?? 0);
            $coefficient += $coef;
            $weighted += $moyenne * $coef;
            $matiereId = (int) ($resultat->matiere_id ?? 0);
            if ($matiereId <= 0) {
                continue;
            }
            $matiereIds[] = $matiereId;
            $absences += (float) (
                $absencesParMatiere[$matiereId]['total_heures']
                ?? $absencesParMatiere[(string) $matiereId]['total_heures']
                ?? 0
            );
        }

        $average = $officialAverage ?? ($coefficient > 0 ? $weighted / $coefficient : null);
        $rang = null;
        if ($rankContext !== null && $average !== null && $matiereIds !== []) {
            $rang = $this->rank(
                $matiereIds,
                $average,
                (int) $rankContext['etudiant_id'],
                (int) $rankContext['classe_id'],
                (int) $rankContext['annee_id'],
                (string) $rankContext['periode']
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
     * @param  list<int>  $matiereIds
     */
    public function rank(
        array $matiereIds,
        float $target,
        int $etudiantId,
        int $classeId,
        int $anneeId,
        string $periode
    ): ?int {
        if ($matiereIds === [] || $classeId <= 0 || $anneeId <= 0) {
            return null;
        }

        $averages = $this->classSectionAverages($matiereIds, $classeId, $anneeId, $periode);
        $averages[$etudiantId] = $target;

        return self::rankAmong($averages, $target);
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

    /**
     * @param  list<int>  $matiereIds
     * @return array<int, float>
     */
    private function classSectionAverages(array $matiereIds, int $classeId, int $anneeId, string $periode): array
    {
        $matiereIds = array_values(array_unique(array_map('intval', $matiereIds)));
        sort($matiereIds);
        $key = $classeId.'|'.$anneeId.'|'.$periode.'|'.implode(',', $matiereIds);
        if (isset(self::$classAverages[$key])) {
            return self::$classAverages[$key];
        }

        $bulletinService = app(BulletinService::class);
        $rows = ESBTPResultat::query()
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', $bulletinService->periodeAliases($periode))
            ->whereIn('matiere_id', $matiereIds)
            ->whereNotNull('moyenne')
            ->get(['etudiant_id', 'moyenne', 'coefficient']);

        $totals = [];
        foreach ($rows as $row) {
            $id = (int) $row->etudiant_id;
            $totals[$id]['weighted'] = ($totals[$id]['weighted'] ?? 0) + ((float) $row->moyenne * (float) $row->coefficient);
            $totals[$id]['coefficient'] = ($totals[$id]['coefficient'] ?? 0) + (float) $row->coefficient;
        }

        $averages = [];
        foreach ($totals as $id => $total) {
            if ($total['coefficient'] > 0) {
                $averages[$id] = $total['weighted'] / $total['coefficient'];
            }
        }

        return self::$classAverages[$key] = $averages;
    }
}
