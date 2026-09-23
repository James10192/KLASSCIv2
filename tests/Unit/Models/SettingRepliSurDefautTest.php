<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Quand la base ne repond pas, `Setting::get()` rend la valeur par defaut et le
 * journalise. Avant, sa branche de repli relancait la meme requete sans rien
 * pour la rattraper : toute vue rendue sans base levait une exception, via le
 * compositeur mobile branche sur toutes les vues.
 *
 * La panne est rendue deterministe par une base SQLite dont le fichier
 * n'existe pas : le test ne depend pas de la presence d'un serveur MySQL.
 */
class SettingRepliSurDefautTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => '/chemin/inexistant/klassci-test.sqlite',
        ]);
        DB::purge('sqlite');
        Cache::flush();

        $memo = new ReflectionProperty(Setting::class, 'replisJournalises');
        $memo->setAccessible(true);
        $memo->setValue(null, []);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_une_base_illisible_rend_la_valeur_par_defaut(): void
    {
        Log::spy();

        $this->assertSame('defaut', Setting::get('cle.absente', 'defaut'));
        $this->assertTrue(Setting::get('ui.mobile_shell.enabled', true));
    }

    public function test_le_repli_est_journalise_et_non_muet(): void
    {
        Log::spy();

        Setting::get('cle.absente', 'defaut');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message, $contexte) => $contexte['key'] === 'cle.absente');
    }

    public function test_une_meme_cle_ne_journalise_qu_une_fois_par_minute(): void
    {
        Log::spy();
        Carbon::setTestNow('2026-09-22 10:00:00');

        Setting::get('cle.absente', 'defaut');
        Setting::get('cle.absente', 'defaut');
        Carbon::setTestNow('2026-09-22 10:00:59');
        Setting::get('cle.absente', 'defaut');

        Log::shouldHaveReceived('warning')->once();

        // Une minute plus tard, une panne qui dure se signale de nouveau :
        // un worker de file vit des heures dans le meme processus.
        Carbon::setTestNow('2026-09-22 10:01:00');
        Setting::get('cle.absente', 'defaut');

        Log::shouldHaveReceived('warning')->twice();
    }

    public function test_chaque_cle_se_signale_separement(): void
    {
        Log::spy();

        Setting::get('premiere.cle', 1);
        Setting::get('seconde.cle', 2);

        Log::shouldHaveReceived('warning')->twice();
    }
}
