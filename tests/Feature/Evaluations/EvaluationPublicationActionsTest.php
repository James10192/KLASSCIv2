<?php

namespace Tests\Feature\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Contrat AJAX et rétrocompatibilité des publications.
 * Exécuter uniquement avec la base MariaDB isolée de scripts/ci-local.sh.
 */
class EvaluationPublicationActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // En CI, aucune installation/abonnement tenant : conserver auth et permissions.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        foreach (['admin.access', 'evaluations.view', 'evaluations.edit'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['admin.access', 'evaluations.view', 'evaluations.edit']);
        $this->actingAs($user);
    }

    private function evaluation(): ESBTPEvaluation
    {
        return ESBTPEvaluation::factory()->create([
            'classe_id' => ESBTPClasse::factory(),
            'date_evaluation' => now()->addDay(),
            'is_published' => false,
            'notes_published' => false,
        ]);
    }

    public function test_un_etat_de_publication_explicitement_demande_est_idempotent(): void
    {
        $evaluation = $this->evaluation();
        $url = route('esbtp.evaluations.toggle-published', $evaluation);

        $this->patchJson($url, ['is_published' => true])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('is_published', true)
            ->assertJsonPath('unchanged', false);

        $this->patchJson($url, ['is_published' => true])
            ->assertOk()->assertJsonPath('is_published', true)->assertJsonPath('unchanged', true);

        $this->patchJson($url, ['is_published' => false])
            ->assertOk()->assertJsonPath('is_published', false)->assertJsonPath('unchanged', false);

        $this->patchJson($url, ['is_published' => false])
            ->assertOk()->assertJsonPath('is_published', false)->assertJsonPath('unchanged', true);

        $this->assertFalse((bool) $evaluation->fresh()->is_published);
        $this->assertFalse((bool) $evaluation->fresh()->notes_published);
    }

    public function test_ancien_appel_sans_etat_cible_continue_de_basculer(): void
    {
        $evaluation = $this->evaluation();
        $url = route('esbtp.evaluations.toggle-published', $evaluation);

        $this->patchJson($url)->assertOk()->assertJsonPath('is_published', true);
        $this->patchJson($url)->assertOk()->assertJsonPath('is_published', false);
    }

    public function test_depublication_de_notes_est_idempotente_et_publication_ineligible_est_refusee(): void
    {
        $evaluation = $this->evaluation();
        $url = route('esbtp.evaluations.toggle-notes-published', $evaluation);

        $this->patchJson($url, ['notes_published' => false])
            ->assertOk()->assertJsonPath('notes_published', false)->assertJsonPath('unchanged', true);
        $this->patchJson($url, ['notes_published' => false])
            ->assertOk()->assertJsonPath('notes_published', false)->assertJsonPath('unchanged', true);

        $this->patchJson($url, ['notes_published' => true])
            ->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_etat_cible_invalide_retourne_422_et_ne_modifie_rien(): void
    {
        $evaluation = $this->evaluation();

        $this->patchJson(route('esbtp.evaluations.toggle-published', $evaluation), ['is_published' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('is_published');

        $this->assertFalse((bool) $evaluation->fresh()->is_published);
    }

    public function test_un_utilisateur_avec_lecture_seule_ne_peut_pas_muter_les_evaluations(): void
    {
        $evaluation = $this->evaluation();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['admin.access', 'evaluations.view']);
        $this->actingAs($viewer);

        $this->patchJson(route('esbtp.evaluations.restore', $evaluation))->assertForbidden();
        $this->patchJson(route('esbtp.evaluations.cancel', $evaluation))->assertForbidden();
        $this->patchJson(route('esbtp.evaluations.toggle-published', $evaluation))->assertForbidden();
        $this->patchJson(route('esbtp.evaluations.toggle-notes-published', $evaluation))->assertForbidden();
        $this->patchJson(route('esbtp.evaluations.update-status', $evaluation), ['status' => 'draft'])->assertForbidden();
        $this->deleteJson(route('esbtp.evaluations.destroy', $evaluation))->assertForbidden();
    }
}
