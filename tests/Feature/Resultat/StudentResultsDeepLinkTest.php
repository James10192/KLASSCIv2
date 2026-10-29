<?php

namespace Tests\Feature\Resultat;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class StudentResultsDeepLinkTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;

    public function test_bts_historical_inscription_is_selected_even_when_another_year_is_current(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $inscription = $etudiant->inscriptions()->firstOrFail();

        $this->annee->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $response = $this->actingAs($this->superAdmin())->get(route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiant->id,
            'inscription_id' => $inscription->id,
            'annee_universitaire_id' => $this->annee->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'include_all_statuses' => 1,
        ]));

        $response->assertOk();
        $this->assertSame($inscription->id, $response->viewData('inscription')->id);
        $this->assertSame($this->annee->id, (int) $response->viewData('annee_id'));
    }

    public function test_bts_deep_link_refuses_another_students_inscription(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $autreEtudiant = $this->etudiantInscrit();
        $autreInscription = ESBTPInscription::where('etudiant_id', $autreEtudiant->id)->firstOrFail();

        $this->actingAs($this->superAdmin())->get(route('esbtp.resultats.etudiant', [
            'etudiant' => $etudiant->id,
            'inscription_id' => $autreInscription->id,
            'annee_universitaire_id' => $this->annee->id,
            'classe_id' => $this->classe->id,
            'periode' => 'semestre1',
            'include_all_statuses' => 1,
        ]))->assertNotFound();
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }
}
