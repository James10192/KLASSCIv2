<?php

namespace Tests\Unit\CLI;

use App\Http\Controllers\API\CLI\CLIUsageController;
use PHPUnit\Framework\TestCase;

class UsagePagesNormalisationTest extends TestCase
{
    public function test_les_identifiants_se_regroupent_sous_une_meme_page(): void
    {
        $this->assertSame('/esbtp/etudiants/{id}/edit', CLIUsageController::normaliser('https://islg.klassci.com/esbtp/etudiants/12/edit?tab=2'));
        $this->assertSame('/esbtp/etudiants/{id}/edit', CLIUsageController::normaliser('https://islg.klassci.com/esbtp/etudiants/98/edit'));
        $this->assertSame('/esbtp/paiements/{id}', CLIUsageController::normaliser('https://x.klassci.com/esbtp/paiements/77/'));
    }

    public function test_les_appels_d_api_ne_sont_pas_des_pages(): void
    {
        $this->assertNull(CLIUsageController::normaliser('https://x.klassci.com/api/cli/stats'));
        $this->assertNull(CLIUsageController::normaliser('console'));
    }
}
