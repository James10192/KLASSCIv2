<?php

namespace App\Enums;

/**
 * Forme sous laquelle l'ecole attend une piece du dossier d'inscription.
 *
 * Distinction qui a un cout reel au guichet : une ecole qui reclame l'ORIGINAL
 * du releve de notes le conserve, alors qu'une COPIE est rendue. Le secretariat
 * doit le savoir avant d'encaisser la piece, pas apres.
 */
enum FormeDocumentRequis: string
{
    case ORIGINAL    = 'original';
    case COPIE       = 'copie';
    case INDIFFERENT = 'indifferent';

    public function label(): string
    {
        return match ($this) {
            self::ORIGINAL    => 'Original',
            self::COPIE       => 'Copie',
            self::INDIFFERENT => 'Original ou copie',
        };
    }

    /** Valeurs brutes, pour Rule::in(). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Options ['valeur' => 'Libelle'] pour un select premium. */
    public static function selectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Tolerant a la donnee sale (import, saisie ancienne) : on retombe sur la
     * forme la moins contraignante plutot que de lever une exception au rendu
     * d'une liste.
     */
    public static function fromLibre(?string $raw): self
    {
        return self::tryFrom(strtolower(trim((string) $raw))) ?? self::COPIE;
    }
}
