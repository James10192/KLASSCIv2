<?php

namespace App\Support;

/**
 * Token claim names — MUST stay in sync with adminKlassci/app/Support/SsoClaim.php
 * (two separate apps, two autoloaders — no shared package).
 */
final class SsoClaim
{
    public const TENANT_CODE = 'tenant_code';
    public const USER_EMAIL = 'user_email';
    public const REDIRECT_TO = 'redirect_to';
    public const ISSUED_BY = 'issued_by';
    public const GROUP_MEMBER_ID = 'group_member_id';
    public const EXP = 'exp';
    public const NONCE = 'nonce';
}
