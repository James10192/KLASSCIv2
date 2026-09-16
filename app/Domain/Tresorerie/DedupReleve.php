<?php

namespace App\Domain\Tresorerie;

final class DedupReleve
{
    public static function estDoublon(?string $identifiantExterne, bool $dejaImporte): bool
    {
        return $identifiantExterne !== null && $identifiantExterne !== '' && $dejaImporte;
    }
}
