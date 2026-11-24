<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\Classeur\MatieresDuClasseur;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FicheNotesExcelPageTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPClasse $classe;

    private ESBTPMatiere $algo;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('superAdmin', 'web');
        $this->actingAs(User::factory()->create()->assignRole('superAdmin'));
        $this->annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $this->classe = ESBTPClasse::factory()->create();
        $this->algo = ESBTPMatiere::factory()->create(['name' => 'Algorithmique', 'code' => 'ALGO']);
        $reseau = ESBTPMatiere::factory()->create(['name' => 'Réseaux', 'code' => 'RES']);
        $this->mock(MatieresDuClasseur::class, fn ($m) => $m->shouldReceive('pour')->andReturn(collect([$this->algo, $reseau])));
    }

    public function test_la_page_propose_de_choisir_une_matiere(): void
    {
        $this->get(route('esbtp.classes.fiche-notes-excel.index', $this->classe))
            ->assertOk()
            ->assertSee('ALGO · Algorithmique')
            ->assertSee('Toutes les matières (2)');
    }

    public function test_telecharger_une_seule_matiere(): void
    {
        Excel::fake();
        $this->get(route('esbtp.classes.fiche-notes-excel.telecharger', [$this->classe,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'matiere_id' => $this->algo->id]))->assertOk();
        Excel::matchByRegex();
        Excel::assertDownloaded('/-algo-semestre1\.xlsx$/', function ($export) {
            return count($export->sheets()) === 1;
        });
    }

    public function test_une_matiere_hors_programme_est_refusee(): void
    {
        $autre = ESBTPMatiere::factory()->create();
        $this->get(route('esbtp.classes.fiche-notes-excel.telecharger', [$this->classe,
            'annee_universitaire_id' => $this->annee->id, 'periode' => 'semestre1', 'matiere_id' => $autre->id]))->assertStatus(422);
    }
}
