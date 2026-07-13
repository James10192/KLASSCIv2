<?php

namespace App\Domain\AcademicPilotage\DTO;

use Illuminate\Support\Collection;

final readonly class AcademicActorScopeResolution
{
    public function __construct(
        public bool $global,
        public Collection $classIds,
        public array $sources,
    ) {}

    public static function global(): self
    {
        return new self(true, collect(), ['global']);
    }
}
