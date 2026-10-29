<?php

namespace Tests\Feature\Inscriptions;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Encaisser depuis la liste des inscriptions : ce que voit le caissier quand
 * le serveur refuse.
 *
 * Le refus existait et son message était complet — il disait le montant saisi,
 * le reste dû, le total et le déjà payé. Mais il partait en REDIRECTION HTML,
 * alors que le modal de la liste soumet en AJAX et lit du JSON : la lecture
 * échouait, et le caissier recevait « Erreur lors de la soumission », sans rien
 * qui lui dise quoi corriger.
 *
 * Ce qui est vérifié ici est donc le CANAL autant que la règle : un refus doit
 * arriver jusqu'à l'écran, dans la forme que l'écran sait lire.
 */
class EncaissementDepuisLaListeTest extends TestCase
{
    use DatabaseTransactions;

    private User $caissier;

    private ESBTPInscription $inscription;

    private ESBTPFraisCategory $scolarite;

    private ESBTPFraisCategory $tenue;

    protected function setUp(): void
    {
        parent::setUp();

        // Deux middlewares hors sujet renverraient la requête ailleurs :
        // l'assistant d'installation et le paywall, qui interroge l'API du SaaS
        // maître. Neutralisés nommément — `withoutMiddleware()` sans argument
        // désactiverait aussi la résolution des routes.
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('paiements.create', 'web');
        Permission::findOrCreate('paiements.validate', 'web');
        Cache::flush();

        $this->caissier = User::factory()->create();
        Permission::findOrCreate('inscriptions.view', 'web');
        // Lire le reste dû passe par le groupe de consultation des inscriptions.
        $this->caissier->givePermissionTo(['admin.access', 'paiements.create', 'inscriptions.view']);
        $this->actingAs($this->caissier);

        $classe = ESBTPClasse::factory()->create();
        $this->inscription = ESBTPInscription::factory()->create([
            'classe_id' => $classe->id,
            'filiere_id' => $classe->filiere_id,
            'niveau_id' => $classe->niveau_etude_id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'created_by' => $this->caissier->id,
        ]);

        $this->scolarite = ESBTPFraisCategory::factory()->ordre(1)->create(['name' => 'Scolarite']);
        $this->tenue = ESBTPFraisCategory::factory()->ordre(2)->create(['name' => 'Tenue']);

        // L'étudiant doit la scolarité. Il n'est PAS souscrit à la tenue.
        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $this->scolarite->id,
            'amount' => 150000,
            'created_by' => $this->caissier->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Le canal : un refus doit arriver lisible jusqu'à l'écran
    // ------------------------------------------------------------------

    public function test_un_depassement_en_ajax_repond_en_json_et_dit_combien_il_reste(): void
    {
        $reponse = $this->postJson(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(200000, $this->scolarite)
        );

        $reponse->assertStatus(422);
        $reponse->assertJsonPath('success', false);

        $message = $reponse->json('message');
        $this->assertStringContainsString('dépasse le montant restant', $message);
        $this->assertStringContainsString('150 000', $message, 'Le reste dû doit être nommé.');
        $this->assertStringContainsString('200 000', $message, 'Le montant saisi aussi.');

        // Le champ fautif est désigné : l'écran peut le pointer.
        $reponse->assertJsonPath('errors.montant.0', $message);

        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_un_frais_non_souscrit_en_ajax_repond_en_json(): void
    {
        $reponse = $this->postJson(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(10000, $this->tenue)
        );

        $reponse->assertStatus(422);
        $reponse->assertJsonPath('success', false);
        $this->assertStringContainsString('pas souscrit', $reponse->json('message'));
        $reponse->assertJsonStructure(['errors' => ['fee_category_id']]);

        $this->assertSame(0, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    // ------------------------------------------------------------------
    // La règle elle-même, et le canal historique
    // ------------------------------------------------------------------

    public function test_hors_ajax_le_refus_reste_une_redirection_avec_ses_erreurs(): void
    {
        // La fiche d'inscription poste un formulaire classique : elle doit
        // continuer à recevoir ses erreurs de session, pas du JSON.
        $reponse = $this->from(route('esbtp.inscriptions.index'))->post(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(200000, $this->scolarite)
        );

        $reponse->assertRedirect(route('esbtp.inscriptions.index'));
        $reponse->assertSessionHasErrors('montant');
    }

    public function test_un_montant_egal_au_reste_du_passe(): void
    {
        $reponse = $this->postJson(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(150000, $this->scolarite)
        );

        $reponse->assertStatus(200);
        $reponse->assertJsonPath('success', true);
        $this->assertSame(1, ESBTPPaiement::where('inscription_id', $this->inscription->id)->count());
    }

    public function test_le_reste_du_tient_compte_de_ce_qui_est_deja_encaisse(): void
    {
        $this->postJson(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(100000, $this->scolarite)
        )->assertStatus(200);

        // 50 000 restent. En demander 60 000 doit être refusé, et le message
        // doit annoncer le reste réel, pas le total de la souscription.
        $reponse = $this->postJson(
            route('esbtp.inscriptions.valider-avec-paiement', $this->inscription),
            $this->versement(60000, $this->scolarite)
        );

        $reponse->assertStatus(422);
        $this->assertStringContainsString('50 000', $reponse->json('message'));
    }

    // ------------------------------------------------------------------
    // La borne que l'écran lit avant la saisie
    // ------------------------------------------------------------------

    public function test_l_ecran_peut_demander_le_reste_du_avant_la_saisie(): void
    {
        $reponse = $this->getJson(route('esbtp.inscriptions.frais.montant-restant', [
            'inscription' => $this->inscription->id,
            'category' => $this->scolarite->id,
        ]));

        $reponse->assertStatus(200);
        $reponse->assertJsonPath('is_subscribed', true);
        $this->assertEqualsWithDelta(150000, $reponse->json('montant_restant'), 0.01);
        $this->assertEqualsWithDelta(150000, $reponse->json('montant_total'), 0.01);
        $this->assertEqualsWithDelta(0, $reponse->json('montant_paye'), 0.01);
    }

    public function test_le_reste_du_d_un_frais_non_souscrit_est_annonce_comme_tel(): void
    {
        $reponse = $this->getJson(route('esbtp.inscriptions.frais.montant-restant', [
            'inscription' => $this->inscription->id,
            'category' => $this->tenue->id,
        ]));

        $reponse->assertStatus(400);
        $reponse->assertJsonPath('is_subscribed', false);
    }

    /** @return array<string, mixed> */
    private function versement(float $montant, ESBTPFraisCategory $categorie): array
    {
        return [
            'montant' => $montant,
            'fee_category_id' => $categorie->id,
            'mode_paiement' => 'especes',
            'date_paiement' => now()->toDateString(),
            'reference_paiement' => 'REF-'.uniqid(),
            'observations' => 'Test',
        ];
    }
}
