<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Image d'apercu partagee sur les reseaux et les messageries.
 *
 * Elle porte le logo KLASSCI, quel que soit le tenant : c'est l'application
 * qu'on ouvre en cliquant le lien.
 *
 * Le logo est carre, alors qu'un apercu WhatsApp ou LinkedIn attend du
 * paysage. Envoye tel quel il s'affiche rogne ou minuscule ; on le recentre
 * donc sur un fond de marque, aux dimensions attendues.
 *
 * Aucune dependance obligatoire : sans GD, on sert le logo brut plutot que
 * rien du tout.
 */
final class OpenGraphImageService
{
    public const LARGEUR = 1200;
    public const HAUTEUR = 630;

    /** Part de la hauteur occupee par le logo. Au-dela, il touche les bords. */
    private const PROPORTION_LOGO = 0.62;

    /**
     * L'apercu porte toujours le logo KLASSCI, jamais celui de l'ecole : c'est
     * le logo de l'application, et c'est bien l'application qu'on ouvre en
     * cliquant le lien. Le nom de l'etablissement, lui, reste dans le titre et
     * la description, ou il dit a l'utilisateur ou il arrive.
     *
     * @return array{contenu: string, mime: string, etag: string}|null
     */
    public function image(): ?array
    {
        $chemin = public_path('images/LOGO-KLASSCI-PNG.png');
        $brut = @file_get_contents($chemin);
        if ($brut === false || $brut === '') {
            return null;
        }

        $etag = substr(sha1($brut.'|'.self::LARGEUR.'x'.self::HAUTEUR), 0, 16);

        $compose = $this->composer($brut);
        if ($compose !== null) {
            return ['contenu' => $compose, 'mime' => 'image/png', 'etag' => $etag];
        }

        // Repli : le logo tel quel. Un apercu imparfait vaut mieux qu'un lien nu.
        return ['contenu' => $brut, 'mime' => 'image/png', 'etag' => $etag];
    }

    /**
     * Compose le logo centre sur un fond de marque, ou null si GD manque ou si
     * l'image source est illisible.
     */
    private function composer(string $brut): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($brut);
        if ($source === false) {
            return null;
        }

        try {
            $toile = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);
            imagealphablending($toile, true);

            $this->peindreFond($toile);

            $largeurSource = imagesx($source);
            $hauteurSource = imagesy($source);
            if ($largeurSource < 1 || $hauteurSource < 1) {
                return null;
            }

            // Le logo garde ses proportions : un logo etire est pire qu'un logo petit.
            $hauteurCible = (int) round(self::HAUTEUR * self::PROPORTION_LOGO);
            $echelle = $hauteurCible / $hauteurSource;
            $largeurCible = (int) round($largeurSource * $echelle);

            $largeurMax = (int) round(self::LARGEUR * 0.72);
            if ($largeurCible > $largeurMax) {
                $echelle = $largeurMax / $largeurSource;
                $largeurCible = $largeurMax;
                $hauteurCible = (int) round($hauteurSource * $echelle);
            }

            $x = (int) round((self::LARGEUR - $largeurCible) / 2);
            $y = (int) round((self::HAUTEUR - $hauteurCible) / 2);

            imagecopyresampled(
                $toile, $source,
                $x, $y, 0, 0,
                $largeurCible, $hauteurCible,
                $largeurSource, $hauteurSource
            );

            ob_start();
            imagepng($toile, null, 6);
            $sortie = ob_get_clean();

            return is_string($sortie) && $sortie !== '' ? $sortie : null;
        } catch (\Throwable $e) {
            Log::warning('Apercu Open Graph : composition impossible, repli sur le logo brut.', [
                'erreur' => $e->getMessage(),
            ]);

            return null;
        } finally {
            if (isset($toile) && $toile instanceof \GdImage) {
                imagedestroy($toile);
            }
            if ($source instanceof \GdImage) {
                imagedestroy($source);
            }
        }
    }

    /**
     * Fond blanc casse plutot que bleu plein : le logo KLASSCI est bleu sur
     * transparent, il disparaitrait sur un aplat de sa propre couleur. Un
     * bandeau en bas rappelle la marque sans avaler le logo.
     */
    private function peindreFond(\GdImage $toile): void
    {
        $fond = imagecolorallocate($toile, 248, 250, 252);
        imagefilledrectangle($toile, 0, 0, self::LARGEUR, self::HAUTEUR, $fond);

        $bandeau = imagecolorallocate($toile, 4, 83, 203);
        $epaisseur = 14;
        imagefilledrectangle(
            $toile,
            0,
            self::HAUTEUR - $epaisseur,
            self::LARGEUR,
            self::HAUTEUR,
            $bandeau
        );
    }
}
