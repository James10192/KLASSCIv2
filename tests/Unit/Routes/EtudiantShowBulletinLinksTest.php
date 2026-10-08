<?php

namespace Tests\Unit\Routes;

use Tests\TestCase;

class EtudiantShowBulletinLinksTest extends TestCase
{
    public function test_les_bulletins_historiques_sont_ouverts_par_id_de_bulletin_et_non_comme_id_etudiant(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/etudiants/show.blade.php'));

        $this->assertStringContainsString("route('esbtp.bulletins.preview-pdf', \$ab)", $view);
        $this->assertStringContainsString("route('esbtp.bulletins.download', \$ab)", $view);
        $this->assertStringNotContainsString("['bulletin' => \$ab->id", $view);
    }
}
