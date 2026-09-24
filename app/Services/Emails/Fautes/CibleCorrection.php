<?php

namespace App\Services\Emails\Fautes;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ESBTPRdvReservation;
use App\Models\User;

/**
 * Une adresse precise : `<table>:<colonne>:<id>`. Seules les colonnes
 * inventoriees ET presentes dans le schema de l'instance
 * (InventaireAdresses::colonnes()) sont des cibles ; `users` ne
 * l'est que sur demande expresse, comme pour le nettoyage des adresses
 * fabriquees (un compte du personnel peut se connecter par cette adresse).
 */
final class CibleCorrection
{
    public const TABLE_COMPTES = 'users';

    /** Le modele de chaque table, pour le journal d'audit. */
    private const MODELES = [
        'esbtp_etudiants' => ESBTPEtudiant::class,
        'esbtp_parents' => ESBTPParent::class,
        'users' => User::class,
        'esbtp_candidatures' => ESBTPCandidature::class,
        'esbtp_rdv_reservations' => ESBTPRdvReservation::class,
    ];

    public function __construct(
        public readonly string $table,
        public readonly string $colonne,
        public readonly int $id,
    ) {}

    /**
     * Null si la cle est mal formee ou vise une colonne qui n'est pas une
     * adresse inventoriee presente dans le schema.
     *
     * @param  list<array{table: string, colonne: string}>  $colonnes  InventaireAdresses::colonnes()
     */
    public static function depuisCle(string $cle, bool $inclureComptes, array $colonnes): ?self
    {
        if (preg_match('/^([a-z_]+):([a-z_]+):([1-9]\d{0,18})$/', $cle, $m) !== 1) {
            return null;
        }
        [, $table, $colonne, $id] = $m;
        if (! in_array(['table' => $table, 'colonne' => $colonne], $colonnes, true)
            || ($table === self::TABLE_COMPTES && ! $inclureComptes)) {
            return null;
        }

        return new self($table, $colonne, (int) $id);
    }

    public function cle(): string
    {
        return $this->table.':'.$this->colonne.':'.$this->id;
    }

    public function modele(): string
    {
        return self::MODELES[$this->table];
    }
}
