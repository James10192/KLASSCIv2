<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SettingsHelper;
use PHPUnit\Framework\TestCase;

/**
 * Le fond des bandeaux PDF vient d'un parametre d'etablissement. La couleur du
 * texte ne peut donc pas etre decretee : elle se deduit du fond via le calcul de
 * contraste WCAG 2.1 (luminance relative + rapport de contraste).
 */
class PdfContrastTest extends TestCase
{
    public function test_white_on_white_falls_back_to_dark(): void
    {
        $this->assertSame('#111827', SettingsHelper::contrastingText('#ffffff', '#ffffff'));
    }

    public function test_white_on_green_stays_white(): void
    {
        $this->assertSame('#ffffff', SettingsHelper::contrastingText('#0b7a2f', '#ffffff'));
    }

    public function test_dark_on_white_stays_dark(): void
    {
        $this->assertSame('#111827', SettingsHelper::contrastingText('#ffffff', '#111827'));
    }

    /**
     * Une ecole qui a choisi une couleur de texte autre que le blanc doit la
     * conserver tant qu'elle est lisible : on corrige l'illisible, on n'impose
     * pas une charte.
     */
    public function test_a_legible_custom_colour_is_preserved(): void
    {
        $this->assertSame('#ffe066', SettingsHelper::contrastingText('#0453cb', '#ffe066'));
        $this->assertSame('#0a0a0a', SettingsHelper::contrastingText('#ffd166', '#0a0a0a'));
    }

    /**
     * ... mais une couleur choisie illisible sur son fond est bien remplacee.
     */
    public function test_an_illegible_custom_colour_is_replaced(): void
    {
        $this->assertNotSame('#ffe066', SettingsHelper::contrastingText('#ffffff', '#ffe066'));
    }

    /**
     * Le cas signale par le fondateur : une ecole choisit une couleur claire,
     * le texte blanc devient invisible a l'impression.
     */
    public function test_light_backgrounds_get_dark_text(): void
    {
        foreach (['#ffd166', '#a7f3d0', '#f8fafc', '#e5e7eb', '#f59e0b'] as $clair) {
            $this->assertSame(
                '#111827',
                SettingsHelper::contrastingText($clair),
                "Un fond clair ({$clair}) doit recevoir du texte sombre."
            );
        }
    }

    /**
     * Non-regression : sur une couleur sombre, le blanc doit rester du blanc.
     * #0453cb est la primaire KLASSCI par defaut.
     */
    public function test_dark_backgrounds_keep_white_text(): void
    {
        foreach (['#0453cb', '#033a8e', '#000000', '#1f2937', '#dc2626'] as $sombre) {
            $this->assertSame(
                '#ffffff',
                SettingsHelper::contrastingText($sombre),
                "Un fond sombre ({$sombre}) doit conserver du texte blanc."
            );
        }
    }

    /**
     * DomPDF ignore une `background-color` invalide : la zone reste blanche comme
     * le papier. Rendre du blanc dessus serait invisible — on rend du sombre.
     */
    public function test_unusable_background_falls_back_to_dark(): void
    {
        $this->assertSame('#111827', SettingsHelper::contrastingText(null));
        $this->assertSame('#111827', SettingsHelper::contrastingText(''));
        $this->assertSame('#111827', SettingsHelper::contrastingText('   '));
        $this->assertSame('#111827', SettingsHelper::contrastingText('#12345'));
        $this->assertSame('#111827', SettingsHelper::contrastingText('#zzzzzz'));
        $this->assertSame('#111827', SettingsHelper::contrastingText('rgb(4,83,203)'));
        $this->assertSame('#111827', SettingsHelper::contrastingText('bleu'));
    }

    public function test_short_hex_is_expanded(): void
    {
        $this->assertSame('#ffffff', SettingsHelper::contrastingText('#000'));
        $this->assertSame('#111827', SettingsHelper::contrastingText('#fff'));
        // #abc === #aabbcc : les deux ecritures doivent donner le meme verdict.
        $this->assertSame(
            SettingsHelper::contrastingText('#aabbcc'),
            SettingsHelper::contrastingText('#abc')
        );
    }

    public function test_hash_and_whitespace_are_optional(): void
    {
        $this->assertSame(
            SettingsHelper::contrastingText('#0453cb'),
            SettingsHelper::contrastingText('  0453cb  ')
        );
    }

    /**
     * La couleur rendue doit reellement atteindre le seuil WCAG demande, pas
     * seulement "ne pas etre blanche".
     */
    public function test_returned_colour_meets_the_required_ratio(): void
    {
        foreach (['#ffffff', '#000000', '#0453cb', '#f59e0b', '#7c7c7c', '#a7f3d0'] as $fond) {
            $this->assertContrasteSuffisant($fond);
        }
    }

    /**
     * Balayage : quelle que soit la couleur choisie par l'ecole, le texte rendu
     * doit rester lisible. Les demi-teintes sont le cas critique — c'est la que
     * ni le blanc ni le sombre de la charte ne suffisent.
     */
    public function test_no_colour_can_produce_unreadable_text(): void
    {
        for ($canal = 0; $canal <= 255; $canal += 5) {
            $this->assertContrasteSuffisant(sprintf('#%02x%02x%02x', $canal, $canal, $canal));
        }

        foreach ([[255, 0, 0], [0, 255, 0], [0, 0, 255], [255, 255, 0], [0, 255, 255], [255, 0, 255]] as $rvb) {
            $this->assertContrasteSuffisant(sprintf('#%02x%02x%02x', ...$rvb));
        }
    }

    /**
     * Le seuil reste ajustable : un appelant qui rend du tres grand texte peut
     * demander 3:1 (WCAG "large text") sans que le helper le lui impose.
     * #8a8a8a est une demi-teinte ou les deux seuils divergent reellement.
     */
    public function test_threshold_is_adjustable(): void
    {
        $this->assertSame('#ffffff', SettingsHelper::contrastingText('#8a8a8a', '#ffffff', '#111827', 3.0));
        $this->assertSame('#111827', SettingsHelper::contrastingText('#8a8a8a', '#ffffff', '#111827', 4.5));
    }

    private function assertContrasteSuffisant(string $fond): void
    {
        $texte = SettingsHelper::contrastingText($fond);
        $ratio = SettingsHelper::contrastRatio(
            SettingsHelper::relativeLuminance($fond),
            SettingsHelper::relativeLuminance($texte)
        );

        $this->assertGreaterThanOrEqual(
            SettingsHelper::CONTRASTE_MINIMUM,
            round($ratio, 2),
            sprintf('Contraste insuffisant sur %s : %.2f:1 avec %s.', $fond, $ratio, $texte)
        );
    }

    public function test_luminance_is_null_only_when_unreadable(): void
    {
        $this->assertNull(SettingsHelper::relativeLuminance(null));
        $this->assertNull(SettingsHelper::relativeLuminance('nope'));
        // Le noir vaut 0.0, pas null : c'est une couleur, pas une absence.
        $this->assertSame(0.0, SettingsHelper::relativeLuminance('#000000'));
        $this->assertSame(1.0, SettingsHelper::relativeLuminance('#ffffff'));
    }

    /**
     * Bornes de l'echelle WCAG : 1:1 pour deux couleurs identiques, 21:1 pour
     * du noir sur blanc.
     */
    public function test_contrast_ratio_bounds(): void
    {
        $this->assertSame(1.0, SettingsHelper::contrastRatio(0.5, 0.5));
        $this->assertSame(21.0, round(SettingsHelper::contrastRatio(0.0, 1.0), 1));
    }

}
