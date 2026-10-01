<?php

namespace Tests\Feature\Notifications;

use App\Services\Notifications\NotificationPresenter;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Le lien d'une notification ne sort jamais de l'application, mais une URL
 * absolue écrite par la planification (qui ne connaît que APP_URL) doit mener
 * là où l'on regarde : elle est ramenée à son chemin.
 */
class NotificationLienInterneTest extends TestCase
{
    private function lien(string $brut, string $hoteRequete): ?string
    {
        config(['app.url' => 'https://esbtp-abidjan.klassci.com']);
        $this->app->instance('request', Request::create('https://'.$hoteRequete.'/notifications'));

        $methode = new \ReflectionMethod(NotificationPresenter::class, 'safeUrl');
        $methode->setAccessible(true);

        return $methode->invoke(new NotificationPresenter, $brut);
    }

    public function test_une_url_absolue_sur_app_url_est_gardee_en_relatif_meme_sur_un_autre_hote(): void
    {
        $this->assertSame(
            '/esbtp-special/bulletins-taches/12/fichier?mode=apercu',
            $this->lien('https://esbtp-abidjan.klassci.com/esbtp-special/bulletins-taches/12/fichier?mode=apercu', 'abidjan.esbtp.ci')
        );
    }

    public function test_un_chemin_relatif_est_garde_tel_quel(): void
    {
        $this->assertSame('/esbtp/bulletins?classe_id=1', $this->lien('/esbtp/bulletins?classe_id=1', 'abidjan.esbtp.ci'));
    }

    public function test_un_domaine_externe_ou_un_schema_douteux_est_rejete(): void
    {
        $this->assertNull($this->lien('https://exemple-malveillant.com/esbtp/bulletins', 'abidjan.esbtp.ci'));
        $this->assertNull($this->lien('javascript:alert(1)', 'abidjan.esbtp.ci'));
        $this->assertNull($this->lien('//exemple-malveillant.com/x', 'abidjan.esbtp.ci'));
    }
}
