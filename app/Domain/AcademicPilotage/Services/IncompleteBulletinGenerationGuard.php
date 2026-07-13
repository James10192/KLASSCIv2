<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\AcademicPilotage\DTO\BulletinPreparationResult;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use Illuminate\Support\Facades\Log;

final class IncompleteBulletinGenerationGuard
{
    public function assertCanGenerate(
        BulletinPreparationResult $preparation,
        bool $hasOverridePermission,
        ?string $overrideReason = null,
        ?int $actorId = null,
    ): void {
        if ($preparation->ready) {
            return;
        }

        if (! $hasOverridePermission) {
            throw AcademicPilotageException::incompleteBulletin($preparation->toArray());
        }

        if (trim((string) $overrideReason) === '') {
            throw AcademicPilotageException::incompleteBulletinReasonRequired($preparation->toArray());
        }

        Log::warning('Incomplete bulletin generation override accepted.', [
            'actor_id' => $actorId,
            'reason' => $overrideReason,
            'preparation' => $preparation->toArray(),
        ]);
    }
}
