<?php

namespace Tests\Unit\Annonces;

use App\Services\Annonces\ReferenceCourrielAnnonce;
use PHPUnit\Framework\TestCase;

final class ReferenceCourrielTentativeTest extends TestCase
{
    public function test_une_tentative_versionnee_isole_les_ecoles_et_les_livraisons(): void
    {
        $reference = ReferenceCourrielAnnonce::tentative('esbtp-abidjan', 2, 581, 2);
        $this->assertSame('klassci-v3-esbtp-abidjan-annonce-2-courriel-581-essai-2', $reference);
        $this->assertSame(
            ['annonce_id' => 2, 'livraison_id' => 581, 'tentative' => 2],
            ReferenceCourrielAnnonce::extraireTentative($reference, 'esbtp-abidjan')
        );
        $this->assertNull(ReferenceCourrielAnnonce::extraireTentative($reference, 'esbtp-yakro'));
        $this->assertNull(ReferenceCourrielAnnonce::tentative('default', 2, 581, 2));
        $this->assertNull(ReferenceCourrielAnnonce::tentative('esbtp-abidjan', 2, 581, 1));
        $this->assertNotSame($reference, ReferenceCourrielAnnonce::tentative('esbtp-abidjan', 2, 581, 3));
    }

    public function test_les_references_v3_forgees_et_hors_de_portee_sont_refusees(): void
    {
        foreach ([
            'klassci-v3-default-annonce-2-courriel-581-essai-2',
            'klassci-v3-esbtp-abidjan-annonce-02-courriel-581-essai-2',
            'klassci-v3-esbtp-abidjan-annonce-2-courriel-581-essai-01',
            'klassci-v3-esbtp-abidjan-annonce-2-courriel-581-essai-999999999999999999999999',
            'klassci-v3-esbtp-abidjan-annonce-2-courriel-581-essai-2-extra',
            'klassci-v3-esbtp-abidjan-annonce-2-courriel-581-essai-1',
        ] as $reference) {
            $this->assertNull(ReferenceCourrielAnnonce::extraireTentative($reference, 'esbtp-abidjan'));
        }
    }

    public function test_aucune_nouvelle_tentative_ne_modifie_le_parcours_v2_historique(): void
    {
        $livraison = (object) [
            'id' => 581, 'annonce_id' => 2, 'attempts' => 1,
            'mailpulse_request_id' => 'klassci-v2-esbtp-abidjan-annonce-2-courriel-581',
        ];
        $this->assertSame($livraison->mailpulse_request_id,
            ReferenceCourrielAnnonce::pourEnvoi($livraison, 'esbtp-abidjan'));
        $this->assertNull(ReferenceCourrielAnnonce::pourEnvoi($livraison, 'esbtp-yakro'));
    }
}
