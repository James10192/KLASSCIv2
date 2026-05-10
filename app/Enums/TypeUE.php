<?php

namespace App\Enums;

/**
 * Catégories d'UE selon la directive UEMOA n°03/2007/CM/UEMOA et CAMES.
 *
 * Source de vérité unique pour `esbtp_unites_enseignement.type_ue` (VARCHAR).
 * La colonne reste un string libre côté DB — l'enum sert au cast Eloquent
 * et à la validation FormRequest.
 */
enum TypeUE: string
{
    case Fondamentale = 'fondamentale';
    case Methodologique = 'methodologique';
    case Decouverte = 'decouverte';
    case Transversale = 'transversale';
    case CultureGenerale = 'culture_generale';
    case Specialite = 'specialite';
    case Libre = 'libre';

    public function label(): string
    {
        return match ($this) {
            self::Fondamentale => 'UE Fondamentale',
            self::Methodologique => 'UE de Méthodologie',
            self::Decouverte => 'UE de Découverte',
            self::Transversale => 'UE Transversale',
            self::CultureGenerale => 'UE de Culture Générale',
            self::Specialite => 'UE de Spécialité',
            self::Libre => 'UE Libre',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Fondamentale => 'Disciplines majeures obligatoires du parcours',
            self::Methodologique => 'Autonomie, méthodologie du travail universitaire',
            self::Decouverte => 'Approfondissement, orientation, options',
            self::Transversale => 'Compétences transverses (langues, numérique)',
            self::CultureGenerale => 'Ouverture culturelle, citoyenneté',
            self::Specialite => 'UE de spécialisation du parcours',
            self::Libre => 'UE complémentaires (sport, engagement, culture)',
        };
    }

    /**
     * Liste des valeurs string pour validation Rule::in(...).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
