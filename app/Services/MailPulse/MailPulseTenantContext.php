<?php

namespace App\Services\MailPulse;

use LogicException;

final class MailPulseTenantContext
{
    public static function code(): string
    {
        $code = strtolower(trim((string) config('app.tenant_code', '')));

        if ($code === '' || preg_match('/^[a-z0-9-]{1,80}$/D', $code) !== 1) {
            throw new LogicException('TENANT_CODE must be configured with lowercase letters, digits, or hyphens.');
        }

        return $code;
    }

    public static function scopedIdentifier(string $identifier): string
    {
        $tenant = self::compactCode();
        $prefix = "klassci-{$tenant}-";

        return str_starts_with($identifier, $prefix)
            ? $identifier
            : $prefix.ltrim($identifier, '-');
    }

    private static function compactCode(): string
    {
        $code = self::code();

        return strlen($code) <= 32
            ? $code
            : substr($code, 0, 19).'-'.substr(hash('sha256', $code), 0, 12);
    }
}
