<?php

namespace App\Domain\AcademicPilotage\Exceptions;

use App\Domain\AcademicPilotage\Enums\GradeSheetAction;
use App\Domain\AcademicPilotage\Enums\GradeSheetEntryMode;
use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

final class AcademicPilotageException extends RuntimeException
{
    public const INVALID_TRANSITION = 'academic_pilotage.invalid_transition';

    public const REASON_REQUIRED = 'academic_pilotage.reason_required';

    public const STALE_GRADE_SHEET = 'academic_pilotage.stale_grade_sheet';

    public const INCOMPLETE_ENTRIES = 'academic_pilotage.incomplete_entries';

    public const INVALID_EVALUATION_SCOPE = 'academic_pilotage.invalid_evaluation_scope';

    public const MUTATION_NOT_ALLOWED = 'academic_pilotage.mutation_not_allowed';

    public const DUPLICATE_NOTES = 'academic_pilotage.duplicate_notes';

    public const INVALID_ENTRY_EVIDENCE = 'academic_pilotage.invalid_entry_evidence';

    public const VALIDATED_NOTE_LOCKED = 'academic_pilotage.validated_note_locked';

    public const INVALID_DOCUMENT_CONTENT = 'academic_pilotage.invalid_document_content';

    public const INCOMPLETE_BULLETIN = 'academic_pilotage.incomplete_bulletin';

    public const INCOMPLETE_BULLETIN_REASON_REQUIRED = 'academic_pilotage.incomplete_bulletin_reason_required';

    public const CLOSED_ACADEMIC_YEAR = 'academic_pilotage.closed_academic_year';

    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
        public readonly int $statusCode = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function invalidTransition(
        GradeSheetStatus $from,
        GradeSheetAction $action,
        GradeSheetEntryMode $mode,
    ): self {
        return new self(
            self::INVALID_TRANSITION,
            sprintf(
                'L’action « %s » est impossible depuis le statut « %s » en mode « %s ».',
                $action->label(),
                $from->label(),
                $mode->label(),
            ),
            [
                'from' => $from->value,
                'action' => $action->value,
                'entry_mode' => $mode->value,
            ],
        );
    }

    public static function reasonRequired(GradeSheetAction $action): self
    {
        return new self(
            self::REASON_REQUIRED,
            sprintf('Une raison est obligatoire pour l’action « %s ».', $action->label()),
            ['action' => $action->value],
        );
    }

    public static function staleGradeSheet(int $expectedVersion, int $actualVersion): self
    {
        return new self(
            self::STALE_GRADE_SHEET,
            'Cette fiche a été modifiée par un autre utilisateur. Rechargez ses données avant de continuer.',
            [
                'expected_lock_version' => $expectedVersion,
                'actual_lock_version' => $actualVersion,
            ],
            409,
        );
    }

    public static function incompleteEntries(int $expectedCount, int $unresolvedCount): self
    {
        return new self(
            self::INCOMPLETE_ENTRIES,
            'Toutes les entrées attendues doivent être renseignées avant de terminer la saisie.',
            [
                'expected_entries' => $expectedCount,
                'unresolved_entries' => $unresolvedCount,
            ],
        );
    }

    public static function invalidEvaluationScope(array $missingFields): self
    {
        return new self(
            self::INVALID_EVALUATION_SCOPE,
            'L’évaluation doit définir une classe, une matière, une année universitaire et une période.',
            ['missing_fields' => $missingFields],
        );
    }

    public static function mutationNotAllowed(string $operation, GradeSheetStatus $status): self
    {
        return new self(
            self::MUTATION_NOT_ALLOWED,
            sprintf(
                'L’opération « %s » est interdite pour une fiche au statut « %s ».',
                $operation,
                $status->label(),
            ),
            ['operation' => $operation, 'status' => $status->value],
        );
    }

    public static function duplicateNotes(array $studentIds): self
    {
        return new self(
            self::DUPLICATE_NOTES,
            'Plusieurs notes actives existent pour un même étudiant et une même évaluation.',
            ['student_ids' => array_values($studentIds)],
        );
    }

    public static function invalidEntryEvidence(int $missingNotes, int $staleNotes): self
    {
        return new self(
            self::INVALID_ENTRY_EVIDENCE,
            'Les preuves de saisie ont changé. Reprenez le contrôle de la fiche avant de continuer.',
            [
                'missing_notes' => $missingNotes,
                'stale_notes' => $staleNotes,
            ],
        );
    }

    public static function validatedNoteLocked(int $gradeSheetId): self
    {
        return new self(
            self::VALIDATED_NOTE_LOCKED,
            'Cette note appartient à une fiche validée. Rouvrez la fiche avant de modifier la note.',
            ['grade_sheet_id' => $gradeSheetId],
            409,
        );
    }

    public static function invalidDocumentContent(): self
    {
        return new self(
            self::INVALID_DOCUMENT_CONTENT,
            'Le contenu du document ne correspond pas à un format autorisé.',
            [],
        );
    }

    public static function incompleteBulletin(array $preparation): self
    {
        return new self(
            self::INCOMPLETE_BULLETIN,
            'La génération du bulletin est bloquée par des données académiques incomplètes.',
            ['preparation' => $preparation],
        );
    }

    public static function incompleteBulletinReasonRequired(array $preparation): self
    {
        return new self(
            self::INCOMPLETE_BULLETIN_REASON_REQUIRED,
            'Un motif est obligatoire pour générer un bulletin incomplet.',
            ['preparation' => $preparation],
        );
    }

    /**
     * Un bulletin déjà calculé ne se recalcule pas sur une année refermée.
     *
     * La maquette appartient au parcours et n'est datée nulle part : la modifier
     * en 2028 change ce qu'une régénération produit pour 2026-2027. Le PV, lui,
     * est scellé — le relevé, non : il relit les bulletins. Deux pièces
     * officielles finiraient par se contredire sur le même étudiant, sans
     * qu'aucune erreur ne soit levée.
     *
     * On refuse donc le recalcul, jamais la première génération : celle-ci ne
     * contredit rien puisqu'il n'existe encore rien à contredire.
     */
    public static function closedAcademicYear(string $anneeLabel, int $anneeId, string $permissionDeContournement): self
    {
        return new self(
            self::CLOSED_ACADEMIC_YEAR,
            sprintf(
                'L’année %s est clôturée : son bulletin ne peut plus être recalculé. '
                .'Une maquette modifiée depuis produirait des résultats différents de ceux déjà certifiés. '
                .'Rouvrez l’année, ou faites-vous accorder la permission « %s ».',
                $anneeLabel,
                $permissionDeContournement,
            ),
            [
                'annee_universitaire_id' => $anneeId,
                'permission' => $permissionDeContournement,
            ],
            409,
        );
    }

    public function render(?Request $request = null): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'details' => $this->details,
        ], $this->statusCode);
    }
}
