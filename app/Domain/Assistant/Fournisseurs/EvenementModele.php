<?php

namespace App\Domain\Assistant\Fournisseurs;

/**
 * Événement normalisé émis par un fournisseur de modèle, quel qu'il soit.
 *
 * Chaque adaptateur (Anthropic, OpenAI-compatible, Gemini) traduit son propre
 * flux vers ces six types ; la boucle d'agent ne connaît que ceux-ci.
 *
 *   texte        ['delta' => string]
 *   outil_debut  ['id' => string, 'nom' => string]      le modèle commence un appel
 *   outil        ['id', 'nom', 'arguments' => array]    appel complet, à exécuter
 *   usage        ['entree' => int, 'sortie' => int]     jetons consommés (cumul du tour)
 *   fin          ['raison' => 'fin'|'outils'|'longueur']
 *   erreur       ['code' => string]                     jamais de texte d'exception
 */
final class EvenementModele
{
    public const TEXTE = 'texte';
    public const OUTIL_DEBUT = 'outil_debut';
    public const OUTIL = 'outil';
    public const USAGE = 'usage';
    public const FIN = 'fin';
    public const ERREUR = 'erreur';

    public function __construct(
        public readonly string $type,
        public readonly array $donnees = [],
    ) {
    }

    public static function texte(string $delta): self
    {
        return new self(self::TEXTE, ['delta' => $delta]);
    }

    public static function outilDebut(string $id, string $nom): self
    {
        return new self(self::OUTIL_DEBUT, ['id' => $id, 'nom' => $nom]);
    }

    public static function outil(string $id, string $nom, array $arguments): self
    {
        return new self(self::OUTIL, ['id' => $id, 'nom' => $nom, 'arguments' => $arguments]);
    }

    public static function usage(int $entree, int $sortie): self
    {
        return new self(self::USAGE, ['entree' => $entree, 'sortie' => $sortie]);
    }

    public static function fin(string $raison): self
    {
        return new self(self::FIN, ['raison' => $raison]);
    }

    public static function erreur(string $code): self
    {
        return new self(self::ERREUR, ['code' => $code]);
    }
}
