<?php

namespace Tests\Feature\Exports;

use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Un test unitaire sur le helper ne prouve pas que le PDF est lisible : encore
 * faut-il que le gabarit s'en serve. On rend donc reellement l'export des
 * paiements et on inspecte le HTML produit avant conversion DomPDF.
 *
 * Les parametres sont injectes via le cache : `Setting::get` lit a travers
 * `Cache::remember`, ce qui evite une base de donnees sans rien simuler du
 * gabarit lui-meme.
 */
class ExportPaiementsPdfContrastTest extends TestCase
{
    private function configurerCouleurs(
        string $primaire,
        string $fondEntete,
        string $couleurTexte = '#ffffff'
    ): void {
        Cache::flush();

        $valeurs = [
            'pdf_primary_color' => $primaire,
            'pdf_header_bg_color' => $fondEntete,
            'pdf_header_text_color' => $couleurTexte,
            'pdf_secondary_color' => '#64748b',
            'pdf_accent_color' => '#f59e0b',
            'pdf_text_color' => '#1f2937',
        ];

        foreach ($valeurs as $cle => $valeur) {
            Cache::put("setting_{$cle}", $valeur, 600);
        }
    }

    private function rendreExport(): string
    {
        return view('esbtp.paiements.export-pdf', [
            'paiements' => collect(),
            'stats' => [
                'total' => 4,
                'montant_total' => 750000,
                'valides' => 3,
                'montant_valide' => 600000,
                'en_attente' => 1,
                'montant_en_attente' => 150000,
                'recovery_rate' => 80,
            ],
            'filters' => [],
            'settings' => ['school_name' => 'Etablissement de test'],
            'etablissement' => ['nom' => 'Etablissement de test'],
            'dateExport' => now(),
            'showCreatorColumn' => false,
            'creatorHeader' => null,
        ])->render();
    }

    /**
     * Extrait la couleur declaree pour une classe CSS du gabarit.
     */
    private function couleurDeLaClasse(string $html, string $classe): string
    {
        $trouve = preg_match(
            '/\.'.preg_quote($classe, '/').'\s*\{[^}]*?color:\s*([^;]+);/s',
            $html,
            $m
        );

        $this->assertSame(1, $trouve, "Regle CSS .{$classe} introuvable dans l'export rendu.");

        return strtolower(trim($m[1]));
    }

    /**
     * Le defaut a corriger : fond clair choisi par l'ecole + texte blanc en dur
     * = chiffres invisibles a l'impression.
     */
    public function test_light_primary_colour_does_not_render_white_kpi_text(): void
    {
        $this->configurerCouleurs('#ffd166', '#ffd166');
        $html = $this->rendreExport();

        // Le fond configure est bien celui utilise par la bande de KPI.
        $this->assertStringContainsString('background-color: #ffd166', $html);

        foreach (['pay-kpi-label', 'pay-kpi-value', 'pay-kpi-sub'] as $classe) {
            $couleur = $this->couleurDeLaClasse($html, $classe);

            $this->assertNotSame('white', $couleur, "{$classe} reste en blanc sur un fond clair.");
            $this->assertNotSame('#ffffff', $couleur, "{$classe} reste en blanc sur un fond clair.");

            $ratio = SettingsHelper::contrastRatio(
                SettingsHelper::relativeLuminance('#ffd166'),
                SettingsHelper::relativeLuminance($couleur)
            );
            $this->assertGreaterThanOrEqual(
                SettingsHelper::CONTRASTE_MINIMUM,
                round($ratio, 2),
                sprintf('%s : contraste %.2f:1 seulement.', $classe, $ratio)
            );
        }
    }

    /**
     * Non-regression : sur la primaire KLASSCI par defaut (bleu fonce), le texte
     * des KPI doit rester blanc.
     */
    public function test_dark_primary_colour_keeps_white_kpi_text(): void
    {
        $this->configurerCouleurs('#0453cb', '#0453cb');
        $html = $this->rendreExport();

        foreach (['pay-kpi-label', 'pay-kpi-value', 'pay-kpi-sub'] as $classe) {
            $this->assertSame(
                '#ffffff',
                $this->couleurDeLaClasse($html, $classe),
                "{$classe} devrait rester blanc sur la primaire foncee."
            );
        }
    }

    /**
     * On corrige l'illisible, on n'impose pas une charte : la couleur de texte
     * configuree par l'ecole est conservee tant qu'elle passe le contraste.
     */
    public function test_a_legible_configured_text_colour_is_preserved(): void
    {
        $this->configurerCouleurs('#0453cb', '#0453cb', '#ffe066');
        $html = $this->rendreExport();

        foreach (['pay-kpi-label', 'pay-kpi-value', 'pay-kpi-sub'] as $classe) {
            $this->assertSame(
                '#ffe066',
                $this->couleurDeLaClasse($html, $classe),
                "{$classe} devrait conserver la couleur configuree par l'ecole."
            );
        }
    }

    /**
     * Le theme partage imposait `.kpi-value { color: <primaire> !important }`,
     * pense pour un KPI sur fond blanc. Sur cette page les cellules sont peintes
     * AVEC la primaire : la regle rendait les chiffres invisibles quelle que soit
     * la couleur. Le gabarit ne doit plus dependre de cette classe.
     */
    public function test_kpi_band_does_not_use_the_shared_kpi_value_class(): void
    {
        $this->configurerCouleurs('#0453cb', '#0453cb');
        $html = $this->rendreExport();

        $this->assertStringNotContainsString('class="kpi-value"', $html);
        $this->assertStringNotContainsString('class="kpi-label"', $html);
        $this->assertStringNotContainsString('class="kpi-sub"', $html);
    }
}
