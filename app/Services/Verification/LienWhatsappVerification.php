<?php

namespace App\Services\Verification;

use Illuminate\Support\Facades\Cache;

/**
 * Le lien wa.me d'une verification inversee, le temps que la famille s'en serve.
 *
 * MailPulse ne le rend qu'une fois, a la creation. Il est garde en cache le
 * temps de vie du code, sous l'empreinte de la demande, pour que le site
 * puisse le reafficher apres un rechargement. Il contient le code en clair :
 * il n'est jamais journalise et disparait avec le code.
 */
class LienWhatsappVerification
{
    public static function poser(string $demandeId, string $lien): void
    {
        $minutes = (int) config('verification_contact.code_whatsapp_expire_minutes', 10);
        Cache::put(self::cle($demandeId), $lien, now()->addMinutes($minutes));
    }

    public static function lire(string $demandeId): ?string
    {
        $lien = Cache::get(self::cle($demandeId));

        return is_string($lien) && $lien !== '' ? $lien : null;
    }

    public static function oublier(string $demandeId): void
    {
        Cache::forget(self::cle($demandeId));
    }

    private static function cle(string $demandeId): string
    {
        return 'verif-lien-wa:'.hash('sha256', $demandeId);
    }
}
