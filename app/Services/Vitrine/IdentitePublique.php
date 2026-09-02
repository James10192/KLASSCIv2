<?php

namespace App\Services\Vitrine;

use App\Helpers\SettingsHelper;

/**
 * L'identité qu'un établissement accepte de montrer sur klassci.com.
 *
 * Le site vitrine affiche les logos de ses écoles et habille le formulaire
 * d'inscription aux couleurs de celle qu'on a choisie. Ces deux choses sont
 * déjà configurées par l'école, une seule fois, dans ses réglages : le logo
 * qu'elle a déposé et l'identité visuelle qu'elle a réglée pour ses documents
 * PDF. On les relit ici plutôt que de lui redemander la même chose ailleurs —
 * une école qui change de logo change son bulletin ET sa page d'inscription du
 * même geste.
 *
 * Ce que cette classe rend est PUBLIC au sens fort : aucune authentification
 * ne le protège. Elle ne sert donc que ce qu'une école imprime déjà en
 * en-tête de chaque bulletin qu'elle distribue — son nom, son sigle, sa ville,
 * son logo, ses couleurs. Rien qui la concerne comme cliente (offre, quotas,
 * effectifs), rien de nominatif, rien de financier. Le téléphone, l'adresse et
 * le nom du directeur figurent aussi dans les réglages : ils ne sont pas ici
 * parce que le site vitrine n'en a pas l'usage, et que la bonne mesure d'une
 * surface publique est ce dont l'appelant a besoin, pas ce qu'on a sous la main.
 *
 * Les couleurs sont ASSAINIES avant de partir. Elles viennent d'un réglage
 * libre et vont atterrir dans un attribut `style` du site vitrine : une valeur
 * comme `red;background:url(...)` y deviendrait une injection CSS. Seule une
 * notation hexadécimale passe ; tout le reste retombe sur la teinte KLASSCI.
 */
class IdentitePublique
{
    /** Le bleu KLASSCI, servi quand un réglage est vide ou illisible. */
    private const COULEUR_REPLI = '#0453cb';

    /**
     * Le texte d'en-tête est libre et s'affiche sur une seule ligne, sous le
     * nom de l'école. Au-delà, ce n'est plus une signature d'établissement mais
     * un paragraphe, et il casse la mise en page du portail.
     */
    private const LONGUEUR_MAX_ENTETE = 120;

    /**
     * @return array{
     *     code: string,
     *     nom: string,
     *     sigle: string,
     *     ville: string,
     *     pays: string,
     *     site_web: string,
     *     logo: array{present: bool, url: string|null},
     *     identite_visuelle: array{
     *         couleur_principale: string,
     *         couleur_secondaire: string,
     *         couleur_accent: string,
     *         couleur_texte: string,
     *         bandeau_fond: string,
     *         bandeau_texte: string,
     *         entete: string
     *     }
     * }
     */
    public function decrire(): array
    {
        $ecole = SettingsHelper::getSchoolInfo();
        $pdf = SettingsHelper::getPdfSettings();
        $logo = SettingsHelper::resolveLogoPath();

        return [
            'code' => (string) config('app.tenant_code', 'default'),
            'nom' => (string) ($ecole['name'] ?? ''),
            'sigle' => (string) ($ecole['acronym'] ?? ''),
            'ville' => (string) ($ecole['city'] ?? ''),
            'pays' => (string) ($ecole['country'] ?? ''),
            'site_web' => (string) ($ecole['website'] ?? ''),
            'logo' => [
                // Le drapeau plutôt que l'URL seule : le site vitrine décide
                // d'afficher un monogramme AVANT de tenter un chargement, au
                // lieu de découvrir un 404 dans le navigateur du visiteur.
                'present' => $logo !== null,
                'url' => $logo !== null ? route('api.public.etablissement.logo') : null,
            ],
            'identite_visuelle' => [
                'couleur_principale' => $this->couleur($pdf['primary_color'] ?? null),
                'couleur_secondaire' => $this->couleur($pdf['secondary_color'] ?? null),
                'couleur_accent' => $this->couleur($pdf['accent_color'] ?? null),
                'couleur_texte' => $this->couleur($pdf['text_color'] ?? null),
                'bandeau_fond' => $this->couleur($pdf['header_bg_color'] ?? null),
                // Déjà calculée par KLASSCI selon le contraste WCAG contre le
                // fond du bandeau : on ne la recalcule pas côté site, sans quoi
                // le document imprimé et la page web pourraient diverger.
                'bandeau_texte' => $this->couleur($pdf['header_text_color'] ?? null),
                'entete' => $this->entete($pdf['header_text'] ?? null),
            ],
        ];
    }

    /**
     * Une couleur hexadécimale, ou la teinte KLASSCI.
     *
     * Les formes à trois et à six chiffres sont acceptées, les notations à
     * canal alpha ne le sont pas : elles rendraient transparent un fond de
     * bandeau posé sous du texte blanc.
     */
    private function couleur(mixed $valeur): string
    {
        $brute = is_string($valeur) ? trim($valeur) : '';

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $brute) === 1
            ? strtolower($brute)
            : self::COULEUR_REPLI;
    }

    private function entete(mixed $valeur): string
    {
        $brute = is_string($valeur) ? trim(preg_replace('/\s+/u', ' ', $valeur) ?? '') : '';

        return mb_substr($brute, 0, self::LONGUEUR_MAX_ENTETE);
    }
}
