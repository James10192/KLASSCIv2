<?php

namespace Tests\Feature\Classes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verifie l'integration du composant <x-au-parcours-picker> dans le formulaire
 * classes.create / classes.edit (mode LMD).
 *
 * Couvre :
 *  1. Le composant est rendu (markup au-pp + factory window.auParcoursPicker)
 *  2. Les parcours portent data-mention-id pour la cascade JS
 *  3. Mode edit pre-fill : value initiale du parcours appliquee
 *  4. AJAX-safe : pas de @push, factory inline avec idempotency guard
 *  5. Listener cleanup destroy() present (anti memory leak)
 */
class ParcoursPickerCascadeTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateAsAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Auth::login($user);

        return $user;
    }

    private function setupLmdContext(): array
    {
        $domaine = ESBTPLMDDomaine::create([
            'name' => 'Sciences Juridiques',
            'code' => 'SCI-JUR',
            'is_active' => true,
        ]);
        $mention = ESBTPLMDMention::create([
            'name' => 'Droit',
            'code' => 'DROIT',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $filiereDroit = ESBTPFiliere::create([
            'name' => 'Droit',
            'code' => 'F-DROIT',
            'description' => 'Filiere Droit',
            'is_active' => true,
        ]);
        $parcoursPrive = ESBTPLMDParcours::create([
            'name' => 'Droit Prive',
            'code' => 'DROIT-PRIVE',
            'mention_id' => $mention->id,
            'filiere_id' => $filiereDroit->id,
            'is_active' => true,
        ]);
        $parcoursPublic = ESBTPLMDParcours::create([
            'name' => 'Droit Public',
            'code' => 'DROIT-PUBLIC',
            'mention_id' => $mention->id,
            'filiere_id' => $filiereDroit->id,
            'is_active' => true,
        ]);
        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 2',
            'code' => 'L2',
            'type' => 'Licence',
            'year' => 2,
            'is_active' => true,
        ]);
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        return compact('domaine', 'mention', 'filiereDroit', 'parcoursPrive', 'parcoursPublic', 'lmdNiveau', 'annee');
    }

    public function test_create_form_contains_parcours_picker_with_inline_factory(): void
    {
        $this->authenticateAsAdmin();
        $this->setupLmdContext();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.create', ['ajax' => 1]));

        $response->assertStatus(200);
        // Markup picker present
        $response->assertSee('class="au-pp', false);
        $response->assertSee('au-pp-trigger', false);
        // Factory inline avec idempotency guard
        $response->assertSee('window.auParcoursPicker', false);
        $response->assertSee('typeof window.auParcoursPicker !== \'function\'', false);
        // Cleanup destroy() pattern (anti memory leak)
        $response->assertSee('_mentionChangedHandler', false);
        $response->assertSee('removeEventListener', false);
        // AJAX-safe : pas de @push residuel
        $response->assertDontSee('@push(\'scripts\')', false);
        $response->assertDontSee('@endpush', false);
    }

    public function test_parcours_data_includes_mention_id_for_cascade(): void
    {
        $this->authenticateAsAdmin();
        $ctx = $this->setupLmdContext();

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.create', ['ajax' => 1]));

        $response->assertStatus(200);
        // Le data-parcours JSON doit contenir mentionId pour permettre la cascade JS
        $response->assertSee('mentionId', false);
        $response->assertSee('Droit Prive', false);
        $response->assertSee('DROIT-PRIVE', false);
        $response->assertSee('Droit Public', false);
        // La mention chip name doit etre transportee aussi (affichage premium)
        $response->assertSee('mentionName', false);
        // Le composant doit declarer la cible cascade via data-mention-filter
        $response->assertSee('data-mention-filter', false);
    }

    public function test_edit_form_prefills_parcours_value(): void
    {
        $this->authenticateAsAdmin();
        $ctx = $this->setupLmdContext();

        // Classe LMD existante avec un parcours selectionne
        $classe = ESBTPClasse::create([
            'name' => 'L2 Droit Prive',
            'code' => 'L2-DP',
            'filiere_id' => $ctx['filiereDroit']->id,
            'niveau_etude_id' => $ctx['lmdNiveau']->id,
            'annee_universitaire_id' => $ctx['annee']->id,
            'parcours_id' => $ctx['parcoursPrive']->id,
            'systeme_academique' => 'LMD',
            'places_totales' => 30,
            'places_occupees' => 0,
            'is_active' => true,
            'description' => 'Test',
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.edit', ['classe' => $classe->id, 'ajax' => 1]));

        $response->assertStatus(200);
        // L'attribut data-current du picker doit refleter le parcours_id de la classe
        $response->assertSee('data-current="' . $ctx['parcoursPrive']->id . '"', false);
    }
}
