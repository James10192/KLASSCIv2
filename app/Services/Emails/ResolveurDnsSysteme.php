<?php

namespace App\Services\Emails;

use RuntimeException;

/**
 * Resolution par le resolveur du systeme. RFC 5321 : sans MX, un serveur de
 * messagerie se rabat sur l'enregistrement A — on fait de meme.
 *
 * `checkdnsrr` n'a pas de delai : un resolveur lent ferait attendre le
 * formulaire public autant qu'il lui plait. Sous Linux, la question part donc
 * en UDP vers le premier `nameserver` de /etc/resolv.conf, avec un delai total
 * (`emails_joignables.mx.delai_secondes`, 1 s) partage entre MX et A ; au-dela,
 * DnsTropLent. Sans resolv.conf (poste Windows), repli sur `checkdnsrr`.
 */
class ResolveurDnsSysteme implements ResolveurDns
{
    private const TYPE_A = 1;

    private const TYPE_MX = 15;

    private const NXDOMAIN = 3;

    public function recoitDuCourrier(string $domaine): bool
    {
        $nom = rtrim($domaine, '.');
        $serveur = $this->serveur();
        if ($serveur === null) {
            return @checkdnsrr($nom.'.', 'MX') || @checkdnsrr($nom.'.', 'A');
        }

        $limite = microtime(true) + (float) config('emails_joignables.mx.delai_secondes', 1.0);

        return $this->interroger($serveur, $nom, self::TYPE_MX, $limite)
            || $this->interroger($serveur, $nom, self::TYPE_A, $limite);
    }

    private function interroger(string $serveur, string $nom, int $type, float $limite): bool
    {
        $reste = $limite - microtime(true);
        if ($reste <= 0) {
            throw new DnsTropLent('Delai DNS epuise');
        }

        $id = random_int(0, 0xFFFF);
        $socket = @stream_socket_client('udp://'.(str_contains($serveur, ':') ? '['.$serveur.']' : $serveur).':53', $code, $erreur, $reste);
        if ($socket === false) {
            throw new RuntimeException('Resolveur injoignable : '.$erreur);
        }

        try {
            stream_set_timeout($socket, (int) $reste, (int) (fmod($reste, 1) * 1_000_000));
            fwrite($socket, pack('nnnnnn', $id, 0x0100, 1, 0, 0, 0).$this->question($nom).pack('nn', $type, 1));
            $reponse = (string) fread($socket, 4096);
            if (stream_get_meta_data($socket)['timed_out']) {
                throw new DnsTropLent('Pas de reponse DNS dans le delai');
            }
        } finally {
            fclose($socket);
        }

        if (strlen($reponse) < 12) {
            throw new RuntimeException('Reponse DNS tronquee');
        }
        ['id' => $recu, 'drapeaux' => $drapeaux, 'reponses' => $reponses] = unpack('nid/ndrapeaux/nquestions/nreponses', $reponse);
        if ($recu !== $id) {
            throw new RuntimeException('Reponse DNS etrangere a la question');
        }

        return match ($drapeaux & 0x000F) {
            0 => $reponses > 0 || ($drapeaux & 0x0200) !== 0, // tronquee : il y avait des enregistrements
            self::NXDOMAIN => false,
            default => throw new RuntimeException('Resolveur en erreur (code '.($drapeaux & 0x000F).')'),
        };
    }

    private function question(string $nom): string
    {
        $question = '';
        foreach (explode('.', $nom) as $etiquette) {
            if ($etiquette === '' || strlen($etiquette) > 63 || preg_match('/[^A-Za-z0-9-]/', $etiquette)) {
                throw new RuntimeException('Nom de domaine non interrogeable');
            }
            $question .= chr(strlen($etiquette)).$etiquette;
        }

        return $question."\0";
    }

    private function serveur(): ?string
    {
        $conf = @file_get_contents('/etc/resolv.conf');
        if ($conf === false || ! preg_match('/^\s*nameserver\s+(\S+)/m', $conf, $m)) {
            return null;
        }

        return $m[1];
    }
}
