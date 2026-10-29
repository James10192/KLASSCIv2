<?php

namespace Tests\Feature\Emails;

use App\Services\Emails\ResolveurDns;

/**
 * Resolveur de test : aucun appel reseau. `recoitDuCourrier` sert la
 * verification MX habituelle (propositions) ; `domaineInexistant` la
 * verification stricte faite au moment d'ecrire.
 */
class DnsSimule implements ResolveurDns
{
    /** @var array<string, bool> domaine => recoit du courrier (defaut : oui) */
    public array $mx = [];

    /** @var array<string, ?bool> domaine => reponse stricte (defaut : inconnue) */
    public array $inexistants = [];

    public int $appelsStricts = 0;

    /** Execute pendant la verification stricte : simule une ecriture concurrente. */
    public ?\Closure $pendantVerification = null;

    public function recoitDuCourrier(string $domaine): bool
    {
        return $this->mx[$domaine] ?? true;
    }

    public function domaineInexistant(string $domaine): ?bool
    {
        $this->appelsStricts++;
        if ($this->pendantVerification !== null) {
            ($this->pendantVerification)();
        }

        return $this->inexistants[$domaine] ?? null;
    }
}
