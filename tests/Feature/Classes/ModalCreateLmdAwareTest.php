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
 * Verifie que le modal AJAX de classes.index recoit bien tout ce qu'il faut pour
 * que le LMD switch fonctionne :
 *  - Le partial AJAX contient la factory window.classeLmdForm inline (PR1.2)
 *  - Le data-niveau-types attribute est correctement encode sur le <select>
 *
 * Sans ces deux conditions, injectHtmlWithScripts + Alpine.initTree dans
 * index.blade.php (PR1.1) ne peuvent pas bootstrap le mode LMD/BTS.
 */
class ModalCreateLmdAwareTest extends TestCase
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

    public function test_ajax_response_contains_inline_classe_lmd_form_factory(): void
    {
        $this->authenticateAsAdmin();
        ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.create', ['ajax' => 1]));

        $response->assertStatus(200);

        // La factory DOIT etre inline dans le partial pour survivre a
        // l'injection AJAX (innerHTML n'execute pas les <script> mais
        // injectHtmlWithScripts les re-cree).
        $response->assertSee('window.classeLmdForm', false);
        $response->assertSee('typeof window.classeLmdForm === \'function\'', false);

        // Listener cleanup ajoute via destroy() (anti memory leak modal reopen)
        $response->assertSee('_mentionChangedHandler', false);
        $response->assertSee('removeEventListener', false);

        // Le partial NE doit PAS rester wrappe dans @push('scripts') sinon
        // le contenu est dropped en reponse AJAX standalone (pas de @stack).
        $response->assertDontSee('@push', false);
        $response->assertDontSee('@endpush', false);
    }

    public function test_ajax_response_exposes_niveau_types_on_select_data_attribute(): void
    {
        $this->authenticateAsAdmin();
        ESBTPNiveauEtude::create([
            'name' => 'Licence 1',
            'code' => 'L1',
            'type' => 'Licence',
            'year' => 1,
            'is_active' => true,
        ]);
        ESBTPNiveauEtude::create([
            'name' => '1ere annee BTS',
            'code' => 'BTS1',
            'type' => 'BTS',
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

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.create', ['ajax' => 1]));

        $response->assertStatus(200);
        $response->assertSee('data-niveau-types', false);
        $response->assertSee('Licence', false);
        $response->assertSee('BTS', false);
    }

    public function test_ajax_response_includes_au_mention_picker_inline_styles_and_factory(): void
    {
        $this->authenticateAsAdmin();
        $domaine = ESBTPLMDDomaine::create([
            'name' => 'Sciences Juridiques',
            'code' => 'SCI-JUR',
            'is_active' => true,
        ]);
        ESBTPLMDMention::create([
            'name' => 'Droit',
            'code' => 'DROIT',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        ESBTPNiveauEtude::create([
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

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('esbtp.classes.create', ['ajax' => 1]));

        $response->assertStatus(200);

        // Le composant au-mention-picker DOIT inliner styles + factory
        // sans @once @push pour survivre a l'injection AJAX (PR1.3).
        $response->assertSee('window.auMentionPicker', false);
        $response->assertSee('.au-mp', false);
        $response->assertSee('au-mp-trigger', false);
    }
}
