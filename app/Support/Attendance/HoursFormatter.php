<?php

namespace App\Support\Attendance;

/**
 * Formatage des heures (décimales) pour l'affichage.
 *
 * KLASSCI stocke les heures d'absence/présence en `decimal(6,2)` (voir
 * `esbtp_attendance_manual_hours`), mais pour l'affichage on veut :
 *
 *   - 4.00 → "4"
 *   - 4.50 → "4.5"
 *   - 4.25 → "4.25"
 *
 * Autrement dit : trailing-zero stripping après arrondi à 2 décimales.
 * Le pattern `rtrim(rtrim(number_format(...), '0'), '.')` était dupliqué
 * dans 3+ sites (les 2 partials manual-hours, ESBTPPDFService) — centralisé ici.
 *
 * Le séparateur décimal est paramétrable car `ESBTPPDFService` utilise
 * la virgule (convention française du bulletin) alors que les grilles de
 * saisie web utilisent le point (input type=number).
 */
final class HoursFormatter
{
    public static function format(float $hours, string $decimalSeparator = '.'): string
    {
        return rtrim(rtrim(number_format($hours, 2, $decimalSeparator, ''), '0'), $decimalSeparator);
    }
}
