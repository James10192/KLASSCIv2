<?php

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Models\ESBTPNote;

final class GradeSheetNoteMutationGuard
{
    /**
     * La colonne `evaluation_id` existe-t-elle ? Lu une fois par base : toute
     * note passe ici. La clé inclut la base, pour qu'un processus qui change de
     * connexion (tests, commandes multi-instance) ne garde pas une réponse fausse.
     *
     * @var array<string, bool>
     */
    private static array $examensRelies = [];

    private static function examensRelies(): bool
    {
        $connexion = \Illuminate\Support\Facades\DB::connection();
        $cle = $connexion->getName().'|'.$connexion->getDatabaseName();

        return self::$examensRelies[$cle] ??= \Illuminate\Support\Facades\Schema::hasColumn('esbtp_examens_planifies', 'evaluation_id');
    }

    public function assertMutable(ESBTPNote $note): void
    {
        if ($note->evaluation_id === null) {
            return;
        }

        $this->assertEvaluationMutable((int) $note->evaluation_id);
    }

    public function assertEvaluationMutable(int $evaluationId, bool $lockForUpdate = false): void
    {
        $query = GradeSheet::query()
            ->where('evaluation_id', $evaluationId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $sheet = $query->first();

        if ($sheet?->status === GradeSheetStatus::VALIDATED) {
            throw AcademicPilotageException::validatedNoteLocked((int) $sheet->id);
        }

        // Le verrou posé après un examen planifié (`notes_locked`) n'était lu
        // nulle part : l'anti-falsification annoncé ne protégeait aucune note.
        $examenVerrouille = self::examensRelies()
            ? \App\Models\ESBTPExamenPlanifie::query()
                ->where('evaluation_id', $evaluationId)
                ->where('notes_locked', true)
                ->value('id')
            : null;

        if ($examenVerrouille !== null) {
            throw AcademicPilotageException::examNotesLocked((int) $examenVerrouille);
        }
    }
}
