<?php

namespace Tests\Unit\Mail;

use App\Helpers\SettingsHelper;
use App\View\Composers\IdentiteDesCourriels;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Les avis aux parents ne portent plus de couleur Bootstrap en dur : le bleu
 * décoratif suit la couleur de l'école, et le rouge, le vert et l'orange sont
 * les couleurs de sens du composeur, communes à toutes les écoles.
 *
 * Les gabarits sont lus en texte : la présence d'un littéral suffit à rater le
 * contrôle, quel que soit le chemin de rendu.
 */
class CouleursEnDurDesAvisParentsTest extends TestCase
{
    private const INTERDITES = ['#007bff', '#0056b3', '#e7f3ff', '#dc3545', '#28a745', '#ffc107', '#17a2b8'];

    public function test_aucun_avis_aux_parents_ne_porte_plus_de_couleur_bootstrap(): void
    {
        $gabarits = glob(resource_path('views/esbtp/emails/parents/*.blade.php'));
        $this->assertNotEmpty($gabarits);

        foreach ($gabarits as $gabarit) {
            $texte = strtolower((string) file_get_contents($gabarit));
            foreach (self::INTERDITES as $couleur) {
                $this->assertStringNotContainsString($couleur, $texte, basename($gabarit)." porte encore $couleur.");
            }
        }
    }

    public function test_les_couleurs_de_sens_sont_celles_du_design_system(): void
    {
        $this->assertSame('#dc2626', IdentiteDesCourriels::COULEUR_DANGER);
        $this->assertSame('#10b981', IdentiteDesCourriels::COULEUR_SUCCES);
        $this->assertSame('#f59e0b', IdentiteDesCourriels::COULEUR_ALERTE);
    }

    public function test_les_couleurs_de_texte_sont_lisibles_sur_fond_blanc(): void
    {
        foreach ([IdentiteDesCourriels::COULEUR_SUCCES_TEXTE, IdentiteDesCourriels::COULEUR_ALERTE_TEXTE] as $couleur) {
            $this->assertGreaterThanOrEqual(4.5, $this->contrasteSurBlanc($couleur), "$couleur est illisible sur fond blanc.");
        }
    }

    public function test_aucun_texte_n_est_ecrit_dans_une_couleur_de_fond(): void
    {
        foreach (glob(resource_path('views/esbtp/emails/parents/*.blade.php')) as $gabarit) {
            preg_match_all('/(?<![-\w])color:\s*\{\{[^}]*\}\}/', (string) file_get_contents($gabarit), $styles);
            foreach ($styles[0] as $style) {
                $this->assertDoesNotMatchRegularExpression(
                    '/\$email(Success|Warning)Color\b/',
                    $style,
                    basename($gabarit)." écrit du texte en couleur de fond : $style",
                );
            }
        }
    }

    public function test_le_composeur_expose_le_vert_et_l_orange(): void
    {
        $donnees = $this->composer(view('esbtp.emails.layout'));

        $this->assertSame(IdentiteDesCourriels::COULEUR_SUCCES, $donnees['emailSuccessColor']);
        $this->assertSame(IdentiteDesCourriels::COULEUR_ALERTE, $donnees['emailWarningColor']);
        $this->assertSame(IdentiteDesCourriels::COULEUR_DANGER, $donnees['emailDangerColor']);
        $this->assertSame(IdentiteDesCourriels::COULEUR_SUCCES_TEXTE, $donnees['emailSuccessText']);
        $this->assertSame(IdentiteDesCourriels::COULEUR_ALERTE_TEXTE, $donnees['emailWarningText']);
    }

    public function test_une_valeur_fournie_par_l_appelant_garde_la_main(): void
    {
        $donnees = $this->composer(view('esbtp.emails.layout', [
            'emailSuccessColor' => '#123456',
            'emailWarningColor' => '#654321',
        ]));

        $this->assertSame('#123456', $donnees['emailSuccessColor']);
        $this->assertSame('#654321', $donnees['emailWarningColor']);
    }

    private function contrasteSurBlanc(string $couleur): float
    {
        $l = SettingsHelper::relativeLuminance($couleur);
        $this->assertNotNull($l);

        return (1.0 + 0.05) / ($l + 0.05);
    }

    private function composer(\Illuminate\View\View $vue): array
    {
        Cache::flush();
        Cache::put('setting_school_name', 'Institut Supérieur KLASSCI', 3600);
        Cache::put('setting_school_logo', '', 3600);

        (new IdentiteDesCourriels())->compose($vue);

        return $vue->getData();
    }
}
