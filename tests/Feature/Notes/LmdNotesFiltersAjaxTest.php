<?php

namespace Tests\Feature\Notes;

use App\Http\Middleware\PaywallMiddleware;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LmdNotesFiltersAjaxTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PaywallMiddleware::class);
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');
        $this->actingAs($user);
    }

    private function classe(
        string $nom,
        ESBTPAnneeUniversitaire $annee,
        ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        bool $active,
        int $capacite
    ): ESBTPClasse {
        $classe = ESBTPClasse::factory()->create([
            'name' => $nom,
            'annee_universitaire_id' => $annee->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => $active,
            'places_totales' => $capacite,
        ]);
        // Le modele deduit BTS du niveau de la factory ; la mise a jour SQL
        // reproduit les classes LMD de l'etablissement sans lancer ses events.
        ESBTPClasse::whereKey($classe->id)->update(['systeme_academique' => 'LMD']);

        return $classe;
    }

    private function filtrer(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->get(route('esbtp.lmd.notes.index', array_merge(['classes_ajax' => '1'], $params)));
    }

    public function test_ajax_combine_recherche_filiere_niveau_statut_et_capacite(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $autreFiliere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $this->classe('LMD FILTRE CIBLE', $annee, $filiere, $niveau, false, 0);
        $this->classe('LMD AUTRE CLASSE', $annee, $autreFiliere, $niveau, false, 0);
        $this->classe('LMD ACTIVE', $annee, $filiere, $niveau, true, 0);

        $this->filtrer([
            'annee_universitaire_id' => $annee->id,
            'search' => 'FILTRE CIBLE',
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'statut' => 'inactive',
            'capacite' => 'pleine',
        ])->assertOk()
            ->assertJsonPath('total_classes', 1)
            ->assertSee('LMD FILTRE CIBLE')
            ->assertDontSee('LMD AUTRE CLASSE')
            ->assertDontSee('LMD ACTIVE');
    }

    public function test_capacite_suit_les_inscriptions_de_l_annee_demandee(): void
    {
        $anneeA = ESBTPAnneeUniversitaire::factory()->create();
        $anneeB = ESBTPAnneeUniversitaire::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();
        $classe = $this->classe('LMD CAPACITE ANNUELLE', $anneeA, $filiere, $niveau, true, 1);

        ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $anneeA->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'status' => 'active',
            // Une inscription active occupe la capacite avant la creation
            // effective du compte etudiant.
            'workflow_step' => 'documents_complets',
        ]);

        $this->filtrer(['annee_universitaire_id' => $anneeA->id, 'capacite' => 'pleine'])
            ->assertOk()->assertJsonPath('total_classes', 1);
        $this->filtrer(['annee_universitaire_id' => $anneeB->id, 'capacite' => 'disponible'])
            ->assertOk()->assertJsonPath('total_classes', 1);
        $this->filtrer(['annee_universitaire_id' => $anneeB->id, 'capacite' => 'pleine'])
            ->assertOk()->assertJsonPath('total_classes', 0);
    }

    public function test_filtres_invalides_refuses_en_json(): void
    {
        $this->filtrer(['statut' => 'administrator', 'capacite' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['statut', 'capacite']);
    }

    public function test_la_vue_utilise_des_selects_premium_et_ajax_sans_reload(): void
    {
        $view = file_get_contents(resource_path('views/esbtp/lmd/notes/index.blade.php'));

        foreach (['filiere_id', 'niveau_id', 'statut', 'capacite', 'annee_universitaire_id'] as $name) {
            $this->assertStringContainsString('name="'.$name.'"', $view);
        }
        foreach (['ueSelect', 'ecueSelect', 'periodeFilter', 'evalType', 'evalPeriode'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $view);
        }
        $this->assertStringNotContainsString('<select', $view);
        $this->assertStringContainsString('new AbortController()', $view);
        $this->assertStringContainsString('history.replaceState', $view);
        $this->assertStringContainsString('restoreCommitted()', $view);
        $this->assertStringContainsString('lmdAcademicYearId = newYear', $view);
        $this->assertStringNotContainsString('location.reload(', $view);
    }
}
