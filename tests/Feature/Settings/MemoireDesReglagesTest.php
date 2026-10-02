<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La memoire de requete des reglages n'existe que hors console : sans forcer
 * ce mode, aucun test ne l'exercerait. On le force ici, comme sous PHP-FPM.
 */
class MemoireDesReglagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->horsConsole(true);
        $this->viderMemoire();
    }

    protected function tearDown(): void
    {
        $this->viderMemoire();
        $this->horsConsole(false);
        parent::tearDown();
    }

    public function test_une_ecriture_par_le_modele_est_vue_par_la_lecture_suivante(): void
    {
        $reglage = $this->reglage('school_name', 'Ecole A');

        $this->assertSame('Ecole A', Setting::get('school_name'));

        $reglage->update(['value' => 'Ecole B']);

        $this->assertSame('Ecole B', Setting::get('school_name'));
    }

    public function test_une_cle_absente_ne_repart_pas_en_base_a_chaque_lecture(): void
    {
        $this->assertSame('defaut', Setting::get('cle_jamais_creee', 'defaut'));
        $this->viderMemoire();

        DB::enableQueryLog();
        $this->assertSame('defaut', Setting::get('cle_jamais_creee', 'defaut'));
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_creer_une_cle_absente_par_le_modele_la_rend_visible(): void
    {
        $this->assertNull(Setting::get('cle_creee_ensuite'));

        $this->reglage('cle_creee_ensuite', 'valeur');

        $this->assertSame('valeur', Setting::get('cle_creee_ensuite'));
    }

    public function test_le_defaut_d_une_cle_absente_n_est_pas_fige(): void
    {
        $this->assertSame('premier', Setting::get('cle_absente', 'premier'));
        $this->viderMemoire();

        $this->assertSame('second', Setting::get('cle_absente', 'second'));
        $this->assertNull(Cache::get('setting_cle_absente'));
    }

    private function reglage(string $cle, string $valeur): Setting
    {
        return Setting::create([
            'key' => $cle, 'value' => $valeur, 'type' => 'string', 'group' => 'general',
            'description' => $cle, 'is_required' => false, 'is_active' => true,
        ]);
    }

    private function horsConsole(bool $horsConsole): void
    {
        $propriete = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $propriete->setAccessible(true);
        $propriete->setValue($this->app, $horsConsole ? false : null);
    }

    private function viderMemoire(): void
    {
        $propriete = new \ReflectionProperty(Setting::class, 'memoire');
        $propriete->setAccessible(true);
        $propriete->setValue(null, []);
    }
}
