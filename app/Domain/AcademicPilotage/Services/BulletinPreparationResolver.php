<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\Contracts\BulletinPreparationService;
use InvalidArgumentException;

final class BulletinPreparationResolver
{
    public function __construct(
        private readonly BtsBulletinPreparationService $bts,
        private readonly LmdBulletinPreparationService $lmd,
        private readonly AcademicSystemNormalizer $systems,
    ) {}

    public function forSystem(string $academicSystem): BulletinPreparationService
    {
        return match ($this->systems->normalize($academicSystem)) {
            AcademicSystemNormalizer::BTS => $this->bts,
            AcademicSystemNormalizer::LMD => $this->lmd,
            default => throw new InvalidArgumentException('Système académique non pris en charge pour les bulletins.'),
        };
    }
}
