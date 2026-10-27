<?php

namespace Tests\Unit\Install;

use App\Support\Installation\InitialisationInstance;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Lancée deux fois en même temps, l'initialisation butait sur une table que
 * l'autre venait de créer (« Table 'cache' already exists »). Une seule à la
 * fois, et un état qui survit à une réponse HTTP perdue.
 */
class InitialisationInstanceTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dossier = storage_path('instance/installation');
        File::deleteDirectory($this->dossier);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossier);
        parent::tearDown();
    }

    public function test_une_seconde_initialisation_ne_demarre_pas_pendant_la_premiere(): void
    {
        File::ensureDirectoryExists($this->dossier);
        $premiere = fopen($this->dossier . '/initialisation.lock', 'c');
        $this->assertTrue(flock($premiere, LOCK_EX | LOCK_NB));

        $resultat = app(InitialisationInstance::class)->lancer('Lycée Test');

        $this->assertFalse($resultat['ok']);
        $this->assertSame('install.migration.en_cours', $resultat['code']);

        flock($premiere, LOCK_UN);
        fclose($premiere);
    }

    public function test_un_etat_en_cours_sans_verrou_tenu_se_lit_comme_interrompu(): void
    {
        File::ensureDirectoryExists($this->dossier);
        File::put($this->dossier . '/initialisation.json', json_encode([
            'statut' => InitialisationInstance::EN_COURS,
            'message' => 'Migrations en cours…',
            'terminal' => ['ligne'],
            'maj' => now()->toIso8601String(),
        ]));

        $etat = app(InitialisationInstance::class)->etat();

        $this->assertSame(InitialisationInstance::ECHEC, $etat['statut']);
        $this->assertSame(['ligne'], $etat['terminal']);
    }

    public function test_un_etat_en_cours_avec_verrou_tenu_reste_en_cours(): void
    {
        File::ensureDirectoryExists($this->dossier);
        File::put($this->dossier . '/initialisation.json', json_encode([
            'statut' => InitialisationInstance::EN_COURS, 'message' => '', 'terminal' => [], 'maj' => null,
        ]));
        $tenu = fopen($this->dossier . '/initialisation.lock', 'c');
        flock($tenu, LOCK_EX | LOCK_NB);

        $this->assertSame(InitialisationInstance::EN_COURS, app(InitialisationInstance::class)->etat()['statut']);

        flock($tenu, LOCK_UN);
        fclose($tenu);
    }
}
