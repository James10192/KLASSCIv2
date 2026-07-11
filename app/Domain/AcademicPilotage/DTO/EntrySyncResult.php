<?php

namespace App\Domain\AcademicPilotage\DTO;

final class EntrySyncResult
{
    public function __construct(
        public readonly int $created,
        public readonly int $updated,
        public readonly int $deactivated,
        public readonly int $unchanged,
        public readonly int $lockVersion,
    ) {}

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'deactivated' => $this->deactivated,
            'unchanged' => $this->unchanged,
            'lock_version' => $this->lockVersion,
        ];
    }
}
