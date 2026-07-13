<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

use App\Domain\AcademicPilotage\Enums\AcademicAlertSeverity;
use App\Domain\AcademicPilotage\Enums\AcademicAlertType;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class AcademicAlertCandidate
{
    public function __construct(
        public AcademicAlertType $type,
        public AcademicAlertSeverity $severity,
        public ?int $academicYearId,
        public ?string $semester,
        public ?int $classId,
        public ?int $studentId,
        public ?int $subjectId,
        public ?int $teacherId,
        public string $message,
        public ?string $recommendedAction,
        public array $metadata = [],
        public ?string $entityType = null,
        public ?int $entityId = null,
    ) {
        if (trim($message) === '') {
            throw new InvalidArgumentException('Une alerte académique doit avoir un message.');
        }

        foreach (['academicYearId', 'classId', 'studentId', 'subjectId', 'teacherId', 'entityId'] as $property) {
            $value = $this->{$property};
            if ($value !== null && $value <= 0) {
                throw new InvalidArgumentException("Le champ {$property} est invalide.");
            }
        }
    }

    public static function fromEntity(
        AcademicAlertType $type,
        AcademicAlertSeverity $severity,
        Model $entity,
        string $message,
        string $recommendedAction,
        array $metadata = [],
    ): self {
        return new self(
            type: $type,
            severity: $severity,
            academicYearId: self::intAttribute($entity, 'annee_universitaire_id'),
            semester: self::stringAttribute($entity, 'semester') ?? self::stringAttribute($entity, 'periode'),
            classId: self::intAttribute($entity, 'classe_id'),
            studentId: self::intAttribute($entity, 'etudiant_id'),
            subjectId: self::intAttribute($entity, 'matiere_id'),
            teacherId: self::intAttribute($entity, 'teacher_id'),
            message: $message,
            recommendedAction: $recommendedAction,
            metadata: $metadata,
            entityType: $entity::class,
            entityId: (int) $entity->getKey(),
        );
    }

    /** @return array<string, mixed> */
    public function scope(): array
    {
        return [
            'type' => $this->type->value,
            'year' => $this->academicYearId,
            'semester' => $this->semester,
            'class' => $this->classId,
            'student' => $this->studentId,
            'subject' => $this->subjectId,
            'teacher' => $this->teacherId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ];
    }

    private static function intAttribute(Model $entity, string $attribute): ?int
    {
        $value = $entity->getAttribute($attribute);

        return $value !== null && (int) $value > 0 ? (int) $value : null;
    }

    private static function stringAttribute(Model $entity, string $attribute): ?string
    {
        $value = $entity->getAttribute($attribute);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
