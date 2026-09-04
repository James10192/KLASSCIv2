<?php

namespace App\Enums;

/**
 * Moment ou une piece devient exigible.
 *
 * Sans cette nuance, toute piece qu'une ecole tolere en cours d'annee
 * (certificat medical, photo de qualite) apparaitrait comme MANQUANTE des le
 * jour de l'inscription : le compteur du dossier serait rouge pour tout le
 * monde a la rentree, et un signal toujours rouge n'est plus un signal.
 *
 * A ne pas confondre avec la RESERVE portee par esbtp_inscriptions
 * (is_sous_reserve / condition_reserve) : la reserve vise un document qui
 * N'EXISTE PAS ENCORE et sera delivre plus tard, souvent l'annee suivante
 * (un releve de baccalaureat non encore edite). Ici, le document existe deja ;
 * seule sa remise est differee. Les deux mecanismes restent separes.
 */
enum EcheanceDocumentRequis: string
{
    case INSCRIPTION     = 'inscription';
    case AVANT_FIN_ANNEE = 'avant_fin_annee';

    public function label(): string
    {
        return match ($this) {
            self::INSCRIPTION     => "A fournir a l'inscription",
            self::AVANT_FIN_ANNEE => "A fournir avant la fin de l'annee",
        };
    }

    /** Libelle court, pour une pastille de tableau. */
    public function labelCourt(): string
    {
        return match ($this) {
            self::INSCRIPTION     => 'Inscription',
            self::AVANT_FIN_ANNEE => "Avant fin d'annee",
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

    public static function fromLibre(?string $raw): self
    {
        return self::tryFrom(strtolower(trim((string) $raw))) ?? self::INSCRIPTION;
    }
}
