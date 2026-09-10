<?php

namespace Tests\Feature\Paiements;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReliquatDetail;
use App\Models\User;
use App\Helpers\SettingsHelper;
use App\Services\InKindDepositService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Supprimer un versement : le droit `paiements.delete` suffit, le motif est
 * obligatoire, et la trace (qui, pourquoi) reste sur la ligne supprimée.
 *
 * Avant : la route demandait `paiements.delete` mais la méthode exigeait en
 * plus `paiements.manage`. Une école qui posait le droit sur un rôle obtenait
 * un bouton qui répondait 403.
 */
class SupprimerVersementAvecMotifTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $rames;

    private ESBTPPaiement $versement;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['paiements.delete', 'paiements.manage', 'paiements.view', 'identity.enrollment_officer', 'inscriptions.view', 'inscriptions.in_kind.mark'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Cache::flush();

        $this->inscription = ESBTPInscription::factory()->create();
        $this->rames = ESBTPFraisCategory::factory()->create(['accepts_in_kind' => true]);
        $this->versement = ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->surCategorie($this->rames->id)
            ->montant(3000)
            ->create();
    }

    /**
     * Un membre du personnel (l'accès aux écrans de paiement exige une
     * identité métier) qui porte, en plus, les droits demandés.
     */
    private function quiPeut(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(array_merge(['identity.enrollment_officer'], $permissions));

        return $user;
    }

    public function test_le_droit_de_supprimer_suffit_et_la_trace_est_gardee(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);
        $inKind = app(InKindDepositService::class);

        // Tant que le versement est là, le frais ne peut pas être marqué déposé.
        $this->assertFalse($inKind->canMarkCategory($this->inscription, $this->rames, null));

        $reponse = $this->actingAs($agent)->delete(route('esbtp.paiements.destroy', $this->versement), [
            'motif' => 'Encaissé par erreur, le paquet de rames a été déposé.',
            'retour' => '/esbtp/inscriptions/'.$this->inscription->id,
        ]);

        $reponse->assertRedirect(url('/esbtp/inscriptions/'.$this->inscription->id))
            ->assertSessionHas('success');

        $supprime = ESBTPPaiement::withTrashed()->find($this->versement->id);
        $this->assertNotNull($supprime->deleted_at, 'Le versement doit être supprimé (logiquement).');
        $this->assertSame($agent->id, (int) $supprime->deleted_by);
        $this->assertSame('Encaissé par erreur, le paquet de rames a été déposé.', $supprime->motif_suppression);

        // Et le frais redevient marquable en nature.
        $this->assertTrue($inKind->canMarkCategory($this->inscription, $this->rames, null));
    }

    public function test_sans_motif_rien_n_est_supprime(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);

        // Neuf caractères : un de moins que le minimum.
        $this->actingAs($agent)
            ->from(route('esbtp.paiements.show', $this->versement))
            ->delete(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'trop cour'])
            ->assertSessionHasErrors('motif');

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_sans_le_droit_c_est_refuse(): void
    {
        $lecteur = $this->quiPeut(['paiements.view']);

        $this->actingAs($lecteur)
            ->delete(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertForbidden();

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_un_versement_reconcilie_reste_intouchable(): void
    {
        $this->versement->forceFill(['reconciliation_locked_at' => now()])->save();
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->from(route('esbtp.paiements.show', $this->versement))
            ->delete(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertRedirect(route('esbtp.paiements.show', $this->versement))
            ->assertSessionHas('error');

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_le_retour_ne_sort_jamais_du_site(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->delete(route('esbtp.paiements.destroy', $this->versement), [
                'motif' => 'Un motif suffisamment long.',
                'retour' => 'https://ailleurs.example/piege',
            ])
            ->assertRedirect(route('esbtp.paiements.index'));
    }

    public function test_le_journal_d_audit_porte_l_auteur_et_le_motif(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)->delete(route('esbtp.paiements.destroy', $this->versement), [
            'motif' => 'Encaissé par erreur, le paquet de rames a été déposé.',
        ])->assertRedirect();

        $audits = \OwenIt\Auditing\Models\Audit::query()
            ->where('auditable_type', ESBTPPaiement::class)
            ->where('auditable_id', $this->versement->id)
            ->orderBy('id')
            ->get();

        $modifie = $audits->firstWhere('event', 'updated');
        $this->assertNotNull($modifie, 'La pose du motif doit laisser un événement « modifié ».');
        $this->assertSame($agent->id, (int) $modifie->user_id);
        $this->assertSame('Encaissé par erreur, le paquet de rames a été déposé.', $modifie->new_values['motif_suppression'] ?? null);
        $this->assertSame($agent->id, (int) ($modifie->new_values['deleted_by'] ?? 0));

        $this->assertNotNull($audits->firstWhere('event', 'deleted'), 'La suppression elle-même doit être journalisée.');
    }

    public function test_en_json_l_absence_de_motif_repond_422_et_ne_supprime_rien(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->deleteJson(route('esbtp.paiements.destroy', $this->versement), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motif');

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);

        // Et avec un motif, la réponse JSON dit le succès (chemin du shell mobile).
        $this->actingAs($agent)
            ->deleteJson(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_une_periode_cloturee_refuse_meme_avec_le_droit(): void
    {
        SettingsHelper::setOrCreate('comptabilite.period_locked_until', now()->addDay()->toDateString(), 'comptabilite', 'date');
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->deleteJson(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertStatus(403);

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_un_versement_de_reliquat_valide_ne_se_supprime_pas(): void
    {
        $souscription = \App\Models\ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->rames->id,
        ]);
        $reliquat = ESBTPReliquatDetail::query()->create([
            'inscription_source_id' => $this->inscription->id,
            'inscription_destination_id' => $this->inscription->id,
            'frais_subscription_id' => $souscription->id,
            'montant_attendu' => 3000,
            'montant_paye' => 0,
            'montant_reliquat' => 3000,
            'montant_regle' => 3000,
            'statut' => 'totalement_regle',
            'date_creation' => now(),
            'created_by' => User::factory()->create()->id,
        ]);
        $this->versement->forceFill(['type_paiement' => 'reliquat', 'reliquat_detail_id' => $reliquat->id])->save();
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->deleteJson(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertStatus(422);

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_un_versement_support_d_un_avoir_ne_se_supprime_pas(): void
    {
        ESBTPPaiement::factory()->pour($this->inscription)->surCategorie($this->rames->id)->montant(1000)
            ->create(['nature' => 'avoir', 'avoir_kind' => 'credit', 'parent_paiement_id' => $this->versement->id]);
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->deleteJson(route('esbtp.paiements.destroy', $this->versement), ['motif' => 'Un motif suffisamment long.'])
            ->assertStatus(422);

        $this->assertNull(ESBTPPaiement::withTrashed()->find($this->versement->id)->deleted_at);
    }

    public function test_le_retour_relatif_au_protocole_est_refuse(): void
    {
        $agent = $this->quiPeut(['paiements.delete']);

        $this->actingAs($agent)
            ->delete(route('esbtp.paiements.destroy', $this->versement), [
                'motif' => 'Un motif suffisamment long.',
                'retour' => '//ailleurs.example/piege',
            ])
            ->assertRedirect(route('esbtp.paiements.index'));
    }

    public function test_la_fiche_d_inscription_nomme_le_versement_qui_bloque_le_depot(): void
    {
        $agent = $this->quiPeut(['paiements.delete', 'inscriptions.view', 'inscriptions.in_kind.mark']);

        $html = $this->actingAs($agent)->get(route('esbtp.inscriptions.show', $this->inscription))->assertOk()->getContent();

        $this->assertStringContainsString('Encaissé (reçu '.$this->versement->numero_recu.')', $html);
        $this->assertStringContainsString('id="modalSupprimerVersement'.$this->versement->id.'"', $html);
        $this->assertStringContainsString('name="retour" value="/esbtp/inscriptions/'.$this->inscription->id.'"', $html);
    }
}
