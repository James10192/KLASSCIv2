<?php

namespace Tests\Unit\LMD;

use App\Models\ESBTPLMDMention;
use App\Services\LMD\RefusDeDeplacement;
use Tests\TestCase;

class RefusDeDeplacementTest extends TestCase
{
    public function test_un_autre_parent_est_un_conflit(): void
    {
        $mention = new ESBTPLMDMention(['domaine_id' => 1, 'name' => 'Gestion', 'code' => 'gestion']);
        $refus = (new RefusDeDeplacement())->siAutreParent(
            $mention,
            'domaine_id',
            2,
            'MENTION',
            'gestion',
            fn ($e) => $e->name,
        );

        $this->assertSame(['type' => 'MENTION', 'code' => 'gestion', 'detail' => 'Gestion'], $refus);
    }

    public function test_le_meme_parent_n_est_pas_un_conflit(): void
    {
        $mention = new ESBTPLMDMention(['domaine_id' => 4, 'code' => 'gestion']);

        $this->assertNull((new RefusDeDeplacement())->siAutreParent(
            $mention, 'domaine_id', 4, 'MENTION', 'gestion', fn () => 'x',
        ));
    }

    public function test_une_fiche_absente_n_est_pas_un_conflit(): void
    {
        $this->assertNull((new RefusDeDeplacement())->siAutreParent(
            null, 'domaine_id', 1, 'MENTION', 'gestion', fn () => 'x',
        ));
    }

    public function test_une_cle_absente_de_la_maquette_n_ecrase_pas(): void
    {
        $valeurs = (new RefusDeDeplacement())->preserver(
            ['name' => 'Sciences'],
            ['name' => 'Sciences'],
            'nature',
        );

        $this->assertArrayNotHasKey('nature', $valeurs);
    }

    public function test_une_cle_donnee_est_ecrite(): void
    {
        $valeurs = (new RefusDeDeplacement())->preserver(
            ['name' => 'Sciences'],
            ['nature' => 'ufr'],
            'nature',
        );

        $this->assertSame('ufr', $valeurs['nature']);
    }
}
