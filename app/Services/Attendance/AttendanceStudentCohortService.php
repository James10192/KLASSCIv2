<?php

namespace App\Services\Attendance;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPSeanceCours;
use App\Services\Notes\NoteStudentCohortService;
use Illuminate\Support\Collection;
use LogicException;

class AttendanceStudentCohortService
{
    public function __construct(private readonly NoteStudentCohortService $noteCohortService)
    {
    }

    public function studentsForSession(ESBTPSeanceCours $seance): Collection
    {
        $seance->loadMissing(['emploiTemps.classe', 'emploiTemps.annee']);
        $emploiTemps = $seance->emploiTemps;

        if (! $emploiTemps?->classe || ! $emploiTemps->annee) {
            throw new LogicException('La séance doit être rattachée à un emploi du temps, une classe et une année universitaire.');
        }

        return $this->studentsForClass(
            $emploiTemps->classe,
            $emploiTemps->annee,
            [$emploiTemps->semestre]
        );
    }

    public function studentsForClassPeriod(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode
    ): Collection {
        return $this->studentsForClass($classe, $annee, $this->semestresForPeriode($periode));
    }

    /**
     * Compte la cohorte sans matérialiser les modèles (boucles de stats par classe).
     */
    public function countForClassPeriod(
        ESBTPClasse $classe,
        ESBTPAnneeUniversitaire $annee,
        string $periode
    ): int {
        return $this->noteCohortService->countStudentsForClass(
            $classe,
            $annee,
            $this->semestresForPeriode($periode)
        );
    }

    /** @return array<int, int> */
    private function semestresForPeriode(string $periode): array
    {
        return match ($periode) {
            'semestre1' => [1],
            'semestre2' => [2],
            'annuel' => [1, 2],
            default => throw new LogicException('Période de présence invalide.'),
        };
    }

    /** @param array<int, int|string> $semestres */
    private function studentsForClass(ESBTPClasse $classe, ESBTPAnneeUniversitaire $annee, array $semestres): Collection
    {
        return $this->noteCohortService
            ->studentsForClass($classe, $annee, $semestres)
            ->each(function ($etudiant): void {
                $etudiant->setAttribute(
                    'attendance_phase_label',
                    $etudiant->getAttribute('notes_phase_label')
                );
            });
    }
}
