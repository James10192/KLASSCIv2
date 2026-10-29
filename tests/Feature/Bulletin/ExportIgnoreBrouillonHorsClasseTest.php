<?php

namespace Tests\Feature\Bulletin;

use App\Http\Controllers\ESBTPBulletinController;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class ExportIgnoreBrouillonHorsClasseTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
    }

    public function test_un_brouillon_hors_classe_n_alarme_pas_l_export(): void
    {
        $dansLaClasse = $this->etudiantInscrit();
        ESBTPBulletin::create([
            'etudiant_id' => $dansLaClasse->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => 12.5,
        ]);

        $parti = ESBTPEtudiant::factory()->create();
        ESBTPBulletin::create([
            'etudiant_id' => $parti->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'moyenne_generale' => null,
        ]);

        $controleur = app(ESBTPBulletinController::class);
        $methode = new \ReflectionMethod($controleur, 'contexteExport');
        $methode->setAccessible(true);
        $contexte = $methode->invoke($controleur, Request::create('/esbtp/bulletins', 'GET', [
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode_id' => 'semestre1',
        ]));

        $this->assertCount(1, $contexte['bulletin_ids']);
        $this->assertSame([], $contexte['ungenerated_ids']);
    }
}
