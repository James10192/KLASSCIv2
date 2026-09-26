<?php

namespace App\Domain\Audit;

use Carbon\CarbonInterface;

/**
 * Une ligne du journal, dite comme une phrase :
 * « Aminata BAMBA (caissière) a annulé le paiement REC-2026-0412 ».
 */
final class LigneDuJournal
{
    /**
     * @param  list<string>  $motifs  pourquoi la regarder (vide pour une action ordinaire)
     */
    public function __construct(
        public readonly int $id,
        public readonly string $acteur,
        public readonly ?string $role,
        public readonly bool $automatique,
        public readonly string $verbe,
        public readonly ObjetNomme $objet,
        public readonly ?string $changement,
        public readonly CarbonInterface $quand,
        public readonly array $motifs,
        public readonly bool $peutOuvrir = true,
        /** La trace brute, pour l'export : elle a valeur de preuve. */
        public readonly ?string $evenement = null,
        public readonly ?string $ip = null,
        public readonly ?string $agent = null,
        public readonly ?string $url = null,
    ) {
    }

    /** La phrase en texte simple : titre de la page de detail, export. */
    public function phrase(): string
    {
        return trim($this->acteur.' '.$this->verbe.' '.$this->objet->enClair());
    }

    public function initiales(): string
    {
        // Les mots seulement : « Moustapha (USAT) » donne MU, pas M(.
        preg_match_all('/\p{L}+/u', $this->acteur, $mots);

        return mb_strtoupper(implode('', array_map(fn ($m) => mb_substr($m, 0, 1, 'UTF-8'), array_slice($mots[0], 0, 2))), 'UTF-8');
    }

    /** « Aujourd'hui à 13:06 », « Hier à 18:38 », « le 22/09/2026 à 09:14 ». */
    public function quandEnClair(): string
    {
        $heure = $this->quand->format('H:i');

        return match (true) {
            $this->quand->isToday() => "Aujourd'hui à ".$heure,
            $this->quand->isYesterday() => 'Hier à '.$heure,
            default => 'le '.$this->quand->format('d/m/Y').' à '.$heure,
        };
    }
}
