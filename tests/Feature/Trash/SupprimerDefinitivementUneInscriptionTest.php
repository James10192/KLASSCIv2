<?php

namespace Tests\Feature\Trash;

use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Supprimer définitivement une inscription depuis la corbeille.
 *
 * Deux défauts corrigés ici :
 *
 * 1. La suppression échouait dès qu'une facture était rattachée — clé étrangère
 *    RESTRICT — et l'erreur SQL brute partait à l'écran, nom de la base compris.
 * 2. Rien n'empêchait d'emporter des versements VALIDÉS : leur clé étrangère est
 *    en cascade, ils disparaissaient sans un mot. L'écran annonçait pourtant un
 *    blocage que le serveur n'appliquait pas.
 */
class SupprimerDefinitivementUneInscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPInscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superAdmin', 'web');
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);
        foreach (['trash.view', 'inscriptions.force_delete', 'identity.enrollment_officer'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        Cache::flush();

        $this->inscription = ESBTPInscription::factory()->create();
        $this->inscription->delete();
    }

    private function comptable(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['identity.enrollment_officer', 'trash.view', 'inscriptions.force_delete']);

        return $user;
    }

    private function facturePour(ESBTPInscription $inscription): int
    {
        return DB::table('esbtp_factures')->insertGetId([
            'numero_facture' => 'FAC-'.uniqid(),
            'etudiant_id' => $inscription->etudiant_id,
            'inscription_id' => $inscription->id,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addMonth()->toDateString(),
            'montant_ht' => 1000,
            'taux_taxe' => 0,
            'montant_taxe' => 0,
            'montant_ttc' => 1000,
            'montant_regle' => 0,
            'montant_du' => 1000,
            'statut' => 'emise',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function une_facture_liee_ne_bloque_plus_la_suppression(): void
    {
        $factureId = $this->facturePour($this->inscription);

        $reponse = $this->actingAs($this->comptable())
            ->deleteJson("/esbtp/trash/inscriptions/{$this->inscription->id}/force");

        $reponse->assertOk()->assertJson(['success' => true]);
        $this->assertStringContainsString('1 facture(s)', $reponse->json('message'));

        $this->assertDatabaseMissing('esbtp_factures', ['id' => $factureId]);
        $this->assertNull(ESBTPInscription::withTrashed()->find($this->inscription->id));
    }

    /** @test */
    public function un_versement_valide_actif_bloque_la_suppression(): void
    {
        $versement = ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->montant(5000)
            ->create(['status' => 'validé']);

        $reponse = $this->actingAs($this->comptable())
            ->deleteJson("/esbtp/trash/inscriptions/{$this->inscription->id}/force");

        $reponse->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('versement(s) validé(s)', $reponse->json('message'));

        // Ni l'inscription ni l'encaissement n'ont bougé.
        $this->assertNotNull(ESBTPInscription::withTrashed()->find($this->inscription->id));
        $this->assertDatabaseHas('esbtp_paiements', ['id' => $versement->id, 'deleted_at' => null]);
    }

    /** @test */
    public function un_versement_non_valide_suit_la_suppression(): void
    {
        $versement = ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->montant(5000)
            ->create(['status' => 'en_attente']);

        $this->actingAs($this->comptable())
            ->deleteJson("/esbtp/trash/inscriptions/{$this->inscription->id}/force")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('esbtp_paiements', ['id' => $versement->id]);
    }

    /** @test */
    public function le_message_d_erreur_ne_montre_jamais_de_sql(): void
    {
        $versement = ESBTPPaiement::factory()
            ->pour($this->inscription)
            ->montant(5000)
            ->create(['status' => 'validé']);

        $message = $this->actingAs($this->comptable())
            ->deleteJson("/esbtp/trash/inscriptions/{$this->inscription->id}/force")
            ->json('message');

        foreach (['SQLSTATE', 'foreign key', 'CONSTRAINT', 'esbtp_factures', 'esbtp_paiements'] as $fuite) {
            $this->assertStringNotContainsStringIgnoringCase($fuite, $message);
        }

        $this->assertNotNull($versement->fresh());
    }

    /** @test */
    public function l_ecran_annonce_les_factures_avant_le_clic(): void
    {
        $this->facturePour($this->inscription);

        $donnees = $this->actingAs($this->comptable())
            ->getJson("/esbtp/trash/inscriptions/{$this->inscription->id}/dependencies")
            ->assertOk()
            ->json('data');

        $types = array_column($donnees['cascading_force_delete'], 'type');
        $this->assertContains('factures', $types, 'Les factures doivent être annoncées comme emportées.');
        $this->assertFalse($donnees['has_blocking'], 'Une facture seule ne bloque pas.');
    }

    /** @test */
    public function l_ecran_signale_le_versement_valide_comme_bloquant(): void
    {
        ESBTPPaiement::factory()->pour($this->inscription)->montant(5000)->create(['status' => 'validé']);
        ESBTPPaiement::factory()->pour($this->inscription)->montant(1000)->create(['status' => 'en_attente']);

        $donnees = $this->actingAs($this->comptable())
            ->getJson("/esbtp/trash/inscriptions/{$this->inscription->id}/dependencies")
            ->assertOk()
            ->json('data');

        $this->assertTrue($donnees['has_blocking']);
        $this->assertContains('paiements_valides', array_column($donnees['blocking_force_delete'], 'type'));
        $this->assertContains('paiements_non_valides', array_column($donnees['cascading_force_delete'], 'type'));
    }

    /** @test */
    public function le_droit_inscriptions_force_delete_est_exige(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['identity.enrollment_officer', 'trash.view']);

        $this->actingAs($user)
            ->deleteJson("/esbtp/trash/inscriptions/{$this->inscription->id}/force")
            ->assertStatus(403);

        $this->assertNotNull(ESBTPInscription::withTrashed()->find($this->inscription->id));
    }

    /** @test */
    public function une_inscription_absente_de_la_corbeille_repond_introuvable(): void
    {
        $active = ESBTPInscription::factory()->create();

        $this->actingAs($this->comptable())
            ->deleteJson("/esbtp/trash/inscriptions/{$active->id}/force")
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->assertNotNull($active->fresh());
    }
}
