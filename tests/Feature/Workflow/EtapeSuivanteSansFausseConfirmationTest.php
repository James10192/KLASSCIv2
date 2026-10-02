<?php

namespace Tests\Feature\Workflow;

use App\Models\User;
use App\Support\WorkflowFlash;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * L'étape suivante d'un workflow ne doit jamais avoir l'air d'une
 * confirmation : sur la page où elle se fait, un bandeau sans bouton ;
 * ailleurs, une fenêtre qui ouvre une page.
 */
class EtapeSuivanteSansFausseConfirmationTest extends TestCase
{
    use DatabaseTransactions;

    private function etape(string $url): void
    {
        session()->put('workflow_next_step', ['type' => 'paiement.created', 'label' => 'Valider ce paiement', 'url' => $url]);
    }

    private function rendre(string $chemin): string
    {
        $this->app->instance('request', Request::create($chemin));

        return view('layouts.partials.etape-suivante')->render();
    }

    public function test_sur_la_page_de_l_etape_un_bandeau_sans_bouton_d_action(): void
    {
        $this->etape(url('/esbtp/paiements/42'));

        $html = $this->rendre('/esbtp/paiements/42');

        $this->assertStringContainsString('wns-bandeau', $html);
        $this->assertStringContainsString("Rien n'est encore fait", $html);
        $this->assertStringNotContainsString('workflowNextStepModal', $html);
        $this->assertStringNotContainsString('href=', $html);
    }

    public function test_ailleurs_la_fenetre_ouvre_une_page_et_ne_valide_rien(): void
    {
        $this->etape(url('/esbtp/paiements/42'));

        $html = $this->rendre('/esbtp/inscriptions/7');

        $this->assertStringContainsString('workflowNextStepModal', $html);
        $this->assertStringContainsString('Ouvrir la page', $html);
        $this->assertStringNotContainsString('data-bs-backdrop="static"', $html);
        // Le libellé de l'étape reste un nom d'étape, pas le texte d'un bouton d'action.
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>\s*<i[^>]*><\/i>\s*Valider/u', $html);
    }

    public function test_une_action_sans_rechargement_ne_laisse_rien_en_session(): void
    {
        Event::fake();
        Permission::findOrCreate('paiements.validate', 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo('paiements.validate');
        $this->app->instance('request', Request::create('/esbtp/paiements', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']));

        WorkflowFlash::dispatch('paiement.created', $user, ['paiement_id' => 42]);

        $this->assertNull(session('workflow_next_step'));
    }

    public function test_une_action_en_page_pose_l_etape_pour_qui_peut_la_faire(): void
    {
        Event::fake();
        Permission::findOrCreate('paiements.validate', 'web');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo('paiements.validate');
        $this->app->instance('request', Request::create('/esbtp/paiements', 'POST'));

        WorkflowFlash::dispatch('paiement.created', $user, ['paiement_id' => 42]);

        $this->assertSame('paiement.created', session('workflow_next_step.type'));
    }

    public function test_une_etape_faite_clot_l_avis_chez_tout_le_monde(): void
    {
        $collegue = User::factory()->create();
        $avis = fn (int $paiement) => \Illuminate\Notifications\DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => \App\Notifications\WorkflowNextStepNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $collegue->id,
            'data' => ['type' => 'paiement.created', 'context' => ['paiement' => $paiement, 'inscription_id' => 9], 'next_label' => 'Valider ce paiement'],
        ]);
        $concerne = $avis(42);
        $autre = $avis(43);

        (new \App\Listeners\CloreLesEtapesFaites())->handle(
            new \App\Events\WorkflowStepCompleted('paiement.validated', User::factory()->create(), ['inscription' => 9, 'paiement' => 42])
        );

        $this->assertNotNull($concerne->fresh()->read_at);
        $this->assertNull($autre->fresh()->read_at);
    }
}
