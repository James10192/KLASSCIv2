<?php

namespace App\Enums;

/**
 * Etat d'une piece du dossier pour une inscription donnee.
 *
 * NON_APPLICABLE existe parce que le catalogue est defini par filiere et par
 * niveau, donc a une maille plus large qu'un dossier : une piece peut etre
 * legitimement demandee a la filiere entiere et sans objet pour un etudiant
 * precis (un transfert n'a pas d'attestation de premiere inscription). Sans ce
 * troisieme etat, le secretariat n'aurait que le choix de mentir en cochant
 * "fournie" pour faire taire le compteur.
 */
enum StatutPieceDossier: string
{
    case MANQUANTE = 'manquante';
    case FOURNIE = 'fournie';
    case NON_APPLICABLE = 'non_applicable';

    public function label(): string
    {
        return match ($this) {
            self::MANQUANTE => 'Manquante',
            self::FOURNIE => 'Fournie',
            self::NON_APPLICABLE => 'Sans objet',
        };
    }

    /** Valeurs brutes, pour Rule::in() et les migrations. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Options pour un select, au format ['valeur' => 'Libelle']. */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * Une piece qui n'est ni fournie ni ecartee reste due.
     * C'est ce seul etat qui alimente le compteur de la fiche.
     */
    public function estDue(): bool
    {
        return $this === self::MANQUANTE;
    }

    /** Statut par defaut a la materialisation : rien n'a encore ete remis. */
    public static function defaut(): self
    {
        return self::MANQUANTE;
    }

    /**
     * Tolerant a l'inconnu : une valeur non reconnue est traitee comme due,
     * jamais silencieusement comme fournie. En cas de doute on reclame la piece,
     * on ne la declare pas recue.
     */
    public static function depuis(?string $brut): self
    {
        if ($brut === null || $brut === '') {
            return self::defaut();
        }

        return self::tryFrom(strtolower(trim($brut))) ?? self::defaut();
    }
}
