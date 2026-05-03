<?php

namespace Tests\Feature\Notes;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PR #3+#4 — Robustesse saisie notes + refonte UX premium du modal.
 *
 * Ces tests valident la présence des nouvelles fonctionnalités UI/UX dans la
 * vue notes.index ET la nouvelle route esbtp.evaluations.quick-update.
 *
 * Voir CHANGELOG.md (mai 2026) pour le détail.
 */
class NotesModalUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('notes.view', 'web');
        Permission::findOrCreate('notes.create', 'web');
        Permission::findOrCreate('notes.edit', 'web');
        Permission::findOrCreate('notes.delete', 'web');
        Permission::findOrCreate('module.notes_evaluations.access', 'web');
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('evaluations.create', 'web');
        Role::findOrCreate('superAdmin', 'web');
    }

    private function authUser(): User
    {
        // superAdmin pour bypasser le middleware CheckInstalled (qui exige
        // au moins un user avec le rôle superAdmin pour considérer
        // l'application comme installée). Gate::before() bypass aussi
        // toutes les permissions, donc pas besoin de givePermissionTo().
        $user = User::factory()->create();
        $user->assignRole('superAdmin');
        $user->givePermissionTo('notes.view');
        $user->givePermissionTo('notes.create');
        $user->givePermissionTo('notes.edit');
        $user->givePermissionTo('notes.delete');
        $user->givePermissionTo('module.notes_evaluations.access');
        $user->givePermissionTo('admin.access');
        $user->givePermissionTo('evaluations.create');
        $this->actingAs($user);

        return $user;
    }

    // ─── View smoke tests ───────────────────────────────────────────────

    public function test_it_renders_notes_index_with_premium_modal_elements(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $response = $this->get('/esbtp/notes');
        $response->assertStatus(200);
        $html = $response->getContent();

        // Modal classSelectionModal toujours présent (existant)
        $this->assertStringContainsString('id="classSelectionModal"', $html);
    }

    public function test_it_includes_network_badge_in_modal_header(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="nm-network-badge"', $html);
        $this->assertStringContainsString('nm-network-dot', $html);
        $this->assertStringContainsString('Synchronisé', $html);
    }

    public function test_it_includes_restore_draft_banner(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="nm-restore-banner"', $html);
        $this->assertStringContainsString('Brouillon non sauvegardé', $html);
        $this->assertStringContainsString('id="nm-restore-btn"', $html);
        $this->assertStringContainsString('id="nm-restore-discard"', $html);
    }

    public function test_it_includes_table_toolbar_with_search_input(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="nm-table-toolbar"', $html);
        $this->assertStringContainsString('id="nm-student-search"', $html);
        $this->assertStringContainsString('Rechercher un étudiant', $html);
        $this->assertStringContainsString('id="nm-students-count"', $html);
        $this->assertStringContainsString('id="nm-evaluations-count"', $html);
    }

    public function test_it_includes_load_more_pagination_button(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="nm-load-more-wrap"', $html);
        $this->assertStringContainsString('id="nm-load-more-btn"', $html);
        $this->assertStringContainsString('Charger', $html);
    }

    public function test_it_includes_horizontal_scroll_arrow(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="nm-scroll-arrow"', $html);
        $this->assertStringContainsString('class="nm-scroll-arrow"', $html);
    }

    public function test_it_includes_save_and_continue_button_in_eval_modal(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="evalModal_save_continue"', $html);
        $this->assertStringContainsString('Créer et continuer', $html);
    }

    public function test_it_includes_quick_edit_eval_modal(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('id="evaluationQuickEditModal"', $html);
        $this->assertStringContainsString('id="nm-eval-quick-form"', $html);
        $this->assertStringContainsString('id="nm-eval-quick-titre"', $html);
        $this->assertStringContainsString('id="nm-eval-quick-bareme"', $html);
        $this->assertStringContainsString('id="nm-eval-quick-coefficient"', $html);
        $this->assertStringContainsString('id="nm-eval-quick-save"', $html);
    }

    public function test_it_loads_common_js_for_iiconfirm(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('inscriptions/common.js', $html);
    }

    public function test_it_includes_keyboard_shortcuts_handler(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        // Le bloc PR #3+#4 contient le handler keydown pour Tab/Enter/Ctrl+S
        $this->assertStringContainsString('nmGetGridGeometry', $html);
        $this->assertStringContainsString('isCtrlS', $html);
        $this->assertStringContainsString('isCtrlF', $html);
    }

    public function test_it_includes_localstorage_autosave_helpers(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('nmAutosaveDraft', $html);
        $this->assertStringContainsString('nmRestoreFromDraft', $html);
        $this->assertStringContainsString('nm_notes_draft_', $html);
        $this->assertStringContainsString('nmPurgeOldDrafts', $html);
    }

    public function test_it_includes_premium_toast_helper(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('nmShowToast', $html);
        $this->assertStringContainsString('nm-toast-stack', $html);
    }

    public function test_it_includes_beforeunload_handler(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        $this->assertStringContainsString('beforeunload', $html);
        $this->assertStringContainsString('nmHasUnsavedChanges', $html);
    }

    public function test_keyboard_shortcuts_hint_is_visible_in_autosave_info(): void
    {
        $this->authUser();
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);

        $html = $this->get('/esbtp/notes')->getContent();

        // L'info bar mentionne désormais les raccourcis (Tab/Enter/Ctrl+S/Esc)
        $this->assertStringContainsString('Ctrl+S', $html);
        $this->assertStringContainsString('Esc', $html);
    }

    // ─── quickUpdate route tests ────────────────────────────────────────

    public function test_quick_update_endpoint_updates_titre_bareme_coefficient(): void
    {
        $user = $this->authUser();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id,
            'titre' => 'Ancien titre',
            'bareme' => 20,
            'coefficient' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->patchJson(
            "/esbtp/evaluations/{$eval->id}/quick-update",
            [
                'titre' => 'Nouveau titre — Devoir',
                'bareme' => 25,
                'coefficient' => 2.5,
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'evaluation' => [
                'id' => $eval->id,
                'titre' => 'Nouveau titre — Devoir',
                'bareme' => 25,
                'coefficient' => 2.5,
            ],
        ]);

        $eval->refresh();
        $this->assertSame('Nouveau titre — Devoir', $eval->titre);
        $this->assertSame(25.0, (float) $eval->bareme);
        $this->assertSame(2.5, (float) $eval->coefficient);
    }

    public function test_quick_update_validates_required_titre(): void
    {
        $user = $this->authUser();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->patchJson(
            "/esbtp/evaluations/{$eval->id}/quick-update",
            [
                'titre' => '',
                'bareme' => 20,
                'coefficient' => 1,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['titre']);
    }

    public function test_quick_update_rejects_bareme_out_of_range(): void
    {
        $user = $this->authUser();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->patchJson(
            "/esbtp/evaluations/{$eval->id}/quick-update",
            [
                'titre' => 'Test',
                'bareme' => 200, // > max:100
                'coefficient' => 1,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['bareme']);
    }

    public function test_quick_update_rejects_coefficient_out_of_range(): void
    {
        $user = $this->authUser();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $response = $this->patchJson(
            "/esbtp/evaluations/{$eval->id}/quick-update",
            [
                'titre' => 'Test',
                'bareme' => 20,
                'coefficient' => 50, // > max:10
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['coefficient']);
    }

    public function test_quick_update_requires_authentication(): void
    {
        // Pas d'authUser() ; on crée juste un user "owner" pour le FK created_by
        // sans s'authentifier avec lui.
        $owner = User::factory()->create();
        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => 1]);
        $classe = ESBTPClasse::factory()->create();
        $matiere = ESBTPMatiere::factory()->create();
        $eval = ESBTPEvaluation::factory()->create([
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'annee_universitaire_id' => $annee->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
        ]);

        $response = $this->patchJson(
            "/esbtp/evaluations/{$eval->id}/quick-update",
            [
                'titre' => 'Test',
                'bareme' => 20,
                'coefficient' => 1,
            ]
        );

        // Soit 401 (API), soit 302 (web redirect to login)
        $this->assertContains($response->status(), [302, 401]);
    }
}
