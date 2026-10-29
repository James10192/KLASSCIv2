<?php

namespace App\Services\LMD;

use Illuminate\Database\Eloquent\Model;

/**
 * Un upsert par code ne deplace pas une fiche deja rattachee ailleurs.
 *
 * Le code d'une mention, d'un parcours ou d'un ECUE est unique dans l'ecole.
 * updateOrCreate reecrivait le parent : importer « Gestion » sous un second
 * domaine y deplacait la mention du premier, avec ses parcours, sans un mot.
 */
class RefusDeDeplacement
{
    /**
     * @param  callable(Model): string  $detail
     * @return array{type: string, code: string, detail: string}|null
     */
    public function siAutreParent(
        ?Model $existant,
        string $colonneParent,
        int $parentAttendu,
        string $type,
        string $code,
        callable $detail,
    ): ?array {
        if ($existant === null) {
            return null;
        }

        $actuel = $existant->{$colonneParent};
        if ($actuel === null || (int) $actuel === $parentAttendu) {
            return null;
        }

        return [
            'type' => $type,
            'code' => $code,
            'detail' => $detail($existant),
        ];
    }

    /**
     * N'ecrit une cle que si la maquette la donne : une omission n'efface pas
     * ce que l'ecran a pose (nature UFR/ecole d'un domaine).
     *
     * @param  array<string, mixed>  $valeurs
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public function preserver(array $valeurs, array $source, string $cle): array
    {
        if (array_key_exists($cle, $source)) {
            $valeurs[$cle] = $source[$cle];
        }

        return $valeurs;
    }
}
