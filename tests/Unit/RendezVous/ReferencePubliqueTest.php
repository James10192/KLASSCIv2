<?php

namespace Tests\Unit\RendezVous;

use App\Enums\CanalPortailPublic;
use App\Enums\NaturePortailPublic;
use App\Services\RendezVous\ReferencePublique;
use Tests\TestCase;

class ReferencePubliqueTest extends TestCase
{
    public function test_la_reference_se_normalise_sans_tirets(): void
    {
        $this->assertSame(
            'AB12CD34EF56',
            app(ReferencePublique::class)->normaliser('ab12-cd34-ef56')
        );
    }

    public function test_le_canal_rendez_vous_a_ses_propres_seaux(): void
    {
        $rdv = CanalPortailPublic::Rendezvous->seaux(NaturePortailPublic::Identite, 'e');
        $candidatures = CanalPortailPublic::Candidatures->seaux(NaturePortailPublic::Identite, 'e');

        $this->assertNotSame($rdv[0]->cle, $candidatures[0]->cle);
        $this->assertStringContainsString('rendezvous', $rdv[0]->cle);
        $this->assertStringNotContainsString('matricule', $rdv[0]->cle);
    }
}
