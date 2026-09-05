<?php

namespace App\Enums;

/**
 * Moment où une pièce du dossier devient exigible.
 *
 * Sans cette nuance, toute pièce qu'une école tolère en cours d'année
 * (certificat médical, photo de meilleure qualité) apparaîtrait comme manquante
 * dès le jour de l'inscription : le dossier de toute l'école serait rouge à la
 * rentrée, et un signal toujours rouge n'est plus un signal.
 *
 * À ne pas confondre avec la RÉSERVE portée par esbtp_inscriptions
 * (is_sous_reserve / condition_reserve) : la réserve vise un document qui
 * n'existe pas encore et sera délivré plus tard, souvent l'année suivante — un
 * relevé de baccalauréat non encore édité. Ici, le document existe déjà ; seule
 * sa remise est différée.
 */
enum EcheancePieceDossier: string
{
    case INSCRIPTION = 'inscription';
    case AVANT_FIN_ANNEE = 'avant_fin_annee';

    public function label(): string
    {
        return match ($this) {
            self::INSCRIPTION => "À fournir à l'inscription",
            self::AVANT_FIN_ANNEE => "À fournir avant la fin de l'année",
        };
    }

    /** Libellé court, pour une pastille de tableau. */
    public function labelCourt(): string
    {
        return match ($this) {
            self::INSCRIPTION => 'Inscription',
            self::AVANT_FIN_ANNEE => "Avant fin d'année",
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
     * Lecture tolérante d'une valeur venue d'ailleurs. Rend null quand la
     * valeur n'est pas reconnue : voir FormePieceDossier::tryFromLibre() pour
     * la raison.
     */
    public static function tryFromLibre(?string $brut): ?self
    {
        return self::tryFrom(strtolower(trim((string) $brut)));
    }
}
