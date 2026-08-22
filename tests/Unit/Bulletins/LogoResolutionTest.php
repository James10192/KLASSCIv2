<?php

namespace Tests\Unit\Bulletins;

use App\Services\BulletinService;
use Tests\TestCase;

/**
 * Un etablissement qui n'a pas televerse son logo laisse school_logo vide.
 * Le repli construisait alors « storage/logos/ » : un DOSSIER, que file_exists
 * validait et que file_get_contents faisait exploser (« Is a directory »).
 * Toute la generation du bulletin s'arretait la, pour trois etablissements
 * sur cinq.
 *
 * Ces tests garantissent qu'aucun chemin de dossier ne peut plus etre pris
 * pour un logo, et qu'un etablissement sans logo obtient le repli KLASSCI
 * plutot qu'une erreur.
 */
class LogoResolutionTest extends TestCase
{
    private function service(): BulletinService
    {
        return app(BulletinService::class);
    }

    public function test_sans_logo_configure_on_obtient_le_repli_et_non_une_erreur(): void
    {
        foreach (['', null] as $vide) {
            $resultat = $this->service()->prepareLogoBase64($vide);

            $this->assertIsString($resultat, 'Le repli doit produire une image.');
            $this->assertStringStartsWith('data:image/', $resultat);
        }
    }

    public function test_un_chemin_de_dossier_n_est_jamais_pris_pour_un_logo(): void
    {
        $dossier = 'dossier-test-logo-'.bin2hex(random_bytes(4));
        $chemin = public_path($dossier);
        mkdir($chemin);

        try {
            $resultat = $this->service()->prepareLogoBase64($dossier);

            // Le dossier est ecarte : on retombe sur le repli, sans exception.
            $this->assertIsString($resultat);
            $this->assertStringStartsWith('data:image/', $resultat);
            $this->assertStringNotContainsString($dossier, $resultat);
        } finally {
            rmdir($chemin);
        }
    }

    public function test_un_logo_reel_est_bien_charge(): void
    {
        $resultat = $this->service()->prepareLogoBase64('images/LOGO-KLASSCI-PNG.png');

        $this->assertIsString($resultat);
        $this->assertStringStartsWith('data:image/png;base64,', $resultat);
        $this->assertGreaterThan(1000, strlen($resultat), 'Le logo doit avoir un contenu.');
    }
}
