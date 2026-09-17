<?php

namespace App\View\Composers;

use App\Helpers\SettingsHelper;
use Illuminate\View\View;

/**
 * Les couleurs des courriels aux parents, disponibles AVANT le gabarit.
 *
 * ## Le défaut que ce composeur corrige
 *
 * Le gabarit `esbtp.emails.parents.layout` résolvait ces quatre couleurs dans
 * son propre `@php`. Or Blade évalue le corps d'un `@section` du modèle ENFANT
 * **avant** de rendre le gabarit : au moment où `paiement-valide.blade.php`
 * écrit `{{ $emailPrimaryColor }}`, la ligne qui la définit n'a pas encore
 * tourné.
 *
 * Conséquence relevée dans le journal de `presentation` :
 * `Undefined variable $emailPrimaryColor (View: …/paiement-valide.blade.php)`.
 * L'envoi est enveloppé dans un `try`, donc rien ne remontait à l'écran : le
 * parent ne recevait simplement pas l'avis de paiement validé.
 *
 * **Quatre** modèles étaient touchés — `paiement-valide` (celui que le journal a
 * révélé), `paiement-relance`, `absence-notification` et `note-published`. Un
 * cinquième s'en gardait par un `?? '#0453cb'` (`rendez-vous-convocation`), ce
 * qui montre que le piège avait déjà été rencontré sans être nommé : il y était
 * contourné à l'aveugle, au prix de la couleur de l'école, toujours remplacée
 * par celle d'usine. Ce repli devient sans effet, et la convocation prend enfin
 * la couleur configurée.
 *
 * ## Pourquoi un composeur et non un `@php` recopié dans chaque modèle
 *
 * Parce que le prochain courriel ajouté hériterait du piège. Ici, il n'a rien à
 * savoir : les couleurs sont là quand son `@section` s'évalue.
 *
 * Les valeurs passées explicitement par un appelant restent prioritaires — le
 * composeur ne pose que ce qui manque, comme le faisait le `??` du gabarit.
 */
class CouleursDesCourrielsParents
{
    /** Ce que l'école n'a pas configuré, ou a configuré de travers. */
    private const DEFAUTS = [
        'emailPrimaryColor' => '#0453cb',
        'emailHeaderTextColor' => '#ffffff',
        'emailSecondaryColor' => '#64748b',
    ];

    public function compose(View $view): void
    {
        $donnees = $view->getData();

        $primaire = $this->couleurSure(
            $donnees['emailPrimaryColor'] ?? SettingsHelper::get('pdf_primary_color', null),
            self::DEFAUTS['emailPrimaryColor']
        );

        $view->with([
            'emailPrimaryColor' => $primaire,
            // Le fond d'en-tête retombe sur la couleur primaire, pas sur une
            // valeur d'usine : une école qui a choisi sa couleur sans choisir
            // son en-tête doit voir la sienne.
            'emailHeaderBgColor' => $this->couleurSure(
                $donnees['emailHeaderBgColor'] ?? SettingsHelper::get('pdf_header_bg_color', null),
                $primaire
            ),
            'emailHeaderTextColor' => $this->couleurSure(
                $donnees['emailHeaderTextColor'] ?? SettingsHelper::getPdfSettings()['header_text_on_bg'] ?? null,
                self::DEFAUTS['emailHeaderTextColor']
            ),
            'emailSecondaryColor' => $this->couleurSure(
                $donnees['emailSecondaryColor'] ?? SettingsHelper::get('pdf_secondary_color', null),
                self::DEFAUTS['emailSecondaryColor']
            ),
        ]);
    }

    /**
     * Une couleur hexadécimale à six chiffres, ou le repli.
     *
     * Le contrôle vient du gabarit et il est conservé tel quel : ces valeurs
     * sont écrites dans un attribut `style`, et un réglage d'instance saisi à la
     * main peut porter n'importe quoi.
     */
    private function couleurSure(mixed $valeur, string $repli): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $valeur) ? (string) $valeur : $repli;
    }
}
