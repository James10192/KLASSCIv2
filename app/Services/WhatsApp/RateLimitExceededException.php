<?php

namespace App\Services\WhatsApp;

use RuntimeException;

/**
 * Exception levée quand le tenant atteint son tier Meta limit en 24h.
 *
 * Phase 4 Plan v4 hardening — caller doit catcher proprement :
 *  - Skip l'envoi sans crasher l'app
 *  - Log + alerter admin tenant
 *  - Re-essayer après window reset
 */
class RateLimitExceededException extends RuntimeException
{
}
