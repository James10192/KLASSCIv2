<?php

namespace Tests\Feature\Classes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests Feature pour le comportement du form quand aucune mention LMD n'est configuree.
 *
 * Scenarios :
 *  1. Aucune ESBTPLMDMention active : la page form rend mais affiche l'alerte
 *     "Aucune mention LMD configurée" dans la section LMD.
 *  2. Si l'utilisateur tente de submit un niveau LMD sans mention disponible :
 *     422 avec message clair (covered by withValidator dans CreateLmdAwareFormTest).
 *  3. Au moins une mention active : l'alerte n'apparait pas et le picker est rendu.
 */
class MentionAvailabilityTest extends TestCase
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

    public function test_form_shows_alert_when_no_mention_configured(): void
    {
        $this->authenticateAsAdmin();
        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 1',
            'code' => 'L1',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);
        ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        // Aucune mention creee. Pre-remplir le niveau LMD via old input.
        session()->flash('_old_input', ['niveau_etude_id' => (string) $lmdNiveau->id]);
        $response = $this->get(route('esbtp.classes.create'));

        $response->assertStatus(200);
        $response->assertSee('Aucune mention LMD configurée', false);
        // Le picker ne doit pas etre rendu si pas de mentions
        $response->assertDontSee('class="au-mp"', false);
    }

    public function test_form_renders_picker_when_at_least_one_mention_configured(): void
    {
        $this->authenticateAsAdmin();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        ESBTPLMDMention::create([
            'name' => 'Mathematiques',
            'code' => 'MATH',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 1',
            'code' => 'L1',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);
        ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        session()->flash('_old_input', ['niveau_etude_id' => (string) $lmdNiveau->id]);
        $response = $this->get(route('esbtp.classes.create'));

        $response->assertStatus(200);
        $response->assertDontSee('Aucune mention LMD configurée', false);
        $response->assertSee('au-mp', false); // Le composant Mention Picker est rendu
    }

    public function test_inactive_mentions_are_excluded(): void
    {
        $this->authenticateAsAdmin();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Lettres', 'code' => 'LET', 'is_active' => true]);
        ESBTPLMDMention::create([
            'name' => 'Mention Inactive',
            'code' => 'INACTIVE',
            'domaine_id' => $domaine->id,
            'is_active' => false,
        ]);
        $lmdNiveau = ESBTPNiveauEtude::create([
            'name' => 'Licence 1',
            'code' => 'L1',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);
        ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        session()->flash('_old_input', ['niveau_etude_id' => (string) $lmdNiveau->id]);
        $response = $this->get(route('esbtp.classes.create'));

        $response->assertStatus(200);
        // Comme la seule mention est inactive, mentions est vide => alerte affichee
        $response->assertSee('Aucune mention LMD configurée', false);
    }
}
