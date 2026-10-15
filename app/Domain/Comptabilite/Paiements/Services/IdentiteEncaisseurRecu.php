<?php

namespace App\Domain\Comptabilite\Paiements\Services;

use App\Helpers\SettingsHelper;

/**
 * Politique de presentation de l'encaisseur sur les recus du tenant.
 * Le signataire officiel reste distinct et n'est pas abrege par ce reglage.
 */
final class IdentiteEncaisseurRecu
{
    public const KEY = 'receipt_cashier_display';
    public const NOM = 'nom';
    public const INITIALES = 'initiales';

    public static function options(): array
    {
        return [self::NOM, self::INITIALES];
    }

    public static function afficher(?string $nom, ?string $mode = null): string
    {
        $nom = trim((string) $nom);
        if ($nom === '') {
            return '—';
        }

        $mode ??= (string) SettingsHelper::get(self::KEY, self::NOM);
        if ($mode !== self::INITIALES) {
            return $nom;
        }

        $mots = preg_split('/[\s\-]+/u', $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initiales = [];
        foreach ($mots as $mot) {
            $premiere = mb_substr($mot, 0, 1, 'UTF-8');
            if ($premiere !== '') {
                $initiales[] = mb_strtoupper($premiere, 'UTF-8').'.';
            }
        }

        return $initiales ? implode(' ', $initiales) : $nom;
    }
}
