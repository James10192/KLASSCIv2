<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Annuler n'est pas supprimer : le versement reste visible, un avoir le
 * compense. Et un versement supprime par erreur se restaure.
 */
class CliAnnulationPaiementTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        Sanctum::actingAs(User::factory()->create(), ['cli:read', 'cli:admin']);

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $classe = ESBTPClasse::factory()->create(['annee_universitaire_id' => $annee->id]);
        $this->inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => ESBTPEtudiant::factory()->create()->id,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);
    }

    public function test_la_simulation_n_ecrit_rien_et_l_application_emet_un_avoir_total(): void
    {
        $paiement = $this->versement();
        $corps = ['avoir_kind' => 'refund', 'motif' => 'Versement saisi par erreur au guichet.'];

        $this->postJson("/api/cli/paiements/{$paiement->id}/annuler", $corps)
            ->assertOk()
            ->assertJsonPath('data.applique', false)
            ->assertJsonPath('data.montant_a_annuler', 47000);
        $this->assertSame(0, ESBTPPaiement::where('parent_paiement_id', $paiement->id)->count());

        $this->postJson("/api/cli/paiements/{$paiement->id}/annuler", $corps + ['apply' => true])
            ->assertOk()
            ->assertJsonPath('data.applique', true);

        $avoir = ESBTPPaiement::where('parent_paiement_id', $paiement->id)->sole();
        $this->assertSame(47000.0, (float) $avoir->montant);
        $this->assertSame('refund', $avoir->avoir_kind);
        // Le versement d'origine reste là : c'est la différence avec supprimer.
        $this->assertNotSoftDeleted($paiement);

        $this->postJson("/api/cli/paiements/{$paiement->id}/annuler", $corps + ['apply' => true])
            ->assertStatus(422);
    }

    public function test_le_sort_de_l_argent_et_le_motif_sont_exiges(): void
    {
        $paiement = $this->versement();

        $this->postJson("/api/cli/paiements/{$paiement->id}/annuler", ['motif' => 'Motif assez long ici.'])
            ->assertStatus(422);
        $this->postJson("/api/cli/paiements/{$paiement->id}/annuler", ['avoir_kind' => 'credit', 'motif' => 'court'])
            ->assertStatus(422);
    }

    public function test_un_versement_supprime_se_restaure(): void
    {
        $paiement = $this->versement();
        $paiement->forceFill(['motif_suppression' => 'Supprimé par erreur.'])->save();
        $paiement->delete();

        $this->postJson("/api/cli/paiements/{$paiement->id}/restaurer")
            ->assertOk()->assertJsonPath('data.applique', false);
        $this->assertSoftDeleted($paiement);

        $this->postJson("/api/cli/paiements/{$paiement->id}/restaurer", ['apply' => true])
            ->assertOk()->assertJsonPath('data.applique', true);

        $this->assertNotSoftDeleted($paiement);
        $this->assertNull($paiement->fresh()->motif_suppression);
    }

    public function test_un_versement_d_une_periode_close_ne_se_restaure_pas(): void
    {
        $paiement = $this->versement();
        $paiement->forceFill(['date_paiement' => '2026-01-10'])->save();
        $paiement->delete();
        \App\Helpers\SettingsHelper::setOrCreate('comptabilite.period_locked_until', '2026-01-31', 'comptabilite', 'string');

        $this->postJson("/api/cli/paiements/{$paiement->id}/restaurer", ['apply' => true])
            ->assertStatus(422);

        $this->assertSoftDeleted($paiement);
    }

    private function versement(): ESBTPPaiement
    {
        return ESBTPPaiement::factory()->pour($this->inscription)->create([
            'status' => 'validé',
            'montant' => 47000,
            'date_paiement' => now()->toDateString(),
        ]);
    }
}
