<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\Frais\PoserBareme;
use Tests\TestCase;

class NananFraisConfigurationContractTest extends TestCase
{
    public function test_nanan_expose_audience_et_echeance_par_combinaison(): void
    {
        $action = app(PoserBareme::class);
        $schema = $action->parameters();
        $ligne = $schema['properties']['configurations']['items'];
        $properties = $ligne['properties'];

        $this->assertSame(
            ['tous', 'nouveaux_etablissement', 'anciens_etablissement'],
            $properties['audience']['enum']
        );
        $this->assertSame(1, $properties['echeance_jours']['minimum']);
        $this->assertSame(365, $properties['echeance_jours']['maximum']);
        $this->assertNotContains('montant', $ligne['required']);
        $this->assertStringContainsString('QUE cette combinaison', $action->description());
        $this->assertStringContainsString('uniquement les nouveaux', $action->description());
        $this->assertStringContainsString('NE redemande PAS son montant', $action->description());
    }

    public function test_nanan_et_ecran_ecrivent_par_le_meme_writer_scoped(): void
    {
        $action = file_get_contents(app_path('Domain/Assistant/Actions/Frais/PoserBareme.php'));
        $pose = file_get_contents(app_path('Services/Frais/PoseDeBareme.php'));
        $writer = file_get_contents(app_path('Services/FraisConfigurationWriter.php'));

        $this->assertStringContainsString('PoseDeBareme', $action);
        $this->assertStringContainsString('FraisConfigurationWriter', $pose);
        $this->assertStringContainsString('$existing->audience', $writer);
        $this->assertStringNotContainsString('$category->audience = $audience', $writer);
    }
}
