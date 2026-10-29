<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\DTO;

final readonly class BulkBulletinGenerationResult
{
    /**
     * @param list<array<string, mixed>> $skipped
     * @param list<array<string, mixed>> $blockingErrors
     * @param list<array<string, mixed>> $errors
     */
    public function __construct(
        public int $created = 0,
        public int $regenerated = 0,
        public array $skipped = [],
        public array $blockingErrors = [],
        public array $errors = [],
        public array $preflight = [],
    ) {}

    public function hasWrites(): bool
    {
        return ($this->created + $this->regenerated) > 0;
    }

    public function hasFailures(): bool
    {
        return $this->blockingErrors !== [] || $this->errors !== [];
    }

    public function statusCode(): int
    {
        if ($this->hasWrites() && $this->hasFailures()) {
            return 207;
        }

        if (! $this->hasWrites() && $this->hasFailures()) {
            return 422;
        }

        return 200;
    }

    public function message(): string
    {
        if ($this->hasWrites() && $this->hasFailures()) {
            return sprintf(
                '%d bulletin(s) créé(s), %d recalculé(s), avec %d blocage(s).',
                $this->created,
                $this->regenerated,
                count($this->blockingErrors) + count($this->errors)
            );
        }

        if ($this->hasWrites()) {
            return sprintf(
                '%d bulletin(s) créé(s), %d bulletin(s) recalculé(s).',
                $this->created,
                $this->regenerated
            );
        }

        if ($this->blockingErrors !== []) {
            return 'Aucun bulletin généré : des prérequis académiques bloquent la génération.';
        }

        if ($this->errors !== []) {
            return 'Aucun bulletin généré : une erreur est survenue pendant la génération.';
        }

        if ($this->skipped !== []) {
            return 'Aucun nouveau bulletin généré : les bulletins existent déjà ou sont hors périmètre.';
        }

        return 'Aucun étudiant actif trouvé pour cette classe et cette année.';
    }

    public function toArray(): array
    {
        return [
            'ok' => ! $this->hasFailures(),
            'created' => $this->created,
            'regenerated' => $this->regenerated,
            'skipped' => $this->skipped,
            'blocking_errors' => $this->blockingErrors,
            'errors' => $this->errors,
            'preflight' => $this->preflight,
            'message' => $this->message(),
        ];
    }
}
