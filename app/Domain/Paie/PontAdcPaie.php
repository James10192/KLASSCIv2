<?php

namespace App\Domain\Paie;

final class PontAdcPaie
{
    public const HOURS_CERTIFIED = 'hours.certified.v1';

    public const EMPLOYEE_SYNC = 'employee.sync.v1';

    public const ACK = 'ack.v1';

    public static function estSynchronise(string $ack, bool $timeout): bool
    {
        return ! $timeout && $ack === 'accepted';
    }

    public static function peutRelancer(string $cleIdempotence, bool $dejaAccepte): bool
    {
        return $cleIdempotence !== '' && ! $dejaAccepte;
    }

    public static function unExportCsvNEstPasUneIntegration(): bool
    {
        return true;
    }
}
