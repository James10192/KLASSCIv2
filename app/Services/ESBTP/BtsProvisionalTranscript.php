<?php

namespace App\Services\ESBTP;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;

/**
 * Projection provisoire : aucune mutation ni publication.
 * Réutilise la même source de calcul que les résultats académiques BTS,
 * notamment pour le passage tronc commun (S1) vers spécialité (S2).
 */
final class BtsProvisionalTranscript
{
    public function __construct(private readonly BtsCurrentResultSnapshotService $snapshots) {}

    /** @return array<int,array<string,mixed>> */
    public function sections(
        int $etudiantId,
        ESBTPInscription $inscription,
        string $periode,
        BtsStudentAcademicRecords $records
    ): array {
        $map = $records->classMap($inscription);
        $semestres = $periode === 'annuel'
            ? ['semestre1', 'semestre2']
            : [$periode];
        $sections = [];

        foreach ($semestres as $semestre) {
            $classeId = (int) ($map[$semestre.'_classe_id'] ?? $inscription->classe_id);
            $snapshot = $this->snapshots->getSemesterSnapshot(
                $etudiantId,
                $classeId,
                (int) $inscription->annee_universitaire_id,
                $semestre
            );
            $subjects = array_values($snapshot['subjects'] ?? []);

            $sections[] = [
                'periode' => $semestre,
                'classe_id' => $classeId,
                'classe_nom' => ESBTPClasse::withTrashed()->whereKey($classeId)->value('name') ?? 'Classe non renseignée',
                'subjects' => $subjects,
                'notes_count' => (int) ($snapshot['notes_count'] ?? 0),
                'manual_resultats_count' => (int) ($snapshot['manual_resultats_count'] ?? 0),
                'moyenne' => $snapshot['effective_total'] ?? null,
                'coefficients_missing' => (bool) ($snapshot['coefficients_missing'] ?? false),
                'configuration_ready' => (bool) ($snapshot['configuration']['ready'] ?? false),
            ];
        }

        return $sections;
    }

    /**
     * Un formulaire vide ne doit jamais se faire passer pour un relevé.
     * Les notes absentes restent affichées comme absences, sans inventer de résultat.
     *
     * @param array<int,array<string,mixed>> $sections
     */
    public static function hasResults(array $sections): bool
    {
        foreach ($sections as $section) {
            if (($section['subjects'] ?? []) !== []
                && (($section['notes_count'] ?? 0) > 0
                    || ($section['manual_resultats_count'] ?? 0) > 0)) {
                return true;
            }
        }

        return false;
    }
}
