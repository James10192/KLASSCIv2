<?php

namespace App\Domain\Assistant\Fournisseurs;

/**
 * Requête neutre envoyée à un fournisseur.
 *
 * Messages, format commun à tous les adaptateurs :
 *   ['role' => 'user',      'texte' => string]
 *   ['role' => 'assistant', 'texte' => string, 'appels' => [['id', 'nom', 'arguments' => array], …]]
 *   ['role' => 'outil',     'id' => string, 'nom' => string, 'resultat' => string (JSON)]
 *
 * Outils : ['nom', 'description', 'parametres' => schéma JSON], déclarés une
 * seule fois ; chaque adaptateur les traduit dans le format de son API.
 */
final class RequeteModele
{
    public function __construct(
        public readonly string $systeme,
        public array $messages,
        public readonly array $outils = [],
        public readonly int $maxTokens = 2048,
        public readonly float $temperature = 0.2,
    ) {
    }

    public function avecMessages(array $messages): self
    {
        $copie = clone $this;
        $copie->messages = $messages;

        return $copie;
    }
}
