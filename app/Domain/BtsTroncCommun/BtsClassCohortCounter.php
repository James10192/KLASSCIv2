<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;

/**
 * Effectif BTS aligne sur l'historique de phases, pas seulement classe_id courant.
 *
 * S1 d'un etudiant oriente : compte dans la classe TC qui portait le semestre.
 * S2 apres changement de specialite : la phase active du semestre l'emporte.
 */
final class BtsClassCohortCounter
{
    public function __construct(private BtsPhaseResolver $phaseResolver)
    {
    }

    public function count(int $classeId, int $anneeUniversitaireId, string $periode): int
    {
        return count($this->etudiantIds($classeId, $anneeUniversitaireId, $periode));
    }

    /**
     * @return list<int>
     */
    public function etudiantIds(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $semester = $this->semesterNumber($periode);

        $inscriptions = ESBTPInscription::query()
            ->with([
                'filiere',
                'classe.filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get();

        $ids = [];
        foreach ($inscriptions as $inscription) {
            if ($this->resolveClasseId($inscription, $semester) === $classeId) {
                $ids[(int) $inscription->etudiant_id] = true;
            }
        }

        $sorted = array_keys($ids);
        sort($sorted);

        return $sorted;
    }

    private function resolveClasseId(ESBTPInscription $inscription, int $semester): ?int
    {
        $phase = $this->phaseResolver->resolveSemesterPhase($inscription, $semester);
        if (is_array($phase) && ! empty($phase['classe_id'])) {
            return (int) $phase['classe_id'];
        }

        return $inscription->classe_id ? (int) $inscription->classe_id : null;
    }

    private function semesterNumber(string $periode): int
    {
        return match ($periode) {
            '2', 'semestre2', 'annuel' => 2,
            default => 1,
        };
    }
}
