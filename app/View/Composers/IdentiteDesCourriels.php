<?php

namespace App\View\Composers;

use App\Helpers\SettingsHelper;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Ce qu'un courriel doit porter de l'école : nom, logo, coordonnées, et deux
 * nuances de sa couleur pour le gabarit `esbtp.emails.layout`.
 *
 * Enregistré APRÈS `CouleursDesCourrielsParents`, dont il lit la couleur
 * primaire. Un appelant qui fournit une valeur garde la main.
 *
 * Le logo est une URL publique, pas une pièce intégrée : les courriels partent
 * par MailPulse, qui ne transporte que du HTML.
 */
class IdentiteDesCourriels
{
    /**
     * Rouge d'alerte des courriels (`--danger` du design system) : bordure des
     * alertes, bouton de l'avis de résultats insuffisants. Une couleur de sens,
     * jamais de décoration — et la même pour toutes les écoles.
     */
    public const COULEUR_DANGER = '#dc2626';

    public function compose(View $view): void
    {
        $donnees = $view->getData();
        $ecole = SettingsHelper::getSchoolInfo();
        $primaire = (string) ($donnees['emailPrimaryColor'] ?? '#0453cb');
        $adresse = implode(', ', array_filter([
            trim((string) ($ecole['address'] ?? '')),
            trim((string) ($ecole['city'] ?? '')),
        ]));

        $view->with([
            'schoolName' => $donnees['schoolName'] ?? (trim((string) ($ecole['name'] ?? '')) ?: 'KLASSCI'),
            'schoolAddress' => $donnees['schoolAddress'] ?? $adresse,
            'schoolPhone' => $donnees['schoolPhone'] ?? trim((string) ($ecole['phone'] ?? '')),
            'schoolEmail' => $donnees['schoolEmail'] ?? trim((string) ($ecole['email'] ?? '')),
            'schoolLogoUrl' => $donnees['schoolLogoUrl'] ?? $this->logoPublic(),
            'emailPrimarySoft' => self::melanger($primaire, '#ffffff', 0.92),
            'emailPrimaryDark' => self::melanger($primaire, '#000000', 0.28),
            'emailDangerColor' => self::COULEUR_DANGER,
            'emailGardeGmailSombre' => self::texteQuasiBlanc((string) ($donnees['emailHeaderTextColor'] ?? '#ffffff')),
        ]);
    }

    /**
     * Le texte posé sur la couleur de l'école (en-tête, bouton) est-il blanc ou
     * presque ? C'est la seule condition où la garde contre l'inversion des
     * applications Gmail (`gm-ecran` / `gm-diff`, dans le gabarit) rend juste.
     *
     * Ces applications, en mode sombre, foncent le texte clair et éclaircissent
     * les fonds unis ; le gabarit défait l'inversion du texte par deux fondus
     * (`difference` puis `screen`) sur un fond noir. Le calcul ne restitue que
     * le BLANC : un texte sombre posé par une école à l'en-tête clair, passé par
     * le même fondu, disparaîtrait dans Gmail même en mode clair. D'où ce seuil,
     * et la garde omise en dessous.
     */
    public static function texteQuasiBlanc(string $couleur): bool
    {
        $luminance = SettingsHelper::relativeLuminance($couleur);

        return $luminance !== null && $luminance >= 0.85;
    }

    /** `$couleur` rapprochée de `$vers` dans la proportion `$part` (0 à 1). */
    public static function melanger(string $couleur, string $vers, float $part): string
    {
        if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $couleur)) {
            $couleur = '#0453cb';
        }
        $a = sscanf($couleur, '#%02x%02x%02x');
        $b = sscanf($vers, '#%02x%02x%02x');

        return vsprintf('#%02x%02x%02x', array_map(
            fn (int $x, int $y) => (int) round($x + ($y - $x) * $part),
            $a,
            $b,
        ));
    }

    private function logoPublic(): ?string
    {
        try {
            $url = app(IdentitePublique::class)->decrire()['logo']['url'] ?? null;
        } catch (\Throwable $e) {
            Log::warning("Courriels : logo de l'établissement introuvable", ['exception' => $e::class]);

            return null;
        }

        return is_string($url) && $url !== '' ? $url : null;
    }
}
