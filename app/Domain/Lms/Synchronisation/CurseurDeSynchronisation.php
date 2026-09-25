<?php

namespace App\Domain\Lms\Synchronisation;

use InvalidArgumentException;

/**
 * Le curseur rendu au LMS : opaque pour lui, il porte la position atteinte
 * dans CHAQUE flux (date de modification + identifiant, pour ne perdre aucune
 * ligne quand plusieurs changent a la meme seconde), le dernier identifiant
 * des suppressions definitives, et l'annee synchronisee.
 */
final class CurseurDeSynchronisation
{
    private const VERSION = 1;

    /** @param array<string, array{0: string, 1: int}> $positions */
    public function __construct(
        public readonly ?int $anneeId,
        public array $positions = [],
        public int $derniereSuppression = 0,
    ) {
    }

    public function encoder(): string
    {
        $json = json_encode([
            'v' => self::VERSION,
            'a' => $this->anneeId,
            'p' => $this->positions,
            's' => $this->derniereSuppression,
        ]);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public static function decoder(string $jeton): self
    {
        $json = base64_decode(strtr($jeton, '-_', '+/'), true);
        $d = $json === false ? null : json_decode($json, true);

        if (! is_array($d) || ($d['v'] ?? null) !== self::VERSION || ! is_array($d['p'] ?? null) || ! is_int($d['s'] ?? null)) {
            throw new InvalidArgumentException('Curseur illisible : recommencez une synchronisation complète, sans « since ».');
        }

        $positions = [];
        foreach ($d['p'] as $type => $pos) {
            if (! in_array($type, FluxDeSynchronisation::TYPES, true)
                || ! is_array($pos) || ! is_string($pos[0] ?? null) || ! is_int($pos[1] ?? null)) {
                throw new InvalidArgumentException('Curseur illisible : recommencez une synchronisation complète, sans « since ».');
            }
            $positions[$type] = [$pos[0], $pos[1]];
        }

        return new self(is_int($d['a'] ?? null) ? $d['a'] : null, $positions, $d['s']);
    }
}
