<?php

namespace App\Domain\BtsTroncCommun;

use App\Helpers\SettingsHelper;

/**
 * Propriétaire EXCLUSIF du classement annuel d'un élève orienté BTS.
 *
 * L'origine fournit les notes du S1 et la spécialité celles du S2. Cela
 * ne décide jamais de la classe contre laquelle l'annuelle est classée.
 * Par défaut, seul le groupe de spécialité (classe actuelle) classe l'élève.
 */
final class BtsAnnualRankingPolicy
{
    public const KEY = 'tronc_commun_rang_annuel_classe';
    public const SPECIALISATION = 'specialisation';
    public const ORIGINE = 'origine';

    public function mode(): string
    {
        $value = SettingsHelper::get(self::KEY, self::SPECIALISATION);

        // Fail-closed sur le choix actuel : un réglage ancien ou invalide ne
        // déplace jamais silencieusement les rangs vers le tronc commun.
        return $value === self::ORIGINE ? self::ORIGINE : self::SPECIALISATION;
    }

    /**
     * @param array{semestre1_classe_id:int,semestre2_classe_id:int} $classMap
     */
    public function classeDeRang(array $classMap): int
    {
        return (int) ($this->mode() === self::ORIGINE
            ? $classMap['semestre1_classe_id']
            : $classMap['semestre2_classe_id']);
    }
}
