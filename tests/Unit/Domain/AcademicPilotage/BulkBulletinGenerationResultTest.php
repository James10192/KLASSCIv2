<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\DTO\BulkBulletinGenerationResult;
use PHPUnit\Framework\TestCase;

/**
 * Ce que la génération en masse répond à l'écran : le message et le code HTTP
 * que la page affiche. On appelle le résultat, on ne lit pas son code source.
 */
class BulkBulletinGenerationResultTest extends TestCase
{
    public function test_une_generation_complete_repond_200_et_donne_les_nombres(): void
    {
        $resultat = new BulkBulletinGenerationResult(created: 3, regenerated: 2);

        $this->assertSame(200, $resultat->statusCode());
        $this->assertSame('3 bulletin(s) créé(s), 2 bulletin(s) recalculé(s).', $resultat->message());
    }

    public function test_une_generation_partielle_repond_207_et_compte_les_blocages(): void
    {
        $resultat = new BulkBulletinGenerationResult(created: 1, blockingErrors: [['x' => 1]], errors: [['y' => 2]]);

        $this->assertSame(207, $resultat->statusCode());
        $this->assertSame('1 bulletin(s) créé(s), 0 recalculé(s), avec 2 blocage(s).', $resultat->message());
    }

    public function test_un_blocage_sans_ecriture_n_est_jamais_presente_comme_un_succes(): void
    {
        $bloque = new BulkBulletinGenerationResult(blockingErrors: [['x' => 1]]);
        $enErreur = new BulkBulletinGenerationResult(errors: [['y' => 2]]);

        $this->assertSame(422, $bloque->statusCode());
        $this->assertStringStartsWith('Aucun bulletin généré :', $bloque->message());
        $this->assertSame(422, $enErreur->statusCode());
        $this->assertStringContainsString('une erreur est survenue', $enErreur->message());
    }

    public function test_sans_etudiant_ni_bulletin_le_message_le_dit(): void
    {
        $this->assertSame(200, (new BulkBulletinGenerationResult)->statusCode());
        $this->assertSame('Aucun étudiant actif trouvé pour cette classe et cette année.', (new BulkBulletinGenerationResult)->message());
        $this->assertStringContainsString('existent déjà', (new BulkBulletinGenerationResult(skipped: [['z' => 1]]))->message());
    }
}
