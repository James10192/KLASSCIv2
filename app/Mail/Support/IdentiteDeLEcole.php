<?php

namespace App\Mail\Support;

use App\Helpers\SettingsHelper;

/**
 * Le nom et la couleur de l'etablissement pour les courriels KLASSCI Care.
 * Lus dans les reglages de l'instance, jamais ecrits en dur.
 */
final class IdentiteDeLEcole
{
    /** @return array{schoolName: string, emailPrimaryColor: string} */
    public static function donnees(): array
    {
        $nom = trim((string) (SettingsHelper::getSchoolInfo()['name'] ?? ''));
        $couleur = (string) (SettingsHelper::getPdfSettings()['primary_color'] ?? '');

        return [
            'schoolName' => $nom !== '' ? $nom : (string) config('app.name'),
            'emailPrimaryColor' => preg_match('/^#[0-9a-fA-F]{3,8}$/', $couleur) ? $couleur : '#0453cb',
        ];
    }
}
