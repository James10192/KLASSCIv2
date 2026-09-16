<?php

namespace App\Domain\Scolarite;

final class AttestationReussite
{
    public const TYPE = 'attestation_reussite';

    public const FREQUENTATION = 'attestation_frequentation';

    public static function nEstPasUneFrequentation(string $type): bool
    {
        return $type === self::TYPE;
    }
}
