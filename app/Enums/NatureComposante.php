<?php

namespace App\Enums;

/**
 * Ce qu'est, dans l'organisation d'une universite, le premier rang de la
 * structure LMD.
 *
 * KLASSCI range les formations en trois rangs : Domaine → Mention → Parcours.
 * Une universite comme UCAO-UUC parle, elle, de Faculte ou d'Ecole →
 * Departement → Specialite, et ses composantes ne sont pas toutes de meme
 * nature : une UFR n'est pas une ecole, et les documents le disent.
 *
 * Les trois rangs suffisent : il manquait un mot, pas une table. Cette nature
 * qualifie le premier rang ; un domaine sans nature reste un domaine au sens
 * du referentiel LMD. Les tables `ufrs` / `departments` d'origine, dormantes,
 * ne sont volontairement pas reprises.
 */
enum NatureComposante: string
{
    case UFR = 'ufr';
    case FACULTE = 'faculte';
    case ECOLE = 'ecole';
    case INSTITUT = 'institut';

    public function label(): string
    {
        return match ($this) {
            self::UFR => 'UFR',
            self::FACULTE => 'Faculté',
            self::ECOLE => 'École',
            self::INSTITUT => 'Institut',
        };
    }

    /** Nom long, pour une ligne d'en-tete de document. */
    public function intitule(): string
    {
        return match ($this) {
            self::UFR => 'Unité de Formation et de Recherche',
            self::FACULTE => 'Faculté',
            self::ECOLE => 'École',
            self::INSTITUT => 'Institut',
        };
    }

    /** Valeurs brutes, pour Rule::in(). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Options ['valeur' => 'Libellé']. */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
