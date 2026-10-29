<?php

namespace App\Services;

class NumberToWords
{
    /**
     * Convertit un nombre en lettres (en français)
     *
     * @param float $number Le nombre à convertir
     * @return string Le nombre en lettres
     */
    public static function convert($number)
    {
        // Arrondir à 2 décimales et séparer les parties entière et décimale
        $number = round($number, 2);
        $parts = explode('.', (string) $number);

        $integer = (int) $parts[0];
        $decimal = isset($parts[1]) ? (int) $parts[1] : 0;

        // Convertir la partie entière
        $result = self::convertInteger($integer);

        // Ajouter la partie décimale si nécessaire
        if ($decimal > 0) {
            $result .= ' virgule ' . self::convertInteger($decimal);
        }

        return $result;
    }

    /**
     * Convertit un nombre entier en lettres
     *
     * @param int $number Le nombre entier à convertir
     * @return string Le nombre en lettres
     */
    private static function convertInteger($number)
    {
        if ($number === 0) {
            return 'zéro';
        }

        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingt', 'quatre-vingt-dix'];

        $result = '';

        // Traiter les milliards
        $billions = floor($number / 1000000000);
        if ($billions > 0) {
            // Pour les milliards, on garde "un milliard" car c'est correct en français
            $result .= self::convertInteger($billions) . ' milliard' . ($billions > 1 ? 's' : '') . ' ';
            $number %= 1000000000;
        }

        // Traiter les millions
        $millions = floor($number / 1000000);
        if ($millions > 0) {
            // Pour les millions, on garde "un million" car c'est correct en français
            $result .= self::convertInteger($millions) . ' million' . ($millions > 1 ? 's' : '') . ' ';
            $number %= 1000000;
        }

        // Traiter les milliers
        $thousands = floor($number / 1000);
        if ($thousands > 0) {
            if ($thousands === 1) {
                $result .= 'mille ';
            } else {
                // Convertir le nombre de milliers, mais supprimer "un" s'il est présent
                $thousandsText = self::convertInteger($thousands);
                // Si le texte commence par "un ", le supprimer
                if (substr($thousandsText, 0, 3) === 'un ') {
                    $thousandsText = substr($thousandsText, 3);
                }
                $result .= $thousandsText . ' mille ';
            }
            $number %= 1000;
        }

        // Traiter les centaines
        $hundreds = floor($number / 100);
        if ($hundreds > 0) {
            if ($hundreds === 1) {
                $result .= 'cent ';
            } else {
                // Convertir le nombre de centaines, mais supprimer "un" s'il est présent
                $hundredsText = self::convertInteger($hundreds);
                // Si le texte est exactement "un", le supprimer
                if ($hundredsText === 'un') {
                    $result .= 'cent ';
                } else {
                    $result .= $hundredsText . ' cent ';
                }
            }
            $number %= 100;
        }

        // Traiter les dizaines et unités
        if ($number > 0) {
            if ($number < 20) {
                $result .= $units[$number];
            } else {
                $ten = floor($number / 10);
                $unit = $number % 10;

                if ($ten === 7 || $ten === 9) {
                    $result .= $tens[$ten - 1] . '-';
                    $result .= $units[10 + $unit];
                } else {
                    $result .= $tens[$ten];
                    if ($unit > 0) {
                        $result .= ($ten === 8 ? '-' : ($unit === 1 ? ' et ' : '-')) . $units[$unit];
                    }
                    if ($ten === 8 && $unit === 0) {
                        $result .= 's';
                    }
                }
            }
        }

        return trim($result);
    }
}
