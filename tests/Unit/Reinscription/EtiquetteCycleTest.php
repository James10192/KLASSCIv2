<?php

namespace Tests\Unit\Reinscription;

use App\Models\ESBTPNiveauEtude;
use PHPUnit\Framework\TestCase;

class EtiquetteCycleTest extends TestCase
{
    public function test_licence_master_doctorat_sont_lmd(): void
    {
        foreach (['Licence', 'Master', 'Doctorat'] as $type) {
            $niveau = new ESBTPNiveauEtude;
            $niveau->type = $type;
            $this->assertSame('LMD', $niveau->etiquetteCycle());
        }
    }

    public function test_bts_reste_bts(): void
    {
        $niveau = new ESBTPNiveauEtude;
        $niveau->type = 'BTS';
        $this->assertSame('BTS', $niveau->etiquetteCycle());
    }
}
