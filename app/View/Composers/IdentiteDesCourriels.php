<?php

namespace App\View\Composers;

use App\Helpers\SettingsHelper;
use App\Services\Vitrine\IdentitePublique;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Ce qu'un courriel doit porter de l'école : nom, logo, coordonnées, deux
 * nuances de sa couleur pour le gabarit `esbtp.emails.layout`, et les trois
 * couleurs de sens (rouge, vert, orange), communes à toutes les écoles.
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

    /**
     * Vert de succès des courriels (`--success` du design system) : paiement
     * validé, montant réglé, moyenne suffisante. Même règle que le rouge : une
     * couleur de sens, commune à toutes les écoles.
     */
    public const COULEUR_SUCCES = '#10b981';

    /**
     * Orange d'avertissement (`--warning` du design system) : reste à payer,
     * absences justifiées, encadré de recommandations. À surveiller, sans être
     * une alerte.
     */
    public const COULEUR_ALERTE = '#f59e0b';

    /**
     * Vert et orange de TEXTE. Écrits sur fond blanc, `#10b981` ne donne que
     * 2,54:1 et `#f59e0b` 2,15:1 : illisibles pour un montant ou une note. Ces
     * deux teintes foncées dépassent 4,5:1 (WCAG AA). Les couleurs de sens
     * ci-dessus restent réservées aux fonds et aux bordures.
     */
    public const COULEUR_SUCCES_TEXTE = '#047857';

    public const COULEUR_ALERTE_TEXTE = '#b45309';

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
            'emailDangerColor' => $donnees['emailDangerColor'] ?? self::COULEUR_DANGER,
            'emailSuccessColor' => $donnees['emailSuccessColor'] ?? self::COULEUR_SUCCES,
            'emailWarningColor' => $donnees['emailWarningColor'] ?? self::COULEUR_ALERTE,
            'emailSuccessText' => $donnees['emailSuccessText'] ?? self::COULEUR_SUCCES_TEXTE,
            'emailWarningText' => $donnees['emailWarningText'] ?? self::COULEUR_ALERTE_TEXTE,
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
