<?php

namespace App\Enums;

/**
 * À quoi une pièce du dossier appartient : à l'étudiant, ou à l'inscription.
 *
 * C'est la distinction qui décide de ce qu'une école redemande chaque rentrée.
 * Un extrait de naissance, des photos d'identité, un diplôme sont déposés une
 * fois et durent : ils appartiennent à l'ÉTUDIANT, et chaque inscription en
 * consomme une quantité — six photos déposées, deux par année, il en reste deux
 * la troisième. Un certificat médical de l'année, lui, appartient à
 * l'INSCRIPTION : rien ne se reporte, il est redonné.
 *
 * Le choix appartient à l'école, pièce par pièce. Une même pièce peut être un
 * stock ici et une formalité annuelle ailleurs, et aucune des deux écoles n'a
 * tort.
 */
enum AppartenancePieceDossier: string
{
    case ETUDIANT = 'etudiant';
    case INSCRIPTION = 'inscription';

    public function label(): string
    {
        return match ($this) {
            self::ETUDIANT => "À l'étudiant — déposée une fois, elle dure",
            self::INSCRIPTION => "À l'inscription — redonnée chaque année",
        };
    }

    /** Libellé court, pour une pastille de tableau. */
    public function labelCourt(): string
    {
        return match ($this) {
            self::ETUDIANT => 'Dure',
            self::INSCRIPTION => 'Annuelle',
        };
    }

    /** La pièce se reporte-t-elle d'une inscription à la suivante ? */
    public function seReporte(): bool
    {
        return $this === self::ETUDIANT;
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
