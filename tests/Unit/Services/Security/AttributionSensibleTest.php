<?php

namespace Tests\Unit\Services\Security;

use App\Services\Security\AttributionSensible;
use Tests\TestCase;

class AttributionSensibleTest extends TestCase
{
    public function test_attribuer_a_un_autre_n_est_pas_bloque_ici(): void
    {
        $this->assertNull(AttributionSensible::messageSiAutoAttribution(false, ['paiements.validate']));
    }

    public function test_s_auto_attribuer_la_caisse_est_refuse(): void
    {
        $message = AttributionSensible::messageSiAutoAttribution(true, ['users.manage', 'paiements.validate']);

        $this->assertNotNull($message);
        $this->assertStringContainsString('valider un paiement', $message);
        $this->assertStringNotContainsString('paiements.validate', $message);
    }

    public function test_s_auto_attribuer_un_droit_ordinaire_passe(): void
    {
        $this->assertNull(AttributionSensible::messageSiAutoAttribution(true, ['users.manage']));
    }
}
