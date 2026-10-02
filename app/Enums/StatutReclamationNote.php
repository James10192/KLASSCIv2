<?php

namespace App\Enums;

/**
 * Cycle d'une réclamation de note.
 *
 *   SOUMISE   l'élève a déposé ; l'enseignant n'a pas encore répondu
 *   AVIS_DONNE l'enseignant a proposé « confirmer » ou « corriger »
 *   ACCEPTEE  le personnel habilité a appliqué la correction (note réécrite, moyennes recalculées)
 *   REJETEE   le personnel habilité a maintenu la note
 *
 * Le personnel habilité peut trancher dès SOUMISE : un enseignant absent ne doit pas
 * bloquer un élève. L'avis de l'enseignant reste alors simplement vide.
 */
enum StatutReclamationNote: string
{
    case SOUMISE = 'soumise';
    case AVIS_DONNE = 'avis_donne';
    case ACCEPTEE = 'acceptee';
    case REJETEE = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::SOUMISE => 'Envoyée',
            self::AVIS_DONNE => 'Avis de l\'enseignant reçu',
            self::ACCEPTEE => 'Note corrigée',
            self::REJETEE => 'Note maintenue',
        };
    }

    public function estOuverte(): bool
    {
        return in_array($this, [self::SOUMISE, self::AVIS_DONNE], true);
    }

    /** Classe du badge : sémantique, donc couleur permise (premium-redesign). */
    public function ton(): string
    {
        return match ($this) {
            self::SOUMISE => 'attente',
            self::AVIS_DONNE => 'info',
            self::ACCEPTEE => 'ok',
            self::REJETEE => 'neutre',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    /** @return list<string> */
    public static function ouvertes(): array
    {
        return [self::SOUMISE->value, self::AVIS_DONNE->value];
    }
}
