<?php

namespace App\Services\Emails;

/**
 * Resolution par le resolveur du systeme. RFC 5321 : sans MX, un serveur de
 * messagerie se rabat sur l'enregistrement A — on fait de meme.
 */
class ResolveurDnsSysteme implements ResolveurDns
{
    public function recoitDuCourrier(string $domaine): bool
    {
        $nom = rtrim($domaine, '.').'.';

        return @checkdnsrr($nom, 'MX') || @checkdnsrr($nom, 'A');
    }
}
