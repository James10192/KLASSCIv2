<?php

namespace Tests\Unit\Emails;

use App\Support\CompteursDemandesPortail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Entre `pull` et `migrate`, la colonne `verification_contact` n'existe pas
 * encore. Les compteurs de la barre laterale, rendus par chaque page, doivent
 * alors repondre 0 SANS interroger la table : sinon tout le back-office tombe.
 */
class CompteursDemandesPortailTest extends TestCase
{
    public function test_sans_la_colonne_les_compteurs_repondent_zero_sans_requete(): void
    {
        Cache::flush();
        Schema::shouldReceive('hasTable')->andReturn(true);
        Schema::shouldReceive('hasColumn')->with('esbtp_candidatures', 'verification_contact')->andReturn(false);
        Schema::shouldReceive('hasColumn')->with('esbtp_reinscription_demandes', 'verification_contact')->andReturn(false);

        DB::enableQueryLog();
        $compteurs = app(CompteursDemandesPortail::class);

        $this->assertSame(0, $compteurs->candidatures());
        $this->assertSame(0, $compteurs->reinscriptions());
        $this->assertSame([], DB::getQueryLog(), 'Aucune requete ne doit partir vers une table non migree.');
    }
}
