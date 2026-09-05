<?php

namespace App\Enums;

/**
 * Forme sous laquelle l'école attend une pièce du dossier d'inscription.
 *
 * La distinction a un coût réel au guichet : une école qui réclame l'ORIGINAL
 * du relevé de notes le conserve, alors qu'une COPIE est rendue à l'étudiant.
 * L'agent doit le savoir avant d'accepter la pièce, pas après.
 */
enum FormePieceDossier: string
{
    case ORIGINAL = 'original';
    case COPIE = 'copie';
    case INDIFFERENT = 'indifferent';

    public function label(): string
    {
        return match ($this) {
            self::ORIGINAL => 'Original',
            self::COPIE => 'Copie',
            self::INDIFFERENT => 'Original ou copie',
        };
    }

    /** Valeurs brutes, pour Rule::in(). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Options ['valeur' => 'Libellé'] pour un sélecteur premium. */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Lecture tolérante d'une valeur venue d'ailleurs : réglage d'école saisi
     * à la main, import, donnée ancienne.
     *
     * Rend null plutôt qu'une valeur de repli choisie ici : c'est à l'appelant
     * de dire ce qu'il fait d'une valeur qu'il ne reconnaît pas, parce que lui
     * seul sait s'il lit un réglage d'établissement ou une donnée corrompue.
     */
    public static function tryFromLibre(?string $brut): ?self
    {
        return self::tryFrom(strtolower(trim((string) $brut)));
    }
}
